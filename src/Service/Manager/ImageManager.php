<?php

namespace Anfallnorr\FileManagerSystem\Service\Manager;

use Symfony\Component\Filesystem\Filesystem;

class ImageManager
{
    private array $unite;

    public function __construct(
        private Filesystem $filesystem,
        private PathManager $pathManager,
        private MimeManager $mimeManager,
    ) {
        $this->unite = ['o' => "Octets", 'ko' => "Ko", 'mo' => "Mo", 'go' => "Go"];
    }

    public function getImageSize(string $filePath): ?array
    {
        $imageSize = ($this->pathManager->isAbsolute($filePath))
            ? @\getimagesize($filePath)
            : @\getimagesize($this->pathManager->abs($filePath));

        if ($imageSize) {
            return [
                'width' => $imageSize[0] ?? null,
                'height' => $imageSize[1] ?? null
            ];
        }
        return null;
    }

    public function getDimensionsFileInfo(string $filePath): array
    {
        $imageSize = @\getimagesize($filePath);
        return [
            'width' => $imageSize[0] ?? null,
            'height' => $imageSize[1] ?? null
        ];
    }

    public static function getSize(string|array $files, int $totalFileSize = 0): int|float
    {
        if (\is_string($files)) {
            $totalFileSize = $totalFileSize + \filesize($files);
        } else {
            foreach ($files as $size) {
                if (\is_array($size) && isset($size['absolute'])) {
                    $totalFileSize = $totalFileSize + \filesize($size['absolute']);
                } else {
                    $totalFileSize = $totalFileSize + \filesize($size);
                }
            }
        }
        return $totalFileSize;
    }

    public function getSizeName(int|float $size): string
    {
        if ($size < 1024) {
            return "{$size} {$this->unite['o']}";
        } elseif ($size < 10485760) {
            $ko = \round($size / 1024, 2);
            return "{$ko} {$this->unite['ko']}";
        } elseif ($size < 1073741824) {
            $mo = \round($size / (1024 * 1024), 2);
            return "{$mo} {$this->unite['mo']}";
        } else {
            $go = \round($size / (1024 * 1024 * 1024), 2);
            return "{$go} {$this->unite['go']}";
        }
    }

    public function resizeImages(array $files, string $sourceDir, string $targetDir, int $width, int $quality = 100, ?string $suffix = null): array
    {
        if ($width <= 0 || $quality <= 0 || $quality > 100) {
            throw new \InvalidArgumentException("Les valeurs de largeur et de qualité doivent être valides.");
        }

        $errors = [];
        $processed = [];

        if (!$this->filesystem->exists($targetDir)) {
            $this->filesystem->mkdir($targetDir);
        }

        foreach ($files as $file) {
            try {
                $image_path = $sourceDir . '/' . $file;
                if (!file_exists($image_path)) {
                    throw new \Exception("Le fichier image n'existe pas : " . $file);
                }

                $info = getimagesize($image_path);
                if (!$info) {
                    throw new \Exception("Le fichier image est corrompu ou n'est pas une image : " . $file);
                }

                $mime = $info['mime'];
                $type = strtolower(substr($mime, strpos($mime, '/') + 1));

                if (!in_array($type, ['jpeg', 'jpg', 'png', 'webp'])) {
                    throw new \Exception("Le format de fichier image n'est pas supporté : " . $file);
                }

                $old_width = $info[0];
                $old_height = $info[1];

                if ($width >= $old_width) {
                    continue;
                }

                $ratio = $width / $old_width;
                $new_width = $width;
                $new_height = intval($ratio * $old_height);

                if ($old_width > 9000 || $old_height > 9000) {
                    throw new \Exception("Le fichier image est trop grand : " . $old_width . "x" . $old_height);
                }

                switch ($type) {
                    case 'jpg':
                    case 'jpeg':
                        $source = imagecreatefromjpeg($image_path);
                        break;
                    case 'webp':
                        $source = imagecreatefromwebp($image_path);
                        break;
                    case 'png':
                        $source = imagecreatefrompng($image_path);
                        imagealphablending($source, false);
                        imagesavealpha($source, true);
                        break;
                }

                $output_image = imagecreatetruecolor($new_width, $new_height);
                if ($type === 'png') {
                    imagealphablending($output_image, false);
                    imagesavealpha($output_image, true);
                }

                if (!imagecopyresampled($output_image, $source, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height)) {
                    throw new \Exception("Impossible de redimensionner l'image : " . $file);
                }

                $fileInfo = pathinfo($file);
                $newFilename = $suffix 
                    ? $fileInfo['filename'] . '-' . $suffix . '.' . $fileInfo['extension']
                    : $file;

                $targetPath = $targetDir . '/' . $newFilename;

                switch ($type) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg($output_image, $targetPath, $quality);
                        break;
                    case 'webp':
                        imagewebp($output_image, $targetPath, $quality);
                        break;
                    case 'png':
                        imagepng($output_image, $targetPath, (int)((9 - ($quality / 100) * 9)));
                        break;
                }

                imagedestroy($source);
                imagedestroy($output_image);

                $fileDetails = [
                    'absolute' => $targetPath,
                    'relative' => substr($targetPath, strlen($this->pathManager->getKernelDirectory() . $this->pathManager->getRelativeDirectory())),
                    'filename' => $newFilename,
                    'filesize' => $this->getSizeName(filesize($targetPath)),
                    'filemtime' => filemtime($targetPath),
                    'extension' => $fileInfo['extension'],
                    'mime' => $this->mimeManager->getMimeContent($targetPath),
                    'dimensions' => [
                        'width' => $new_width,
                        'height' => $new_height
                    ],
                    'suffix' => $suffix
                ];

                $processed[] = $fileDetails;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return ['success' => $processed, 'errors' => $errors];
    }
}
