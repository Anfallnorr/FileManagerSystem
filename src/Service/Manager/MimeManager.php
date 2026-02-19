<?php

namespace Anfallnorr\FileManagerSystem\Service\Manager;

use Symfony\Component\Mime\MimeTypes;

class MimeManager
{
    public const array EXTENSIONS = [
        'documents' => ['doc', 'docx', 'odf', 'odp', 'ods', 'odt', 'otf', 'ppt', 'csv', 'pps', 'pptx', 'xls', 'xlsx', 'rtf', 'txt', 'pdf'],
        'images' => ['jpg', 'jpeg', 'png', 'tif', 'webp', 'bmp', 'ico', 'svg', 'gif'],
        'audios' => ['mp3', 'wav', 'wave', 'wma', 'aac', 'mid', 'midi', 'ogg', 'aif', 'aiff'],
        'videos' => ['mp4', 'mpg', 'mpeg', 'mov', '3gp', 'avi']
    ];

    public const array MIME_TYPES = [
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'svg' => 'image/svg+xml',
        'psd' => 'image/vnd.adobe.photoshop',
        'indd' => 'application/x-indesign',
        'cdr' => 'application/coreldraw',
        'sketch' => 'application/sketch',
        'fig' => 'application/fig',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'tiff' => 'image/tiff',
        'ico' => 'image/x-icon',
        'html' => 'text/html',
        'htm' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'php' => 'application/x-httpd-php',
        'txt' => 'text/plain',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'tar' => 'application/x-tar',
        'gz' => 'application/gzip',
        '7z' => 'application/x-7z-compressed',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'webm' => 'video/webm',
    ];

    public function __construct(
        private MimeTypes $mime,
        private PathManager $pathManager,
    ) {
    }

    public function getMimeTypes(): array
    {
        return self::MIME_TYPES;
    }

    public function getMimeType(string $key): string|array|null
    {
        return self::MIME_TYPES[$key] ?? null;
    }

    public function getMimeContent(string $filename): string
    {
        return $this->mime->guessMimeType((!$this->pathManager->isAbsolute($filename))
            ? $this->pathManager->abs($filename)
            : $filename
        );
    }

    public static function getExtByType(string $type): array
    {
        return self::EXTENSIONS[$type] ?? [];
    }
}
