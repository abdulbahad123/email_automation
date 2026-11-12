<?php

namespace Modules\AppFiles\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\AppFiles\Models\Files;
use Intervention\Image\ImageManager;
use Illuminate\Http\File;
use Google_Client;
use Google_Service_Drive;
use Media;

class UploadFileService
{
    protected $client;
    protected $service;
    protected $disk;
    protected $allowedFileTypes = ['jpeg', 'gif', 'png', 'jpg', 'webp', 'mp4', 'mov', 'm4v', 'webm', 'avi', 'mkv', 'wmv', '3gp', '3gpp', 'csv', 'pdf', 'mp3', 'json'];
    protected $maxFileSize = 2048;

    // Map the storage types to the corresponding disks
    protected $disks = [
        'aws' => 's3',
        'contabo' => 'contabo',
        'local' => 'public',
    ];

    public function __construct()
    {
        $storageType = get_option('file_storage_server', 'local');
        $this->disk = $this->disks[$storageType] ?? 'public';
        $maxMb = (int)\Access::permission('appfiles.max_size');
        $this->maxFileSize = $maxMb > 0 ? $maxMb : 50;

        $rawTypes = get_option("file_allowed_file_types", "jpeg,gif,png,jpg,webp,mp4,mov,m4v,webm,avi,mkv,wmv,3gp,csv,pdf,mp3,json");
        if (empty($rawTypes) || trim($rawTypes) === '') {
            $rawTypes = "jpeg,gif,png,jpg,webp,mp4,mov,m4v,webm,avi,mkv,wmv,3gp,csv,pdf,mp3,json";
        }
        $this->allowedFileTypes = array_filter(array_map('trim', explode(',', $rawTypes)));
        if (empty($this->allowedFileTypes)) {
            $this->allowedFileTypes = ['jpeg', 'gif', 'png', 'jpg', 'webp', 'mp4', 'mov', 'm4v', 'webm', 'avi', 'mkv', 'wmv', '3gp', '3gpp', 'csv', 'pdf', 'mp3', 'json'];
        }

        switch ($storageType) {
            case 'aws':
                \Config::set('filesystems.disks.s3', [
                    'driver' => 's3',
                    'key'    => get_option('file_aws3_access_key') ?? '',
                    'secret' => get_option('file_awss3_secret_access_key') ?? '',
                    'region' => get_option('file_aws3_region') ?? '',
                    'bucket' => get_option('file_aws_bucket_name') ?? '',
                    'url'    => null,
                    'endpoint' => null,
                    'use_path_style_endpoint' => true,
                ]);
                break;
            
            case 'contabo':
                \Config::set('filesystems.disks.contabo', [
                    'driver' => 's3',
                    'key'    => get_option('file_contabos3_access_key'),
                    'secret' => get_option('file_contabo_secret_access_key'),
                    'region' => get_option('file_contabos3_region', 'us-east-1'),
                    'bucket' => get_option('file_contabos3_bucket_name'),
                    'endpoint' => get_option('file_contabos3_endpoint'),
                    'use_path_style_endpoint' => true,
                    'visibility' => 'public'
                ]);
                break;
        }
    }

    public function getFileStorageStats($team_id = null)
    {
        if(!$team_id){
            $team_id = request()->team_id ?? 0;
        }
        
        $totalFiles = Files::where('team_id', $team_id)
            ->where('is_folder', 0)
            ->count();

        $usedBytes = (int) Files::where('team_id', $team_id)
            ->where('is_folder', 0)
            ->sum('size');

        $maxStorageMb = (int) \Access::permission('appfiles.max_storage');
        $maxBytes = $maxStorageMb > 0 ? ($maxStorageMb * 1024 * 1024) : 0;

        $formatSize = function ($bytes) {
            if ($bytes >= 1073741824) {
                return round($bytes / 1073741824, 2) . 'GB';
            }
            if ($bytes >= 1048576) {
                return round($bytes / 1048576, 2) . 'MB';
            }
            if ($bytes >= 1024) {
                return round($bytes / 1024, 2) . 'KB';
            }
            return $bytes . 'B';
        };

        return [
            'total_files' => $totalFiles,
            'used_bytes' => $usedBytes,
            'used_friendly' => $formatSize($usedBytes),
            'max_bytes' => $maxBytes,
            'max_friendly' => $maxBytes > 0 ? $formatSize($maxBytes) : __('Unlimited'),
        ];
    }

    public function checkStorageLimit(int $newFileSizeBytes, ?int $team_id = null): bool
    {
        $team_id = $team_id ?? request()->team_id;

        if (!$team_id) {
            return false;
        }

        $maxStorageMb = (int) \Access::permission('appfiles.max_storage');
        if ($maxStorageMb <= 0) {
            return false; 
        }

        $maxStorageBytes = $maxStorageMb * 1024 * 1024;
        $usedBytes = (int) Files::where('team_id', $team_id)->where('is_folder', 0)->sum('size');

        return ($usedBytes + $newFileSizeBytes) > $maxStorageBytes;
    }
    
    public function handleFileUpload(Request $request, $folder)
    {
        $files = $request->file('files');
        $fileData = [];

        $totalSize = array_sum(array_map(fn($file) => $file->getSize(), $files));
        if ($this->checkStorageLimit($totalSize)) {
            throw new \Exception(__('You have exceeded your storage limit.'));
        }

        foreach ($files as $file) {
            $file_name = $this->sanitizeFileName($file->getClientOriginalName());
            $extension = strtolower($file->getClientOriginalExtension());
            $size = $file->getSize();

            // Extension check
            $allowedFileTypes = is_array($this->allowedFileTypes)
                ? $this->allowedFileTypes
                : explode(',', $this->allowedFileTypes);

            if (!in_array($extension, $allowedFileTypes)) {
                throw new \Exception(__('This file type is not allowed: ') . $extension);
            }

            // Size check (MB)
            $limitMb = $this->maxFileSize > 0 ? $this->maxFileSize : 50;
            if ($size > $limitMb * 1024 * 1024) {
                throw new \Exception(__('File size exceeds the maximum allowed size of :size MB.', [
                    'size' => $limitMb
                ]));
            }

            // Ensure destination directory exists if local storage
            try {
                @mkdir(storage_path('app/public/files'), 0775, true);
            } catch (\Throwable $e) {}

            // Safe MIME detection without crashing if php_fileinfo is disabled
            $mime_type = null;
            try {
                $mime_type = $file->getMimeType();
            } catch (\Throwable $e) {}

            if (empty($mime_type) || $mime_type === 'application/octet-stream') {
                try {
                    $mime_type = $file->getClientMimeType();
                } catch (\Throwable $e) {}
            }

            if (empty($mime_type)) {
                $mimeMap = [
                    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                    'gif' => 'image/gif', 'webp' => 'image/webp', 'mp4' => 'video/mp4',
                    'mov' => 'video/quicktime', 'avi' => 'video/x-msvideo', 'webm' => 'video/webm', 'm4v' => 'video/mp4', 'mkv' => 'video/x-matroska', 'wmv' => 'video/x-ms-wmv', '3gp' => 'video/3gpp', '3gpp' => 'video/3gpp',
                    'csv' => 'text/csv', 'pdf' => 'application/pdf', 'mp3' => 'audio/mpeg',
                    'json' => 'application/json', 'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xls' => 'application/vnd.ms-excel',
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ];
                $mime_type = $mimeMap[$extension] ?? 'application/octet-stream';
            }

            if ($extension === 'csv' && in_array($mime_type, ['text/plain', 'application/octet-stream'])) {
                $mime_type = 'text/csv';
            }

            // Ensure destination directories exist with full write permissions
            try {
                @mkdir(storage_path('app/public/files'), 0777, true);
                @mkdir(public_path('storage/files'), 0777, true);
            } catch (\Throwable $e) {}

            // Save file via Storage disk
            $relativePath = false;
            try {
                $relativePath = Storage::disk($this->disk)->putFileAs('files', $file, $file_name, 'public');
            } catch (\Throwable $e) {}

            // Direct disk copy fallback to ensure file exists even if symlink or putFileAs failed
            $destStorage = storage_path('app/public/files/' . $file_name);
            $destPublic = public_path('storage/files/' . $file_name);
            if ($file->isValid()) {
                if (!file_exists($destStorage)) {
                    @copy($file->getRealPath(), $destStorage);
                }
                if (!file_exists($destPublic)) {
                    $srcCopy = (file_exists($destStorage) && @filesize($destStorage) > 0) ? $destStorage : $file->getRealPath();
                    if ($srcCopy && file_exists($srcCopy)) { @copy($srcCopy, $destPublic); }
                }
            }

            if (!$relativePath) {
                $relativePath = 'files/' . $file_name;
            }

            // Convert to final URL/path
            if ($this->disk === 's3') {
                $file_path = Storage::disk($this->disk)->url($relativePath);
            } elseif ($this->disk === 'contabo') {
                $publicUrl = rtrim(get_option('file_contabos3_public_url'), '/'); 
                $file_path = "{$publicUrl}/{$relativePath}";
            } else {
                $file_path = $relativePath;
            }

            // Detect image info
            $is_image = false;
            $img_width = 0;
            $img_height = 0;
            if ($file->isValid() && (\Str::startsWith($mime_type, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']))) {
                try {
                    $realPath = $file->getRealPath();
                    if ($realPath && file_exists($realPath)) {
                        $info = @getimagesize($realPath);
                        if ($info) {
                            $img_width = $info[0] ?? 0;
                            $img_height = $info[1] ?? 0;
                            $is_image = true;
                        }
                    }
                    if (!$is_image && class_exists(ImageManager::class)) {
                        $ImageManager = ImageManager::gd();
                        $image = $ImageManager->read($file->getRealPath());
                        $img_width = $image->width();
                        $img_height = $image->height();
                        $is_image = true;
                    }
                } catch (\Throwable $e) {
                    $is_image = true;
                }
            }

            $fileData[] = [
                'id_secure' => rand_string(),
                'team_id' => $request->team_id,
                'is_folder' => 0,
                'pid' => $folder->id ?? 0,
                'name' => $file_name,
                'file' => $file_path,
                'type' => $mime_type,
                'extension' => $extension,
                'detect' => Media::detectFileType($extension),
                'size' => $file->getSize(),
                'is_image' => $is_image,
                'width' => (int)$img_width,
                'height' => (int)$img_height,
                'created' => time(),
            ];
        }

        Files::insert($fileData);
    }

    public function finalizeChunkedFile(string $partPath, string $originalName, int $teamId, int $pid = 0)
    {
        if (!file_exists($partPath)) {
            throw new \Exception(__('Uploaded chunk file not found.'));
        }
        $size = (int)@filesize($partPath);
        if ($size <= 0) {
            @unlink($partPath);
            throw new \Exception(__('Uploaded file is empty.'));
        }
        $limitMb = $this->maxFileSize > 0 ? $this->maxFileSize : 150;
        if ($size > $limitMb * 1024 * 1024) {
            @unlink($partPath);
            throw new \Exception(__('File size exceeds the maximum allowed size of :size MB.', ['size' => $limitMb]));
        }
        if ($this->checkStorageLimit($size, $teamId)) {
            @unlink($partPath);
            throw new \Exception(__('You have exceeded your storage limit.'));
        }
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = is_array($this->allowedFileTypes) ? $this->allowedFileTypes : explode(',', (string)$this->allowedFileTypes);
        if (!in_array($extension, $allowed)) {
            @unlink($partPath);
            throw new \Exception(__('This file type is not allowed: ') . $extension);
        }
        $file_name = $this->sanitizeFileName($originalName);
        $mimeMap = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp', 'mp4' => 'video/mp4',
            'm4v' => 'video/mp4', 'mov' => 'video/quicktime', 'avi' => 'video/x-msvideo',
            'webm' => 'video/webm', 'mkv' => 'video/x-matroska', 'wmv' => 'video/x-ms-wmv',
            '3gp' => 'video/3gpp', '3gpp' => 'video/3gpp', 'mp3' => 'audio/mpeg',
            'pdf' => 'application/pdf', 'csv' => 'text/csv', 'json' => 'application/json',
        ];
        $mime_type = $mimeMap[$extension] ?? 'application/octet-stream';
        $relativePath = 'files/' . $file_name;

        if ($this->disk === 's3' || $this->disk === 'contabo') {
            $stream = fopen($partPath, 'rb');
            Storage::disk($this->disk)->put($relativePath, $stream, 'public');
            if (is_resource($stream)) fclose($stream);
            @unlink($partPath);
            if ($this->disk === 's3') {
                $file_path = Storage::disk($this->disk)->url($relativePath);
            } else {
                $pubUrl = rtrim((string)get_option('file_contabos3_public_url'), '/');
                $file_path = $pubUrl . '/' . $relativePath;
            }
        } else {
            @mkdir(storage_path('app/public/files'), 0775, true);
            @mkdir(public_path('storage/files'), 0775, true);
            $destStorage = storage_path('app/public/' . $relativePath);
            $destPublic = public_path('storage/' . $relativePath);
            if (!@rename($partPath, $destStorage)) {
                @copy($partPath, $destStorage);
                @unlink($partPath);
            }
            if (file_exists($destStorage)) {
                @copy($destStorage, $destPublic);
            }
            $file_path = $relativePath;
        }

        Files::insert([[
            'id_secure' => rand_string(),
            'team_id' => $teamId,
            'is_folder' => 0,
            'pid' => $pid,
            'name' => $file_name,
            'file' => $file_path,
            'type' => $mime_type,
            'extension' => $extension,
            'detect' => Media::detectFileType($extension),
            'size' => $size,
            'is_image' => 0,
            'width' => 0,
            'height' => 0,
            'created' => time(),
        ]]);
        return $file_path;
    }
    public function storeSingleFile($file, string $folder = 'uploads', bool $autoCrop = false, string $aspectRatio = '1:1'): string
    {
        if (method_exists($file, 'getClientOriginalName')) {
            $fileName = $this->sanitizeFileName($file->getClientOriginalName());
        } else {
            $fileName = $this->sanitizeFileName($file->getFilename());
        }

                $fileMime = '';
        try {
            $fileMime = $file->getMimeType();
        } catch (\Throwable $e) {}
        if (empty($fileMime) && method_exists($file, 'getClientMimeType')) {
            $fileMime = $file->getClientMimeType();
        }
        $fileExt = method_exists($file, 'getClientOriginalExtension') ? strtolower($file->getClientOriginalExtension()) : '';
        $isImage = str_starts_with($fileMime, 'image/') || in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);

        if ($autoCrop && $isImage) {
            $manager = \Intervention\Image\ImageManager::gd();
            $image = $manager->read($file->getRealPath());

            $width = $image->width();
            $height = $image->height();

            [$aspectW, $aspectH] = explode(':', $aspectRatio);
            $aspectRatioFloat = floatval($aspectW) / floatval($aspectH);
            $currentRatio = $width / $height;

            if ($currentRatio > $aspectRatioFloat) {
                $newWidth = intval($height * $aspectRatioFloat);
                $x = intval(($width - $newWidth) / 2);
                $y = 0;
                $cropWidth = $newWidth;
                $cropHeight = $height;
            } else {
                $newHeight = intval($width / $aspectRatioFloat);
                $x = 0;
                $y = intval(($height - $newHeight) / 2);
                $cropWidth = $width;
                $cropHeight = $newHeight;
            }

            $cropped = $image->crop($cropWidth, $cropHeight, $x, $y);

            $tempDir = sys_get_temp_dir();
            $tempPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;
            $cropped->save($tempPath);

            $relativePath = Storage::disk($this->disk)
                ->putFileAs($folder, new \Illuminate\Http\File($tempPath), $fileName, 'public');

            @unlink($tempPath);
        } else {
            $relativePath = Storage::disk($this->disk)
                ->putFileAs($folder, $file, $fileName, 'public');
        }

                if ($this->disk === 'public') {
            $src = storage_path('app/public/' . $relativePath);
            $dst = public_path('storage/' . $relativePath);
            if (is_file($src) && !is_link(public_path('storage')) && realpath($src) !== @realpath($dst)) {
                @mkdir(dirname($dst), 0775, true);
                @copy($src, $dst);
            }
        }

        if ($this->disk === 's3') {
            return Storage::disk($this->disk)->url($relativePath);
        } elseif ($this->disk === 'contabo') {
            $publicUrl = rtrim(get_option('file_contabos3_public_url'), '/');
            return "{$publicUrl}/{$relativePath}";
        }

        return $relativePath;
    }


    public function storeSingleFileFromURL(string $url, string $folder = 'uploads', ?string $customFileName = null): string
    {
        $response = \Http::timeout(60)
            ->withOptions([
                'verify' => false
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new \Exception("Unable to download file from URL: $url");
        }

        $fileContent = $response->body();

        $finalFileName = $this->sanitizeFileNameFromUrl($url, $customFileName);

        $tempPath = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tempPath, $fileContent);

        $file = new \Illuminate\Http\File($tempPath);

        $relativePath = Storage::disk($this->disk)
            ->putFileAs($folder, $file, $finalFileName, 'public');

        @unlink($tempPath);

        if ($this->disk === 's3') {
            return Storage::disk($this->disk)->url($relativePath);
        } elseif ($this->disk === 'contabo') {
            $publicUrl = rtrim(get_option('file_contabos3_public_url'), '/');
            return "{$publicUrl}/{$relativePath}";
        }

        return $relativePath;
    }

    public function saveFileFromUrl(array $file)
    {
        if (empty($file['file_url'])) {
            throw new \Exception(__('File URL is required'));
        }

        $from = $file['from'] ?? '';
        if ($from) {
            switch ($from) {
                case 'google_drive':
                    if (!\Gate::allows('appfiles.google_drive')) {
                        throw new \Exception(__('You do not have permission to upload files from Google Drive.'));
                    }
                    break;
                case 'dropbox':
                    if (!\Gate::allows('appfiles.dropbox')) {
                        throw new \Exception(__('You do not have permission to upload files from Dropbox.'));
                    }
                    break;
                case 'onedrive':
                    if (!\Gate::allows('appfiles.onedrive')) {
                        throw new \Exception(__('You do not have permission to upload files from OneDrive.'));
                    }
                    break;
            }
        }

        $fileContent = $this->downloadFileContent($file['file_url'], $from, $file['access_token'] ?? '');
        $file_name = $file['file_name'] ?? basename(parse_url($file['file_url'], PHP_URL_PATH));
        $file_ext  = pathinfo($file_name, PATHINFO_EXTENSION);
        $file_name = $this->sanitizeFileNameFromUrl($file_name);

        $file_type = null;
        if ($from === 'google_drive') {
            $file_type = 'application/octet-stream';
        } else {
            $response  = \Http::get($file['file_url']);
            if (!$response->successful()) {
                throw new \Exception(__('Failed to fetch file headers'));
            }
            $file_type = $response->header('Content-Type') ?: 'application/octet-stream';
            if (!$file_ext) {
                $map = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'video/mp4'  => 'mp4',
                    'audio/mpeg' => 'mp3',
                    'application/json' => 'json',
                    'text/csv'   => 'csv',
                ];
                $file_ext  = $map[$file_type] ?? 'bin';
                $file_name .= '.' . $file_ext;
            }
        }

        $file_detect = Media::detectFileType($file_ext);
        $file_size   = strlen($fileContent);

        if (!in_array(strtolower($file_ext), $this->allowedFileTypes)) {
            throw new \Exception(__('Invalid file type. Allowed: ') . implode(', ', $this->allowedFileTypes));
        }
        if ($this->checkStorageLimit($file_size)) {
            throw new \Exception(__('You have exceeded your storage limit.'));
        }
        if ($file_size > $this->maxFileSize * 1024 * 1024) {
            throw new \Exception(__('File size exceeds the maximum allowed size of :size MB.', [
                'size' => $this->maxFileSize
            ]));
        }

        // Save object key
        $relativePath = 'files/' . uniqid() . '_' . $file_name;
        Storage::disk($this->disk)->put($relativePath, $fileContent);
        if ($this->disk === 'public') {
            $pubTarget = public_path('storage/' . $relativePath);
            @mkdir(dirname($pubTarget), 0775, true);
            @file_put_contents($pubTarget, $fileContent);
        }

        // Build final URL
        if ($this->disk === 's3') {
            $file_path = Storage::disk($this->disk)->url($relativePath);
        } elseif ($this->disk === 'contabo') {
            $publicUrl = rtrim(get_option('file_contabos3_public_url'), '/');
            $file_path = "{$publicUrl}/{$relativePath}";
        } else {
            $file_path = $relativePath;
        }

        // Image info
        $is_image   = false;
        $img_width  = 0;
        $img_height = 0;
        if (str_starts_with($file_type, 'image/')) {
            try {
                $ImageManager = ImageManager::gd();
                $image = $ImageManager->read(Storage::disk($this->disk)->path($relativePath));
                $img_width  = $image->width();
                $img_height = $image->height();
                $is_image   = true;
            } catch (\Exception $e) {}
        }

        $fileData = [
            'id_secure' => rand_string(),
            'team_id'   => request()->team_id,
            'is_folder' => 0,
            'pid'       => $file['folder_id'] ?? 0,
            'name'      => $file_name,
            'file'      => $file_path,
            'type'      => $file_type,
            'extension' => $file_ext,
            'detect'    => $file_detect,
            'size'      => $file_size,
            'is_image'  => $is_image,
            'width'     => (int)$img_width,
            'height'    => (int)$img_height,
            'created'   => time(),
        ];

        Files::insert($fileData);

        return \Media::url($file_path);
    }

    protected function sanitizeFileNameFromUrl($url, $customFileName = null)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $baseNameRaw = basename($path);
        $decodedName = urldecode($baseNameRaw);
        $filename = $customFileName ?: $decodedName;
        $filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $filename);
        $filename = strtolower($filename);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/[^a-z0-9\-_]+/', '-', $name);
        $name = trim($name, '-_');
        $rand = rand_string(6);

        return $name . '_' . $rand . ($ext ? '.' . $ext : '');
    }

    protected function downloadFileContent($file_url, $from, $access_token = '')
    {
        if ($from === 'google_drive') {
            if (empty($access_token)) {
                throw new \Exception(__('Access Token is required'));
            }
            return $this->getGoogleDriveFileContent($file_url, $access_token)['fileContent'];
        }

        $response = \Http::get($file_url);
        if (!$response->successful()) {
            throw new \Exception(__('Failed to download file'));
        }

        return $response->body();
    }

    protected function getGoogleDriveFileContent($file_id, $access_token)
    {
        $this->client = new Google_Client();
        $this->client->setClientId(get_option('file_google_drive_client_id'));
        $this->client->setClientSecret(get_option('file_google_drive_client_secret'));
        $this->client->setDeveloperKey(get_option('file_google_drive_api_key'));
        $this->client->addScope(Google_Service_Drive::DRIVE_FILE);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        $this->client->setAccessToken($access_token);
        $this->service = new Google_Service_Drive($this->client);

        $response = $this->service->files->get($file_id, ['alt' => 'media']);
        $content = $response->getBody()->getContents();
        $fileName = $response->getHeaders()['Content-Disposition'][0] ?? $file_id;

        return ['fileContent' => $content, 'name' => $fileName];
    }

    public function saveMultipleFilesFromUrls(array $files, $folder_id = false)
    {
        $result = [];
        foreach ($files as $file) {
            $file = json_decode($file, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($file['link'], $file['name'], $file['from'])) {
                $result[] = $this->saveFileFromUrl([
                    'file_url' => $file['link'],
                    'file_name' => $file['name'],
                    'folder_id' => $folder_id,
                    'from' => $file['from'],
                    'access_token' => $file['access_token'] ?? '',
                ]);
            }
        }
        return $result;
    }

    /**
     * Deletes files and folders based on the provided request.
     */
    public function destroy(Request $request)
    {
        $id_arr = id_arr($request->input('id'));

        if (empty($id_arr)) {
            return response()->json([
                'status'  => 0,
                'message' => __('Please select at least one item'),
            ]);
        }

        // Get files and folders by id_secure and file
        $items = Files::whereIn('id_secure', $id_arr)
                      ->orWhereIn('file', $id_arr)
                      ->get();

        // Delete files and folders recursively
        foreach ($items as $item) {
            if ($item->is_folder) {
                $this->deleteFolderAndContents($item->id);
            } else {
                $this->deleteFileFromServer($item->file);
                $item->delete();
            }
        }

        return response()->json([
            'status'  => 1,
            'message' => __('Succeed'),
        ]);
    }

    /**
     * Recursively deletes a folder and its contents.
     *
     * @param int $folder_id
     * @return bool
     */
    protected function deleteFolderAndContents($folder_id)
    {
        $folder = Files::with('subfolders')->where('id', $folder_id)->first();

        if (!$folder) {
            return false;
        }

        // Delete all subfolders/files within the folder.
        $this->deleteContents($folder);

        // Delete the folder file from storage and then remove its record.
        $this->deleteFileFromServer($folder->file);
        $folder->delete();

        return true;
    }

    /**
     * Recursively deletes the contents (files and subfolders) of a folder.
     * Note: This method no longer deletes the folder record itself.
     *
     * @param Files $folder
     */
    protected function deleteContents($folder)
    {
        // Delete subfolders recursively.
        if (!empty($folder->subfolders)) {
            foreach ($folder->subfolders as $subfolder) {
                $this->deleteFolderAndContents($subfolder->id);
            }
        }

        // Delete files directly within this folder.
        $files = Files::where('pid', $folder->id)->get();
        foreach ($files as $file) {
            $this->deleteFileFromServer($file->file);
            $file->delete();
        }
    }

    protected function getObjectKeyFromUrl($url, $bucket = null)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $parsed = parse_url($url);
        if (!isset($parsed['path'])) {
            return $url;
        }

        $path = ltrim($parsed['path'], '/');

        // Nếu có bucket, bỏ prefix bucket/
        if ($bucket && strpos($path, $bucket . '/') === 0) {
            $path = substr($path, strlen($bucket) + 1);
        }

        return $path;
    }

    public function deleteFileFromServer($filePath)
    {
        if (!$filePath) return;

        $bucket = null;
        if ($this->disk === 's3') {
            $bucket = get_option('file_aws_bucket_name');
        } elseif ($this->disk === 'contabo') {
            $bucket = get_option('file_contabos3_bucket_name');
        }

        $filePath = $this->getObjectKeyFromUrl($filePath, $bucket);

        if (Storage::disk($this->disk)->exists($filePath)) {
            Storage::disk($this->disk)->delete($filePath);
        }
    }

    protected function sanitizeFileName($filename)
    {
        $filename = strtolower($filename);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/[^a-z0-9\-_]+/u', '-', $name);
        $name = trim($name, '-_');
        $rand = rand_string(6);
        $clean = $name . '_' . $rand . ($ext ? '.' . $ext : '');

        return $clean;
    }
}