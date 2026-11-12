<?php

namespace App\Services;

use Symfony\Component\Mime\MimeTypeGuesserInterface;

class FallbackMimeTypeGuesser implements MimeTypeGuesserInterface
{
    public function isGuesserSupported(): bool
    {
        return true;
    }

    public function guessMimeType(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        // 1. Try finfo if available
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $type = @finfo_file($finfo, $path);
                @finfo_close($finfo);
                if (!empty($type)) {
                    return $type;
                }
            }
        }

        // 2. Try mime_content_type if available
        if (function_exists('mime_content_type')) {
            try {
                $type = @mime_content_type($path);
                if (!empty($type)) {
                    return $type;
                }
            } catch (\Throwable $e) {}
        }

        // 3. Try reading image magic header bytes
        $handle = @fopen($path, 'rb');
        if ($handle) {
            $bytes = fread($handle, 16);
            fclose($handle);

            if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
                return 'image/jpeg';
            }
            if (str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
                return 'image/png';
            }
            if (str_starts_with($bytes, "GIF87a") || str_starts_with($bytes, "GIF89a")) {
                return 'image/gif';
            }
            if (str_starts_with($bytes, "RIFF") && substr($bytes, 8, 4) === "WEBP") {
                return 'image/webp';
            }
            if (str_starts_with($bytes, "BM")) {
                return 'image/bmp';
            }
            if (str_starts_with($bytes, "%PDF-")) {
                return 'application/pdf';
            }
        }

        // 4. Fallback by file extension
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $extensionMap = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'bmp'  => 'image/bmp',
            'ico'  => 'image/x-icon',
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip'  => 'application/zip',
            'rar'  => 'application/x-rar-compressed',
            'mp4'  => 'video/mp4',
            'mov'  => 'video/quicktime',
            'avi'  => 'video/x-msvideo',
            'webm' => 'video/webm',
            'mp3'  => 'audio/mpeg',
            'wav'  => 'audio/wav',
            'ogg'  => 'audio/ogg',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',
        ];

        return $extensionMap[$ext] ?? 'application/octet-stream';
    }
}
