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
            Log::error('Customer object is null or missing ID', [
                'customer' => $customer,
                'customer_id' => $customer->id ?? 'null'
            ]);
            return redirect()->route('login.form')->with('error', 'Authentication error. Please log in again.');
        }

        $customerId = $customer->id;

        $validated = $request->validate([
            'mobile_number' => ['required', 'regex:/^09\d{9}$/'],
            'prescription_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:500'],
            'order_type' => ['required', 'in:prescription,online_order'],
        ]);

        try {
            $file = $request->file('prescription_file');
            $filename = time() . '_' . Str::random(6) . '_customer_' . $customerId;

            $encryptionResult = FileEncryptionService::encryptAndStore(
                $file,
                'prescriptions/encrypted',
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
                'user_id' => $customerId,
                'customer_id' => $customerId,
            ]);

            Log::info('Prescription created successfully with encryption:', [
                'prescription_id' => $prescription->id,
                'customer_id' => $customerId,
                'order_id' => $orderId,
                'order_type' => $validated['order_type'],
                'encrypted_path' => $encryptionResult['encrypted_path'],
                'original_filename' => $encryptionResult['metadata']['original_name']
            ]);

            NotificationService::notifyNewOrder($prescription);

            Order::create([
                'prescription_id' => $prescription->id,
                'order_id' => $orderId,
                'status' => 'Pending',
            ]);

            $successMessage = $validated['order_type'] === 'prescription'
                ? 'Thank you! Your prescription has been received and securely encrypted.'
                : 'Thank you! Your online order has been received and securely processed.';

            return redirect()->route('prescription.upload.form')
                ->with('success', $successMessage)
                ->with('qr_link', $qrUrl)
                ->with('qr_image', asset('storage/' . $qrPath));

        } catch (\Exception $e) {
            Log::error('Upload failed:', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'order_type' => $validated['order_type'] ?? 'unknown',
                'file_name' => $file->getClientOriginalName() ?? 'unknown'
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['prescription_file' => 'Failed to process file: ' . $e->getMessage()]);
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
}
