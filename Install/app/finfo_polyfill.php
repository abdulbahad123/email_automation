<?php

/**
 * Robust PHP finfo Polyfill
 * Prevents 'Class "finfo" not found' and 'Unable to guess the MIME type'
 * on servers where the PHP fileinfo extension is disabled.
 */

if (!defined('FILEINFO_NONE')) define('FILEINFO_NONE', 0);
if (!defined('FILEINFO_SYMLINK')) define('FILEINFO_SYMLINK', 2);
if (!defined('FILEINFO_MIME')) define('FILEINFO_MIME', 1040);
if (!defined('FILEINFO_MIME_TYPE')) define('FILEINFO_MIME_TYPE', 16);
if (!defined('FILEINFO_MIME_ENCODING')) define('FILEINFO_MIME_ENCODING', 1024);
if (!defined('FILEINFO_DEVICES')) define('FILEINFO_DEVICES', 8);
if (!defined('FILEINFO_CONTINUE')) define('FILEINFO_CONTINUE', 32);
if (!defined('FILEINFO_PRESERVE_ATIME')) define('FILEINFO_PRESERVE_ATIME', 128);
if (!defined('FILEINFO_RAW')) define('FILEINFO_RAW', 256);
if (!defined('FILEINFO_EXTENSION')) define('FILEINFO_EXTENSION', 2097152);

if (!class_exists('finfo', false)) {
    class finfo
    {
        private $flags;

        public function __construct(int $flags = FILEINFO_MIME_TYPE, ?string $magic_file = null)
        {
            $this->flags = $flags;
        }

        public function set_flags(int $flags): bool
        {
            $this->flags = $flags;
            return true;
        }

        public function file(string $filename, int $flags = FILEINFO_NONE, $context = null): string|false
        {
            if (!file_exists($filename) || !is_readable($filename)) {
                return false;
            }

            // 1. Try reading header magic bytes
            $handle = @fopen($filename, 'rb');
            if ($handle) {
                $bytes = fread($handle, 16);
                fclose($handle);

                if (str_starts_with($bytes, "\xFF\xD8\xFF")) return 'image/jpeg';
                if (str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) return 'image/png';
                if (str_starts_with($bytes, "GIF87a") || str_starts_with($bytes, "GIF89a")) return 'image/gif';
                if (str_starts_with($bytes, "RIFF") && substr($bytes, 8, 4) === "WEBP") return 'image/webp';
                if (str_starts_with($bytes, "BM")) return 'image/bmp';
                if (str_starts_with($bytes, "%PDF-")) return 'application/pdf';
                if (strlen($bytes) >= 8 && substr($bytes, 4, 4) === 'ftyp') return 'video/mp4';
            }

            // 2. Fallback to extension mapping
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            return self::mapExtensionToMime($ext);
        }

        public function buffer(string $string, int $flags = FILEINFO_NONE, $context = null): string|false
        {
            if (empty($string)) {
                return 'application/octet-stream';
            }

            if (str_starts_with($string, "\xFF\xD8\xFF")) return 'image/jpeg';
            if (str_starts_with($string, "\x89PNG\r\n\x1a\n")) return 'image/png';
            if (str_starts_with($string, "GIF87a") || str_starts_with($string, "GIF89a")) return 'image/gif';
            if (str_starts_with($string, "RIFF") && substr($string, 8, 4) === "WEBP") return 'image/webp';
            if (str_starts_with($string, "BM")) return 'image/bmp';
            if (str_starts_with($string, "%PDF-")) return 'application/pdf';

            return 'application/octet-stream';
        }

        public static function mapExtensionToMime(string $ext): string
        {
            $map = [
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
                'tar'  => 'application/x-tar',
                'gz'   => 'application/gzip',
                'mp4'  => 'video/mp4',
                'mov'  => 'video/quicktime',
                'avi'  => 'video/x-msvideo',
                'webm' => 'video/webm',
                'm4v'  => 'video/mp4',
                'mkv'  => 'video/x-matroska',
                'wmv'  => 'video/x-ms-wmv',
                '3gp'  => 'video/3gpp',
                '3gpp' => 'video/3gpp',
                'mp3'  => 'audio/mpeg',
                'wav'  => 'audio/wav',
                'ogg'  => 'audio/ogg',
                'txt'  => 'text/plain',
                'csv'  => 'text/csv',
                'json' => 'application/json',
                'xml'  => 'application/xml',
            ];

            return $map[$ext] ?? 'application/octet-stream';
        }
    }
}

if (!function_exists('finfo_open')) {
    function finfo_open(int $flags = FILEINFO_MIME_TYPE, ?string $magic_file = null)
    {
        return new finfo($flags, $magic_file);
    }
}

if (!function_exists('finfo_file')) {
    function finfo_file($finfo, string $filename, int $flags = FILEINFO_NONE, $context = null)
    {
        if ($finfo instanceof finfo) {
            return $finfo->file($filename, $flags, $context);
        }
        $inst = new finfo($flags);
        return $inst->file($filename, $flags, $context);
    }
}

if (!function_exists('finfo_buffer')) {
    function finfo_buffer($finfo, string $string, int $flags = FILEINFO_NONE, $context = null)
    {
        if ($finfo instanceof finfo) {
            return $finfo->buffer($string, $flags, $context);
        }
        $inst = new finfo($flags);
        return $inst->buffer($string, $flags, $context);
    }
}

if (!function_exists('finfo_close')) {
    function finfo_close($finfo): bool
    {
        return true;
    }
}
