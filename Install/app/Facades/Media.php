<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;

class Media extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor()
    { 
        return 'media';
    }

    /**
     * Return an array of available storage disks with labels.
     */
    protected static function disks()
    { 
        return [
            'aws'      => __("AmazonS3"),
            'contabo'  => __("Contabo S3"),
            'local'    => __("Public"),
        ];
    }

    /**
     * Get the URL for a given file path.
     * - If $path is empty, returns an empty string.
     * - If $path is already a valid URL, returns it directly.
     * - If the default storage disk is S3 (aws or s3), returns the S3 URL.
     * - Otherwise, assumes local/public storage.
     */
    protected static function url($path = '')
    {
        // 0. Empty
        if (empty($path)) {
            return '';
        }

        /**
         * 1. AI base64 (array)
         * [
         *   'b64_json' => '...',
         *   'mimeType' => 'image/png'
         * ]
         */
        if (is_array($path) && isset($path['b64_json'], $path['mimeType'])) {
            return 'data:' . $path['mimeType'] . ';base64,' . $path['b64_json'];
        }

        /**
         * 1b. AI base64 (object)
         */
        if (is_object($path) && isset($path->b64_json, $path->mimeType)) {
            return 'data:' . $path->mimeType . ';base64,' . $path->b64_json;
        }

        /**
         * 2. Nếu đã là data URI → trả luôn
         */
        if (is_string($path) && str_starts_with($path, 'data:')) {
            return $path;
        }

        /**
         * 3. Nếu là URL hợp lệ → trả luôn
         */
        if (is_string($path) && filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        /**
         * 4. Local / Storage path
         */
        if (!is_string($path)) {
            return '';
        }

        $disk = get_option("file_storage_server", "local");

        if (in_array($disk, ['aws', 'contabo'])) {
            try {
                return Storage::disk($disk)->url($path);
            } catch (\Throwable $e) {
                return url(Storage::url('app/public/' . ltrim($path, '/')));
            }
        }

        return url('storage/' . ltrim($path, '/'));
    }

    /**
     * Extract the relative file path from a full URL.
     */
    protected static function getPathFromUrl($path = '')
    { 
        $baseUrl = url(Storage::url('app/public/'));
        return str_replace($baseUrl . "/", "", $path);
    }

    /**
     * Get the full filesystem path for the given file.
     */
    protected static function path($path, $storageType = "public")
    { 
        return Storage::disk($storageType)->path($path);
    }

    /**
     * Determine if the given path or URL points to an image.
     * For local files, uses getimagesize; for URLs, delegates to isImgUrl().
     */
    protected static function isImg($path)
    {
        // 0. Nếu là object kiểu base64 (AI)
        if (is_object($path) && isset($path->b64_json, $path->mimeType)) {
            return str_starts_with($path->mimeType, 'image/');
        }
    
        // 0b. Nếu là array kiểu base64 (AI)
        if (is_array($path) && isset($path['b64_json'], $path['mimeType'])) {
            return str_starts_with($path['mimeType'], 'image/');
        }
    
        // 1. Nếu là local file (string)
        if (is_string($path) && file_exists($path)) {
            $imgInfo = @getimagesize($path);
            return $imgInfo && isset($imgInfo[2]) && in_array($imgInfo[2], [
                IMAGETYPE_GIF,
                IMAGETYPE_JPEG,
                IMAGETYPE_PNG,
                IMAGETYPE_WBMP,
                IMAGETYPE_WEBP
            ]);
        }
    
        // 2. Convert sang URL nếu không phải URL
        if (is_string($path) && !filter_var($path, FILTER_VALIDATE_URL)) {
            $path = self::url($path);
        }
    
        // 3. Nếu là URL hợp lệ → kiểm tra bằng isImgUrl()
        if (is_string($path) && filter_var($path, FILTER_VALIDATE_URL)) {
            return self::isImgUrl($path);
        }
    
        return false;
    }
    
    /**
     * Check if the given URL points to a valid image.
     */
    protected static function isImgUrl($url)
    {
        if (!$url || !is_string($url)) {
            return false;
        }

        // 1. Lấy header (suppress warning)
        $headers = @get_headers($url, 1);
        if (!$headers || !is_array($headers)) {
            return false;
        }

        // 2. Theo dõi redirect (nếu có nhiều redirect)
        while (isset($headers['Location']) || isset($headers['location'])) {
            $redirect = $headers['Location'] ?? $headers['location'];
            $redirectUrl = is_array($redirect) ? end($redirect) : $redirect;

            $headers = @get_headers($redirectUrl, 1);
            if (!$headers) break;
        }

        // 3. Chuẩn hóa key về lowercase
        $headers = array_change_key_case($headers, CASE_LOWER);

        // 4. Lấy content-type
        $contentType = '';
        if (isset($headers['content-type'])) {
            $contentType = $headers['content-type'];
            $contentType = is_array($contentType) ? $contentType[0] : $contentType;
            $contentType = strtolower(trim($contentType));
        }

        // 5. Các MIME hợp lệ
        $validImgTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp'
        ];

        // 5.1 Nếu content-type dạng "image/jpeg; charset=binary"
        foreach ($validImgTypes as $t) {
            if (str_starts_with($contentType, $t)) {
                return true;
            }
        }

        // 6. Một số CDN trả về 'binary/octet-stream' nhưng file thật là ảnh
        if ($contentType === 'binary/octet-stream') {
            // fallback kiểm tra đuôi file
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'])) {
                return true;
            }
        }

        // 7. Fallback: kiểm tra đuôi file
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'])) {
            return true;
        }

        return false;
    }

    protected static function isDocument($path)
    {
        // 0. Nếu $path là array (AI trả base64 ...) → luôn không phải document
        if (is_array($path)) {
            return false;
        }

        // 1. Nếu không phải URL → convert sang URL (nếu là đường dẫn local)
        if (is_string($path) && !filter_var($path, FILTER_VALIDATE_URL)) {
            $path = self::url($path);
        }

        // 2. Lấy extension từ URL path
        $parsedPath = parse_url($path, PHP_URL_PATH);
        if (!$parsedPath) {
            return false;
        }

        $ext = strtolower(pathinfo($parsedPath, PATHINFO_EXTENSION));

        if (!$ext) {
            return false; // File không có extension
        }

        // 3. Các loại tài liệu hợp lệ
        $docExtensions = [
            'pdf', 'doc', 'docx',
            'xls', 'xlsx',
            'txt', 'csv'
        ];

        return in_array($ext, $docExtensions);
    }

    protected static function isOgg($path)
    {
        // 0. Nếu là array → không phải audio
        if (is_array($path)) {
            return false;
        }

        // 1. Nếu không phải URL → chuyển sang URL
        if (is_string($path) && !filter_var($path, FILTER_VALIDATE_URL)) {
            $path = self::url($path);
        }

        // 2. Nếu vẫn không phải URL → return false
        if (!filter_var($path, FILTER_VALIDATE_URL)) {
            return false;
        }

        // 3. Lấy extension từ URL path
        $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));

        return $ext === 'ogg';
    }

    protected static function isAudio($path)
    {
        // 0. Nếu $path là array (ví dụ AI base64) → không phải audio
        if (is_array($path)) {
            return false;
        }

        $audioExtensions = ['mp3', 'ogg', 'wav', 'flac', 'aac', 'm4a'];

        // 1. Local file
        if (is_string($path) && file_exists($path)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            return in_array($ext, $audioExtensions);
        }

        // 2. Convert sang URL nếu cần
        if (is_string($path) && !filter_var($path, FILTER_VALIDATE_URL)) {
            $path = self::url($path);
        }

        if (!filter_var($path, FILTER_VALIDATE_URL)) {
            return false;
        }

        // 3. Kiểm tra MIME (HEAD request)
        try {
            $headers = @get_headers($path, 1);
            if ($headers && is_array($headers)) {

                // Handle redirect
                if (isset($headers['Location']) || isset($headers['location'])) {
                    $loc = $headers['Location'] ?? $headers['location'];
                    $redirect = is_array($loc) ? end($loc) : $loc;
                    $headers = @get_headers($redirect, 1);
                }

                $headers = array_change_key_case($headers, CASE_LOWER);

                if (isset($headers['content-type'])) {
                    $ct = $headers['content-type'];
                    $ct = is_array($ct) ? $ct[0] : $ct;
                    $ct = strtolower(trim($ct));

                    // Nếu server trả đúng audio/*
                    if (str_starts_with($ct, 'audio/')) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {}

        // 4. Fallback check extension
        $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($ext, $audioExtensions);
    }

    /**
     * Check if the given path or URL points to a video.
     */
    protected static function isVideo($path)
    {
        // 0. Nếu $path là array (AI b64_json) → chắc chắn không phải video
        if (is_array($path)) {
            return false;
        }

        // Danh sách video extensions
        $videoExt = ['mp4', 'mov', 'avi', 'mkv', 'webm'];

        // 1. Nếu là file local thực sự
        if (is_string($path) && file_exists($path)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            return in_array($ext, $videoExt);
        }

        // 2. Nếu không phải URL, convert sang URL
        if (is_string($path) && !filter_var($path, FILTER_VALIDATE_URL)) {
            $path = self::url($path);
        }

        // 3. Nếu không phải URL hợp lệ → return false
        if (!filter_var($path, FILTER_VALIDATE_URL)) {
            return false;
        }

        // 4. Kiểm tra headers (có xử lý redirect)
        try {
            $context = stream_context_create([
                "ssl" => [
                    "verify_peer"      => false,
                    "verify_peer_name" => false,
                ]
            ]);

            $headers = @get_headers($path, 1, $context);
            if (!$headers || !is_array($headers)) {
                return false;
            }

            // Follow redirects
            while (isset($headers['Location']) || isset($headers['location'])) {
                $redirect = $headers['Location'] ?? $headers['location'];
                $nextUrl = is_array($redirect) ? end($redirect) : $redirect;

                $headers = @get_headers($nextUrl, 1, $context);
                if (!$headers) break;
            }

            $headers = array_change_key_case($headers, CASE_LOWER);

            // Lấy content-type
            $contentType = '';
            if (isset($headers['content-type'])) {
                $contentType = $headers['content-type'];
                $contentType = is_array($contentType) ? $contentType[0] : $contentType;
                $contentType = strtolower(trim($contentType));
            }

            // 5. Nếu server trả đúng video/*
            if (str_starts_with($contentType, 'video/')) {
                return true;
            }

            // 6. Một số server trả binary/octet-stream nhưng file thật là video
            if ($contentType === 'binary/octet-stream') {
                // fallback file extension
                $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));
                if (in_array($ext, $videoExt)) {
                    return true;
                }
            }

            // 7. Fallback: check extension từ URL
            $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (in_array($ext, $videoExt)) {
                return true;
            }

            return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Detect the file type based on its extension.
     */
    protected static function detectFileType($ext)
    {
        $ext = strtolower($ext);
        $mapping = [
            'jpg'   => 'image',
            'jpeg'  => 'image',
            'png'   => 'image',
            'gif'   => 'image',
            'svg'   => 'image',
            'webp'  => 'image',
            'mp4'   => 'video',
            'mov'   => 'video',
            'm4v'   => 'video',
            'avi'   => 'video',
            'webm'  => 'video',
            'mkv'   => 'video',
            'wmv'   => 'video',
            '3gp'   => 'video',
            '3gpp'  => 'video',
            'csv'   => 'csv',
            'pdf'   => 'pdf',
            'xlsx'  => 'doc',
            'xls'   => 'doc',
            'docx'  => 'doc',
            'doc'   => 'doc',
            'txt'   => 'doc',
            'mp3'   => 'audio',
            'ogg'   => 'audio',
        ];

        return $mapping[$ext] ?? 'other';
    }

    /**
     * Get file icon and color based on the detected file type.
     */
    protected static function detectFileIcon($type)
    {
        switch ($type) {
            case 'image':
                return ["color" => "gray",    "icon" => "fa-light fa-image"];
            case 'video':
                return ["color" => "success", "icon" => "fa-light fa-film"];
            case 'audio':
                return ["color" => "primary", "icon" => "fa-light fa-volume"];
            case 'csv':
                return ["color" => "info",    "icon" => "fa-light fa-file-csv"];
            case 'pdf':
                return ["color" => "cyan",    "icon" => "fa-light fa-file-pdf"];
            case 'doc':
                return ["color" => "success", "icon" => "fa-light fa-file-contract"];
            case 'zip':
                return ["color" => "primary", "icon" => "fa-light fa-file-zipper"];
            default:
                return ["color" => "primary", "icon" => "fa-light fa-file-circle-question"];
        }
    }
}
