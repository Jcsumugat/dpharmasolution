<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Prescription;
use App\Models\Order;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use App\Services\FileEncryptionService;
use App\Services\DuplicateDetectionService;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Crypt;

class ClientUpload extends Controller
{
    public function handleUpload(Request $request)
    {
        Log::info('Upload attempt - Auth status:', [
            'is_customer_logged_in' => Auth::guard('customer')->check(),
            'customer_id' => Auth::guard('customer')->id(),
        ]);

        if (!Auth::guard('customer')->check()) {
            Log::error('Customer not authenticated during upload');
            return redirect()->route('login.form')->with('error', 'Please log in to upload prescriptions.');
        }

        $customer = Auth::guard('customer')->user();

        if (!$customer || !isset($customer->id)) {
            Log::error('Customer object is null or missing ID');
            return redirect()->route('login.form')->with('error', 'Authentication error. Please log in again.');
        }

        $customerId = $customer->id;

        $validated = $request->validate([
            'mobile_number' => ['required', 'regex:/^09\d{9}$/'],
            'prescription_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'order_type' => ['required', 'in:prescription,online_order'],
            'file_hash' => ['nullable', 'string', 'regex:/^[a-f0-9]{32}$/i'],
        ]);

        try {
            $file = $request->file('prescription_file');

            // Get file info for debugging
            $filePath = $file->getRealPath();
            $fileSize = $file->getSize();
            $originalName = $file->getClientOriginalName();

            // Calculate both hashes
            $frontendHash = $validated['file_hash'] ?? null;
            $backendHash = md5_file($filePath);

            // Use frontend hash as primary (more consistent), backend as fallback
            $primaryHash = $frontendHash ?: $backendHash;

            // Get first 100 bytes for debugging
            $firstBytes = file_get_contents($filePath, false, null, 0, 100);
            $firstBytesArray = array_map('ord', str_split($firstBytes));

            Log::info('File hash comparison', [
                'frontend_hash' => $frontendHash,
                'backend_hash' => $backendHash,
                'primary_hash' => $primaryHash,
                'hashes_match' => $frontendHash === $backendHash,
                'hash_source' => $frontendHash ? 'frontend' : 'backend',
                'customer_id' => $customerId,
                'original_filename' => $originalName,
                'file_size' => $fileSize,
                'real_path' => $filePath,
                'first_100_bytes' => $firstBytesArray
            ]);

            // Check for EXACT duplicate using BOTH hashes to catch browser-modified files
            $isConfirmedDuplicate = false;
            $existingDuplicate = Prescription::where('customer_id', $customerId)
                ->where(function ($query) use ($frontendHash, $backendHash) {
                    if ($frontendHash) {
                        $query->where('file_hash', $frontendHash);
                    }
                    if ($backendHash && $frontendHash !== $backendHash) {
                        $query->orWhere('file_hash', $backendHash);
                    }
                })
                ->first();

            if ($existingDuplicate) {
                $isConfirmedDuplicate = true;
                Log::warning('Duplicate upload - user confirmed', [
                    'existing_prescription_id' => $existingDuplicate->id,
                    'existing_order_id' => $existingDuplicate->order->order_id ?? 'N/A',
                    'matched_hash' => $existingDuplicate->file_hash,
                    'frontend_hash' => $frontendHash,
                    'backend_hash' => $backendHash,
                    'customer_confirmed' => true
                ]);
            }

            // Proceed with encryption
            $filename = time() . '_' . Str::random(6) . '_customer_' . $customerId;

            $encryptionResult = FileEncryptionService::encryptAndStore(
                $file,
                'prescriptions',
                $filename
            );

            if (!$encryptionResult['success']) {
                throw new \Exception('File encryption failed');
            }

            $token = Str::random(32);

            $latestId = Order::max('id') ?? 0;
            $orderPrefix = $validated['order_type'] === 'prescription' ? 'RX' : 'OD';
            $orderId = $orderPrefix . str_pad($latestId + 1, 5, '0', STR_PAD_LEFT);

            $qrUrl = url("/preorder/validate/$token");
            $qrSvg = QrCode::format('svg')->size(250)->generate($qrUrl);
            $qrPath = 'qrcodes/' . $orderId . '.svg';
            Storage::disk('public')->put($qrPath, $qrSvg);

            // Create prescription with primary hash
            // Note: We store the primary hash (frontend preferred) but the system
            // will check both during comprehensive analysis
            $prescription = Prescription::create([
                'mobile_number' => $validated['mobile_number'],
                'notes' => $validated['notes'] ?? null,
                'file_path' => $encryptionResult['encrypted_path'],
                'original_filename' => $encryptionResult['metadata']['original_name'],
                'file_mime_type' => $encryptionResult['metadata']['mime_type'],
                'file_size' => $encryptionResult['metadata']['size'],
                'is_encrypted' => true,
                'token' => $token,
                'status' => 'pending',
                'order_type' => $validated['order_type'],
                'qr_code_path' => $qrPath,
                'file_hash' => $primaryHash,  // Store primary hash
                'user_id' => $customerId,
                'customer_id' => $customerId,
                'duplicate_check_status' => $isConfirmedDuplicate ? 'duplicate' : 'pending',
            ]);

            Log::info('Prescription created successfully', [
                'prescription_id' => $prescription->id,
                'order_id' => $orderId,
                'file_hash_stored' => $primaryHash,
                'frontend_hash' => $frontendHash,
                'backend_hash' => $backendHash,
                'hash_source' => $frontendHash ? 'frontend' : 'backend',
                'customer_id' => $customerId,
                'is_confirmed_duplicate' => $isConfirmedDuplicate
            ]);

            // Create order
            Order::create([
                'prescription_id' => $prescription->id,
                'order_id' => $orderId,
                'status' => 'Pending',
            ]);

            // Send notification
            NotificationService::notifyNewOrder($prescription);

            // Deep analysis (perceptual hash + OCR) - NON-BLOCKING
            $warningMessage = '';
            try {
                $decryptedData = FileEncryptionService::decryptFile($encryptionResult['encrypted_path']);

                if ($decryptedData['success']) {
                    $extension = $file->getClientOriginalExtension();
                    $tempPath = sys_get_temp_dir() . '/' . uniqid('rx_') . '.' . $extension;
                    file_put_contents($tempPath, $decryptedData['content']);

                    Log::info('Running comprehensive duplicate analysis', [
                        'prescription_id' => $prescription->id,
                        'is_confirmed_duplicate' => $isConfirmedDuplicate
                    ]);

                    // Run comprehensive check (includes perceptual hash + OCR)
                    $analysisResults = DuplicateDetectionService::comprehensiveCheck(
                        $tempPath,
                        $customerId,
                        $prescription->id
                    );

                    // Prepare update data
                    $updateData = [
                        'perceptual_hash' => $analysisResults['perceptual_hash'] ?? null,
                        'extracted_text' => $analysisResults['extracted_text'] ?? null,
                        'duplicate_check_status' => $analysisResults['duplicate_status'],
                        'similarity_score' => $analysisResults['highest_similarity'] ?? null,
                        'duplicate_checked_at' => now()
                    ];

                    // If this was a confirmed duplicate (caught by our dual hash check), mark it
                    if ($isConfirmedDuplicate && !empty($existingDuplicate)) {
                        $updateData['duplicate_of_id'] = $existingDuplicate->id;
                        $updateData['duplicate_check_status'] = 'duplicate';
                        $updateData['similarity_score'] = 100;

                        $updateData['admin_message'] = "⚠️ EXACT DUPLICATE: User uploaded identical file previously (Order #{$existingDuplicate->order->order_id}). " .
                            "Customer was notified but chose to proceed. Please verify if this is a legitimate reorder or accidental duplicate.";

                        $warningMessage = " Note: This is identical to a previous upload. Our team will verify it's a legitimate reorder.";
                    }
                    // Check for similar/text matches from analysis
                    elseif ($analysisResults['has_duplicate'] && !empty($analysisResults['matches'])) {
                        $topMatch = $analysisResults['matches'][0];
                        $updateData['duplicate_of_id'] = $topMatch['prescription_id'];

                        $similarity = $analysisResults['highest_similarity'];
                        $matchType = $analysisResults['match_type'] ?? 'unknown';

                        if ($matchType === 'exact') {
                            $updateData['admin_message'] = "⚠️ EXACT DUPLICATE: Identical to previous upload (Order #{$topMatch['order_id']}). " .
                                "Please verify with customer before processing.";
                            $warningMessage = " Note: This appears identical to a previous upload. Our team will review it.";
                        } else {
                            $updateData['admin_message'] = "⚠️ Potential duplicate detected ({$similarity}% {$matchType} match with Order #{$topMatch['order_id']}). " .
                                "Please verify with customer before processing.";
                            $warningMessage = " Note: This appears similar to a previous upload (" .
                                round($similarity, 1) . "% match). Our team will review it.";
                        }
                    }

                    $prescription->update($updateData);

                    // Clean up temp file
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }

                    Log::info('Comprehensive analysis completed', [
                        'prescription_id' => $prescription->id,
                        'has_duplicate' => $analysisResults['has_duplicate'],
                        'duplicate_status' => $analysisResults['duplicate_status'],
                        'similarity' => $analysisResults['highest_similarity'] ?? 'N/A',
                        'is_confirmed_duplicate' => $isConfirmedDuplicate
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Deep analysis failed (non-critical)', [
                    'prescription_id' => $prescription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            $successMessage = $validated['order_type'] === 'prescription'
                ? 'Thank you! Your prescription has been received and securely encrypted.' . $warningMessage
                : 'Thank you! Your online order has been received and securely processed.' . $warningMessage;

            return redirect()->route('prescription.upload.form')
                ->with('success', $successMessage)
                ->with('qr_link', $qrUrl)
                ->with('qr_image', asset('storage/' . $qrPath));
        } catch (\Exception $e) {
            Log::error('Upload failed:', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['prescription_file' => 'Failed to process file: ' . $e->getMessage()]);
        }
    }

    public function viewDocument($id)
    {
        try {
            $prescription = Prescription::findOrFail($id);

            // Security check
            if ($prescription->customer_id !== Auth::guard('customer')->id()) {
                Log::warning('Unauthorized prescription document access attempt', [
                    'customer_id' => Auth::guard('customer')->id(),
                    'prescription_id' => $id
                ]);
                abort(403, 'Unauthorized access to this document');
            }

            // Check if file exists
            if (!$prescription->file_path || !Storage::exists($prescription->file_path)) {
                abort(404, 'Document not found');
            }

            Log::info('Customer viewing prescription document', [
                'customer_id' => Auth::guard('customer')->id(),
                'prescription_id' => $id
            ]);

            // Use FileEncryptionService to decrypt
            $decryptedData = FileEncryptionService::decryptFile($prescription->file_path);

            if (!$decryptedData['success']) {
                abort(500, 'Failed to decrypt document');
            }

            // Return the decrypted file
            return Response::make($decryptedData['content'], 200, [
                'Content-Type' => $decryptedData['metadata']['mime_type'] ?? 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . ($decryptedData['metadata']['original_name'] ?? 'document') . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        } catch (\Exception $e) {
            Log::error('Error viewing prescription document:', [
                'prescription_id' => $id,
                'customer_id' => Auth::guard('customer')->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error retrieving document',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function showUploadForm()
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('login.form')->with('error', 'Please log in to view this page.');
        }

        $customer = Auth::guard('customer')->user();

        if (!$customer || !isset($customer->id)) {
            Log::error('Customer authentication failed in showUploadForm');
            return redirect()->route('login.form')->with('error', 'Please log in to view this page.');
        }

        $customerId = $customer->id;

        $prescriptions = Prescription::with(['order', 'customer'])
            ->where('customer_id', $customerId)
            ->latest()
            ->get();

        Log::info('Prescriptions retrieved:', [
            'customer_id_used' => $customerId,
            'prescriptions_count' => $prescriptions->count(),
        ]);

        return view('client.uploads', compact('prescriptions'));
    }



    public function showPrescriptionHistory()
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('login.form')->with('error', 'Please log in to view your history.');
        }

        $customer = Auth::guard('customer')->user();
        $customerId = $customer->id;

        $prescriptions = Prescription::with(['order', 'customer'])
            ->where('customer_id', $customerId)
            ->where('order_type', 'prescription')
            ->latest()
            ->get();

        return view('client.prescription-history', compact('prescriptions'));
    }

    public function showOnlineOrderHistory()
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('login.form')->with('error', 'Please log in to view your history.');
        }

        $customer = Auth::guard('customer')->user();
        $customerId = $customer->id;

        $orders = Prescription::with(['order', 'customer'])
            ->where('customer_id', $customerId)
            ->where('order_type', 'online_order')
            ->latest()
            ->get();

        return view('client.online-order-history', compact('orders'));
    }

    public function validatePreorder(string $token)
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('login.form')->with('error', 'Please log in to validate your preorder.');
        }

        $customer = Auth::guard('customer')->user();
        $customerId = $customer->id;

        $prescription = Prescription::where('token', $token)
            ->where('customer_id', $customerId)
            ->first();

        if (!$prescription) {
            abort(404, 'Invalid or expired QR code token.');
        }

        $order = $prescription->order;

        return view('client.preorder-confirmation', compact('prescription', 'order'));
    }

    public function showQrCode($id)
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('login.form')->with('error', 'Please log in to view QR codes.');
        }

        $customer = Auth::guard('customer')->user();
        $customerId = $customer->id;

        $prescription = Prescription::where('id', $id)
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $qrPath = asset('storage/' . ($prescription->qr_code_path ?? ''));

        return view('client.qr-display', compact('qrPath'));
    }

    public function viewStatus(string $token)
    {
        $prescription = Prescription::where('token', $token)->first();

        if (!$prescription) {
            abort(404, 'Order not found.');
        }

        return view('auth.prescription-status', compact('prescription'));
    }

    public function viewPrescriptionFile($prescriptionId)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            Log::warning('Unauthorized prescription file access attempt', [
                'user_id' => Auth::id(),
                'prescription_id' => $prescriptionId,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            abort(403, 'Unauthorized access. Only administrators can view prescription files.');
        }

        $prescription = Prescription::findOrFail($prescriptionId);

        if (!$prescription->file_path || !$prescription->is_encrypted) {
            abort(404, 'Prescription file not found or not encrypted.');
        }

        try {
            Log::info('Admin accessed prescription file', [
                'admin_id' => Auth::id(),
                'prescription_id' => $prescriptionId,
                'customer_id' => $prescription->customer_id,
                'order_type' => $prescription->order_type,
                'ip' => request()->ip()
            ]);

            if (str_starts_with($prescription->file_mime_type, 'image/')) {
                return FileEncryptionService::displayDecryptedImage($prescription->file_path);
            } else {
                return FileEncryptionService::downloadDecryptedFile($prescription->file_path);
            }
        } catch (\Exception $e) {
            Log::error('Failed to decrypt prescription file:', [
                'prescription_id' => $prescriptionId,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            abort(500, 'Failed to load prescription file.');
        }
    }

    public function downloadPrescriptionFile($prescriptionId)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            Log::warning('Unauthorized prescription file download attempt', [
                'user_id' => Auth::id(),
                'prescription_id' => $prescriptionId,
                'ip' => request()->ip()
            ]);
            abort(403, 'Unauthorized access. Only administrators can download prescription files.');
        }

        $prescription = Prescription::findOrFail($prescriptionId);

        if (!$prescription->file_path || !$prescription->is_encrypted) {
            abort(404, 'Prescription file not found or not encrypted.');
        }

        try {
            Log::info('Admin downloaded prescription file', [
                'admin_id' => Auth::id(),
                'prescription_id' => $prescriptionId,
                'customer_id' => $prescription->customer_id,
                'order_type' => $prescription->order_type,
                'original_filename' => $prescription->original_filename,
                'ip' => request()->ip()
            ]);

            return FileEncryptionService::downloadDecryptedFile($prescription->file_path);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt prescription file for download:', [
                'prescription_id' => $prescriptionId,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            abort(500, 'Failed to download prescription file.');
        }
    }

    public function getPrescriptionMetadata($prescriptionId)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access.');
        }

        $prescription = Prescription::findOrFail($prescriptionId);

        if (!$prescription->file_path || !$prescription->is_encrypted) {
            return response()->json(['error' => 'File not found'], 404);
        }

        try {
            $metadata = FileEncryptionService::getFileMetadata($prescription->file_path);

            return response()->json([
                'success' => true,
                'metadata' => $metadata,
                'prescription' => [
                    'id' => $prescription->id,
                    'customer_id' => $prescription->customer_id,
                    'order_type' => $prescription->order_type,
                    'status' => $prescription->status,
                    'created_at' => $prescription->created_at
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve metadata'], 500);
        }
    }

    /**
     * Quick duplicate check using file hash (client-side)
     */

    public function quickDuplicateCheck(Request $request)
    {
        try {
            // Validate input - MAKE IT REQUIRED
            $validated = $request->validate([
                'file_hash' => ['required', 'string', 'regex:/^[a-f0-9]{32}$/i'], // Changed to required
            ]);

            $fileHash = $validated['file_hash'];

            // Check authentication
            if (!Auth::guard('customer')->check()) {
                return response()->json([
                    'is_duplicate' => false,
                    'message' => 'Authentication required.',
                    'error' => 'not_authenticated'
                ], 401);
            }

            $customerId = Auth::guard('customer')->id();

            Log::info('Quick duplicate check initiated', [
                'customer_id' => $customerId,
                'file_hash' => $fileHash
            ]);

            // Check for exact duplicate using the hash
            $existingPrescription = Prescription::where('file_hash', $fileHash)
                ->where('customer_id', $customerId)
                ->first();

            if ($existingPrescription) {
                $orderId = $existingPrescription->order->order_id ?? 'N/A';
                $uploadDate = $existingPrescription->created_at->format('M d, Y');

                Log::warning('Duplicate file detected via API', [
                    'customer_id' => $customerId,
                    'file_hash' => $fileHash,
                    'existing_prescription_id' => $existingPrescription->id,
                    'existing_order_id' => $orderId
                ]);

                return response()->json([
                    'is_duplicate' => true,
                    'message' => "This file was previously uploaded on {$uploadDate} (Order #{$orderId}).",
                    'details' => [
                        'prescription_id' => $existingPrescription->id,
                        'order_id' => $orderId,
                        'uploaded_at' => $uploadDate,
                        'uploaded_at_human' => $existingPrescription->created_at->diffForHumans()
                    ]
                ]);
            }

            Log::info('No duplicate found - file is unique', [
                'customer_id' => $customerId,
                'file_hash' => $fileHash
            ]);

            return response()->json([
                'is_duplicate' => false,
                'message' => 'File is unique and ready to upload.'
            ]);
        } catch (\Exception $e) {
            Log::error('Quick duplicate check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'is_duplicate' => false,
                'message' => 'File is ready to upload.',
                'error' => 'check_failed'
            ]);
        }
    }

    /**
     * Full duplicate analysis (called after form submission)
     */
    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'prescription_id' => 'required|exists:prescriptions,id'
        ]);

        $prescription = Prescription::findOrFail($request->prescription_id);

        // Security check
        if ($prescription->customer_id !== Auth::guard('customer')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Decrypt file for analysis
        $decryptedData = \App\Services\FileEncryptionService::decryptFile($prescription->file_path);

        if (!$decryptedData['success']) {
            return response()->json(['error' => 'Failed to process file'], 500);
        }

        // Save to temp file for analysis
        $tempPath = sys_get_temp_dir() . '/' . uniqid('prescription_') . '.tmp';
        file_put_contents($tempPath, $decryptedData['content']);

        try {
            // Run comprehensive check
            $results = \App\Services\DuplicateDetectionService::comprehensiveCheck(
                $tempPath,
                $prescription->customer_id
            );

            // Update prescription with analysis results
            $prescription->update([
                'file_hash' => $results['file_hash'] ?? null,
                'perceptual_hash' => $results['perceptual_hash'] ?? null,
                'extracted_text' => $results['extracted_text'] ?? null,
                'duplicate_check_status' => $results['duplicate_status'] ?? 'verified',
                'duplicate_of_id' => $results['matches'][0]['prescription_id'] ?? null,
                'similarity_score' => $results['highest_similarity'] ?? null,
                'duplicate_checked_at' => now()
            ]);

            return response()->json($results);
        } finally {
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }
}
