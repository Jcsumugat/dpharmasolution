<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Models\Prescription;

class DuplicateDetectionService
{
    /**
     * Calculate MD5 hash of file content
     */
    public static function calculateFileHash($filePath): string
    {
        return md5_file($filePath);
    }

    /**
     * Calculate perceptual hash (pHash) for image similarity
     * FIXED: Use resize() instead of scale() and toArray() for pixel data
     */
    public static function calculatePerceptualHash($filePath): string
    {
        try {
            // Create image manager with GD driver (Intervention Image v3)
            $manager = new ImageManager(new Driver());

            // Load image
            $image = $manager->read($filePath);

            // Resize to exactly 8x8 and convert to grayscale
            // Using resize() with exact dimensions is more reliable than scale()
            $image->resize(8, 8)->greyscale();

            // Get pixel data using a more reliable method
            $pixels = [];

            // Convert to GD resource to access pixel data directly
            $core = $image->core()->native();

            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 8; $x++) {
                    try {
                        // Get color index at position
                        $rgb = imagecolorat($core, $x, $y);

                        // Extract RGB components
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;

                        // For grayscale, all channels should be the same
                        // Use average to be safe
                        $gray = (int)(($r + $g + $b) / 3);
                        $pixels[] = $gray;
                    } catch (\Exception $e) {
                        Log::warning("Failed to get color at ({$x}, {$y}): {$e->getMessage()}");
                        $pixels[] = 128; // Default gray value
                    }
                }
            }

            // Ensure we have exactly 64 pixels
            if (count($pixels) !== 64) {
                Log::error("Expected 64 pixels, got " . count($pixels));
                return '';
            }

            // Calculate average pixel value
            $avg = array_sum($pixels) / count($pixels);

            // Create hash: 1 if pixel > average, 0 if below
            $hash = '';
            foreach ($pixels as $pixel) {
                $hash .= ($pixel > $avg) ? '1' : '0';
            }

            // Convert binary to hex
            $hexHash = base_convert($hash, 2, 16);

            Log::info('Perceptual hash calculated successfully', [
                'hash' => $hexHash,
                'avg_pixel_value' => round($avg, 2)
            ]);

            return $hexHash;
        } catch (\Exception $e) {
            Log::error('Perceptual hash calculation failed: ' . $e->getMessage(), [
                'file_path' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);
            return '';
        }
    }

    /**
     * Extract text from image using OCR
     * FIXED: Explicit Tesseract path for Windows
     */
    public static function extractTextFromImage($filePath): string
    {
        try {
            $ocr = new TesseractOCR($filePath);

            // Explicitly set Tesseract executable path for Windows
            $tesseractPath = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';
            if (file_exists($tesseractPath)) {
                $ocr->executable($tesseractPath);
            }

            $ocr->lang('eng');
            $ocr->psm(6);

            $text = $ocr->run();
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);

            Log::info('OCR extraction completed', [
                'text_length' => strlen($text),
                'preview' => substr($text, 0, 100)
            ]);

            return $text;
        } catch (\Exception $e) {
            Log::error('OCR extraction failed: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Calculate similarity between two perceptual hashes (Hamming distance)
     * Returns similarity percentage (0-100)
     */
    public static function calculateHashSimilarity(string $hash1, string $hash2): float
    {
        if (empty($hash1) || empty($hash2)) {
            return 0;
        }

        $bin1 = str_pad(base_convert($hash1, 16, 2), 64, '0', STR_PAD_LEFT);
        $bin2 = str_pad(base_convert($hash2, 16, 2), 64, '0', STR_PAD_LEFT);

        $distance = 0;
        for ($i = 0; $i < 64; $i++) {
            if ($bin1[$i] !== $bin2[$i]) {
                $distance++;
            }
        }

        return (1 - ($distance / 64)) * 100;
    }

    /**
     * Calculate text similarity using Levenshtein distance
     * Returns similarity percentage (0-100)
     */
    public static function calculateTextSimilarity(string $text1, string $text2): float
    {
        if (empty($text1) || empty($text2)) {
            return 0;
        }

        $text1 = strtolower(trim($text1));
        $text2 = strtolower(trim($text2));

        $maxLen = max(strlen($text1), strlen($text2));
        if ($maxLen === 0) {
            return 100;
        }

        $distance = levenshtein($text1, $text2);
        return (1 - ($distance / $maxLen)) * 100;
    }

    /**
     * Check if file hash exists in database (exact duplicate)
     */
    public static function checkExactDuplicate(string $fileHash, int $customerId): ?array
    {
        $duplicate = Prescription::where('file_hash', $fileHash)
            ->where('customer_id', $customerId)
            ->first();

        if ($duplicate) {
            return [
                'is_duplicate' => true,
                'type' => 'exact',
                'prescription_id' => $duplicate->id,
                'order_id' => $duplicate->order->order_id ?? 'N/A',
                'uploaded_at' => $duplicate->created_at->format('M d, Y'),
                'similarity' => 100
            ];
        }

        return null;
    }

    /**
     * Check for similar prescriptions using perceptual hash
     */
    public static function checkSimilarDuplicates(string $perceptualHash, int $customerId, float $threshold = 90.0, ?int $excludePrescriptionId = null): array
    {
        $query = Prescription::where('customer_id', $customerId)
            ->whereNotNull('perceptual_hash');

        if ($excludePrescriptionId) {
            $query->where('id', '!=', $excludePrescriptionId);
        }

        $prescriptions = $query->get();
        $matches = [];

        foreach ($prescriptions as $prescription) {
            $similarity = self::calculateHashSimilarity($perceptualHash, $prescription->perceptual_hash);

            if ($similarity >= $threshold) {
                $matches[] = [
                    'is_duplicate' => true,
                    'type' => 'similar',
                    'prescription_id' => $prescription->id,
                    'order_id' => $prescription->order->order_id ?? 'N/A',
                    'uploaded_at' => $prescription->created_at->format('M d, Y'),
                    'similarity' => round($similarity, 2)
                ];
            }
        }

        usort($matches, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $matches;
    }

    /**
     * Check for text-based duplicates using OCR
     */
    public static function checkTextDuplicates(string $extractedText, int $customerId, float $threshold = 85.0, ?int $excludePrescriptionId = null): array
    {
        if (empty($extractedText) || strlen($extractedText) < 20) {
            return [];
        }

        $query = Prescription::where('customer_id', $customerId)
            ->whereNotNull('extracted_text');

        if ($excludePrescriptionId) {
            $query->where('id', '!=', $excludePrescriptionId);
        }

        $prescriptions = $query->get();
        $matches = [];

        foreach ($prescriptions as $prescription) {
            $similarity = self::calculateTextSimilarity($extractedText, $prescription->extracted_text);

            if ($similarity >= $threshold) {
                $matches[] = [
                    'is_duplicate' => true,
                    'type' => 'text_match',
                    'prescription_id' => $prescription->id,
                    'order_id' => $prescription->order->order_id ?? 'N/A',
                    'uploaded_at' => $prescription->created_at->format('M d, Y'),
                    'similarity' => round($similarity, 2)
                ];
            }
        }

        usort($matches, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $matches;
    }

    /**
     * Comprehensive duplicate check (all methods)
     * IMPROVED: Skip exact hash check when excludePrescriptionId is provided
     * since exact duplicates are already handled during upload
     */
    public static function comprehensiveCheck(string $filePath, int $customerId, ?int $excludePrescriptionId = null): array
    {
        $results = [
            'has_duplicate' => false,
            'checks_performed' => [],
            'matches' => [],
            'highest_similarity' => 0,
            'duplicate_status' => 'verified'
        ];

        try {
            // Calculate file hash for storage, but skip duplicate check if this is post-upload analysis
            $fileHash = self::calculateFileHash($filePath);
            $results['file_hash'] = $fileHash;

            // Only check for exact duplicates if this is a pre-upload check
            // (when excludePrescriptionId is null, meaning no prescription created yet)
            if ($excludePrescriptionId === null) {
                $results['checks_performed'][] = 'exact_hash';

                $exactMatch = Prescription::where('file_hash', $fileHash)
                    ->where('customer_id', $customerId)
                    ->first();

                if ($exactMatch) {
                    $results['has_duplicate'] = true;
                    $results['matches'][] = [
                        'is_duplicate' => true,
                        'type' => 'exact',
                        'prescription_id' => $exactMatch->id,
                        'order_id' => $exactMatch->order->order_id ?? 'N/A',
                        'uploaded_at' => $exactMatch->created_at->format('M d, Y'),
                        'similarity' => 100
                    ];
                    $results['highest_similarity'] = 100;
                    $results['match_type'] = 'exact';
                    $results['duplicate_status'] = 'duplicate';

                    Log::info('Exact duplicate found in pre-upload check', [
                        'duplicate_prescription_id' => $exactMatch->id,
                        'file_hash' => $fileHash
                    ]);

                    return $results;
                }
            } else {
                // Post-upload analysis - check if THIS prescription is an exact duplicate of others
                // This helps identify user-confirmed duplicates for admin review
                $exactMatch = Prescription::where('file_hash', $fileHash)
                    ->where('customer_id', $customerId)
                    ->where('id', '!=', $excludePrescriptionId)
                    ->first();

                if ($exactMatch) {
                    $results['has_duplicate'] = true;
                    $results['matches'][] = [
                        'is_duplicate' => true,
                        'type' => 'exact',
                        'prescription_id' => $exactMatch->id,
                        'order_id' => $exactMatch->order->order_id ?? 'N/A',
                        'uploaded_at' => $exactMatch->created_at->format('M d, Y'),
                        'similarity' => 100
                    ];
                    $results['highest_similarity'] = 100;
                    $results['match_type'] = 'exact';
                    $results['duplicate_status'] = 'duplicate';
                    $results['checks_performed'][] = 'exact_hash_post_upload';

                    Log::warning('User confirmed duplicate upload detected in post-analysis', [
                        'new_prescription_id' => $excludePrescriptionId,
                        'existing_prescription_id' => $exactMatch->id,
                        'file_hash' => $fileHash
                    ]);
                }
            }

            // Continue with image-based checks for perceptual and text similarity
            $mimeType = mime_content_type($filePath);
            if (strpos($mimeType, 'image/') === 0) {
                // Perceptual hash check
                $perceptualHash = self::calculatePerceptualHash($filePath);
                $results['perceptual_hash'] = $perceptualHash;
                $results['checks_performed'][] = 'perceptual_hash';

                if (!empty($perceptualHash)) {
                    $similarMatches = self::checkSimilarDuplicates(
                        $perceptualHash,
                        $customerId,
                        90.0,
                        $excludePrescriptionId
                    );

                    if (!empty($similarMatches)) {
                        $results['has_duplicate'] = true;
                        $results['matches'] = array_merge($results['matches'], $similarMatches);

                        // Only update highest similarity if no exact match was found
                        if ($results['highest_similarity'] < 100) {
                            $results['highest_similarity'] = $similarMatches[0]['similarity'];
                            $results['match_type'] = 'similar';
                        }
                    }
                }

                // OCR text check
                $extractedText = self::extractTextFromImage($filePath);
                $results['extracted_text'] = $extractedText;
                $results['checks_performed'][] = 'ocr';

                if (!empty($extractedText) && strlen($extractedText) >= 20) {
                    $textMatches = self::checkTextDuplicates(
                        $extractedText,
                        $customerId,
                        85.0,
                        $excludePrescriptionId
                    );

                    if (!empty($textMatches)) {
                        $results['has_duplicate'] = true;
                        $results['matches'] = array_merge($results['matches'], $textMatches);

                        // Only update if this is higher than current similarity (but not exact)
                        if ($textMatches[0]['similarity'] > $results['highest_similarity'] && $results['highest_similarity'] < 100) {
                            $results['highest_similarity'] = $textMatches[0]['similarity'];
                            $results['match_type'] = 'text_match';
                        }
                    }
                }
            }

            // Determine duplicate status based on highest similarity
            if ($results['has_duplicate']) {
                if ($results['highest_similarity'] == 100) {
                    $results['duplicate_status'] = 'duplicate';
                } elseif ($results['highest_similarity'] >= 90) {
                    $results['duplicate_status'] = 'suspicious';
                } else {
                    $results['duplicate_status'] = 'verified';
                }
            }

            Log::info('Comprehensive check completed', [
                'customer_id' => $customerId,
                'exclude_prescription_id' => $excludePrescriptionId,
                'is_post_upload_analysis' => $excludePrescriptionId !== null,
                'has_duplicate' => $results['has_duplicate'],
                'duplicate_status' => $results['duplicate_status'],
                'highest_similarity' => $results['highest_similarity'],
                'match_count' => count($results['matches']),
                'checks_performed' => $results['checks_performed']
            ]);
        } catch (\Exception $e) {
            Log::error('Comprehensive duplicate check failed: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'exclude_prescription_id' => $excludePrescriptionId,
                'file_path' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);
            $results['error'] = $e->getMessage();
        }

        return $results;
    }
    
    /**
     * Quick hash-only check (for API endpoint)
     */
    public static function quickHashCheck(string $fileHash, int $customerId): bool
    {
        return Prescription::where('file_hash', $fileHash)
            ->where('customer_id', $customerId)
            ->exists();
    }
}
