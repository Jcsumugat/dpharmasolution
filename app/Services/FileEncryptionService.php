<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class FileEncryptionService
{
    /**
     * Encrypt and store an uploaded file
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $filename
     * @return array Contains encrypted file path and metadata
     */
    public static function encryptAndStore(UploadedFile $file, string $directory = 'prescriptions/encrypted', string $filename = null): array
    {
        try {
            // Generate filename if not provided
            if (!$filename) {
                $filename = time() . '_' . \Illuminate\Support\Str::random(6) . '.enc';
            } else {
                $filename = pathinfo($filename, PATHINFO_FILENAME) . '.enc';
            }

            // Read file content
            $fileContent = file_get_contents($file->getRealPath());

            if ($fileContent === false) {
                throw new \Exception('Failed to read file content');
            }

            // Create metadata for decryption
            $metadata = [
                'original_name' => $file->getClientOriginalName(),
                'original_extension' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'encrypted_at' => now()->toISOString(),
            ];

            // Combine metadata and file content
            $dataToEncrypt = [
                'metadata' => $metadata,
                'content' => base64_encode($fileContent)
            ];

            // Encrypt the data
            $encryptedData = Crypt::encryptString(json_encode($dataToEncrypt));

            // Store encrypted file
            $encryptedPath = $directory . '/' . $filename;
            Storage::put($encryptedPath, $encryptedData);

            Log::info('File encrypted and stored successfully', [
                'original_name' => $metadata['original_name'],
                'encrypted_path' => $encryptedPath,
                'file_size' => $metadata['size']
            ]);

            return [
                'encrypted_path' => $encryptedPath,
                'metadata' => $metadata,
                'success' => true
            ];

        } catch (\Exception $e) {
            Log::error('File encryption failed', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName()
            ]);

            throw new \Exception('File encryption failed: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt and retrieve file content
     *
     * @param string $encryptedPath
     * @return array Contains file content and metadata
     */
    public static function decryptFile(string $encryptedPath): array
    {
        try {
            // Check if file exists
            if (!Storage::exists($encryptedPath)) {
                throw new \Exception('Encrypted file not found');
            }

            // Read encrypted data
            $encryptedData = Storage::get($encryptedPath);

            if ($encryptedData === false) {
                throw new \Exception('Failed to read encrypted file');
            }

            // Decrypt the data
            $decryptedJson = Crypt::decryptString($encryptedData);
            $decryptedData = json_decode($decryptedJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to decode decrypted data');
            }

            // Decode file content
            $fileContent = base64_decode($decryptedData['content']);

            if ($fileContent === false) {
                throw new \Exception('Failed to decode file content');
            }

            Log::info('File decrypted successfully', [
                'encrypted_path' => $encryptedPath,
                'original_name' => $decryptedData['metadata']['original_name']
            ]);

            return [
                'content' => $fileContent,
                'metadata' => $decryptedData['metadata'],
                'success' => true
            ];

        } catch (\Exception $e) {
            Log::error('File decryption failed', [
                'error' => $e->getMessage(),
                'encrypted_path' => $encryptedPath
            ]);

            throw new \Exception('File decryption failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a temporary decrypted file for viewing/downloading
     *
     * @param string $encryptedPath
     * @return string Temporary file path
     */
    public static function createTempDecryptedFile(string $encryptedPath): string
    {
        $decryptedData = self::decryptFile($encryptedPath);

        // Create temporary file
        $tempPath = 'temp/decrypted_' . time() . '_' . \Illuminate\Support\Str::random(6) . '.' . $decryptedData['metadata']['original_extension'];

        // Store temporary file
        Storage::disk('private')->put($tempPath, $decryptedData['content']);

        // Schedule cleanup after 1 hour
        self::scheduleCleanup($tempPath);

        return $tempPath;
    }

    /**
     * Generate secure download response for encrypted file
     *
     * @param string $encryptedPath
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public static function downloadDecryptedFile(string $encryptedPath)
    {
        $decryptedData = self::decryptFile($encryptedPath);

        return response()->streamDownload(function () use ($decryptedData) {
            echo $decryptedData['content'];
        }, $decryptedData['metadata']['original_name'], [
            'Content-Type' => $decryptedData['metadata']['mime_type'],
            'Content-Length' => strlen($decryptedData['content'])
        ]);
    }

    /**
     * Display encrypted image in browser
     *
     * @param string $encryptedPath
     * @return \Illuminate\Http\Response
     */
    public static function displayDecryptedImage(string $encryptedPath)
    {
        $decryptedData = self::decryptFile($encryptedPath);

        // Verify it's an image
        if (!str_starts_with($decryptedData['metadata']['mime_type'], 'image/')) {
            throw new \Exception('File is not an image');
        }

        return response($decryptedData['content'])
            ->header('Content-Type', $decryptedData['metadata']['mime_type'])
            ->header('Content-Length', strlen($decryptedData['content']))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Delete encrypted file
     *
     * @param string $encryptedPath
     * @return bool
     */
    public static function deleteEncryptedFile(string $encryptedPath): bool
    {
        try {
            if (Storage::disk('private')->exists($encryptedPath)) {
                Storage::disk('private')->delete($encryptedPath);

                Log::info('Encrypted file deleted', [
                    'encrypted_path' => $encryptedPath
                ]);

                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete encrypted file', [
                'error' => $e->getMessage(),
                'encrypted_path' => $encryptedPath
            ]);
            return false;
        }
    }

    /**
     * Schedule cleanup of temporary files
     *
     * @param string $tempPath
     */
    private static function scheduleCleanup(string $tempPath): void
    {
        // You can implement this with Laravel's task scheduling
        // For now, we'll just log it
        Log::info('Temporary file created, schedule cleanup', [
            'temp_path' => $tempPath,
            'cleanup_time' => now()->addHour()->toISOString()
        ]);
    }

    /**
     * Clean up old temporary files (call this in a scheduled job)
     */
    public static function cleanupTempFiles(): void
    {
        try {
            $tempFiles = Storage::disk('private')->files('temp');
            $oneHourAgo = now()->subHour();

            foreach ($tempFiles as $file) {
                $lastModified = Storage::disk('private')->lastModified($file);

                if ($lastModified < $oneHourAgo->timestamp) {
                    Storage::disk('private')->delete($file);
                    Log::info('Cleaned up temporary file', ['file' => $file]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Cleanup failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get file metadata without decrypting content
     *
     * @param string $encryptedPath
     * @return array
     */
    public static function getFileMetadata(string $encryptedPath): array
    {
        if (!Storage::disk('private')->exists($encryptedPath)) {
            throw new \Exception('Encrypted file not found');
        }

        $encryptedData = Storage::disk('private')->get($encryptedPath);
        $decryptedJson = Crypt::decryptString($encryptedData);
        $decryptedData = json_decode($decryptedJson, true);

        return $decryptedData['metadata'] ?? [];
    }
}
