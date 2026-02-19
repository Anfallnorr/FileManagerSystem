<?php

namespace Anfallnorr\FileManagerSystem\Service\Manager;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileManager
{
    public function __construct(
        private Filesystem $filesystem,
        private PathManager $pathManager,
        private MimeManager $mimeManager,
        private ImageManager $imageManager,
        private SluggerInterface $slugger,
    ) {}

    public function createSlug(string $string): string
    {
        return $this->slugger->slug($string)->lower();
    }

    public function exists(?string $filePath = null): bool
    {
        $filePath ??= $this->pathManager->getDefaultDirectory();
        if ($this->pathManager->isAbsolute($filePath)) {
            return $this->filesystem->exists($filePath);
        }
        $exist = $this->pathManager->getDefaultDirectory() . '/' . \ltrim($filePath, '/');
        return $this->filesystem->exists($exist);
    }

    public function getFileContent(string $relativeFile): string
    {
        return \file_get_contents($this->pathManager->abs($relativeFile));
    }

    public function createFile(string $filename, string $content = '<!DOCTYPE html><html lang="en"><body style="background: #ffffff;"></body></html>'): void
    {
        $extension = \pathinfo($filename, PATHINFO_EXTENSION);
        $filename = \pathinfo($filename, PATHINFO_FILENAME);
        $slug = $this->createSlug($filename);
        $this->filesystem->dumpFile($this->pathManager->getDefaultDirectory() . '/' . $slug . '.' . $extension, $content);
    }

    public function getFiles(string $path = '/', string|array|null $depth = '== 0', ?string $folder = null, ?string $ext = null): array|bool
    {
        $defaultDir = $this->pathManager->getDefaultDirectory();
        $realPath = \realpath(\rtrim($defaultDir, '/') . '/' . \trim($path, '/'));

        if (!$realPath || !is_dir($realPath)) {
            return false;
        }

        $finder = new Finder();
        if ($depth !== null) {
            $finder->depth($depth);
        }
        $finder->files()->in($realPath);

        if ($folder) {
            $finder->path($folder);
        }
        if ($ext) {
            $finder->files()->name("*.{$ext}");
        }

        if (!$finder->hasResults()) {
            return false;
        }

        $fileList = [];
        foreach ($finder as $file) {
            $fileList[] = $this->getFileInfo($file);
        }

        return $fileList;
    }

    public function getFileInfo(\SplFileInfo $file): array
    {
        $filePath = $file->getRealPath();
        $kernelDir = $this->pathManager->getKernelDirectory();
        $relativeDir = $this->pathManager->getRelativeDirectory();
        $defaultDir = $this->pathManager->getDefaultDirectory();

        return [
            'absolute' => $filePath,
            'relative' => \substr($filePath, \strlen($kernelDir . $relativeDir)),
            'absolute_dir' => $defaultDir,
            'relative_dir' => $relativeDir,
            'dirname' => \basename(\dirname($filePath)),
            'filename' => $file->getFilename(),
            'filesize' => $this->imageManager->getSizeName($file->getSize()),
            'filemtime' => $file->getMTime(),
            'dimensions' => $this->imageManager->getDimensionsFileInfo($filePath),
            'extension' => $file->getExtension(),
            'mime' => $this->mimeManager->getMimeContent($file->getPathname())
        ];
    }

    public function remove(string $relativePath = ''): bool
    {
        $defaultDir = $this->pathManager->getDefaultDirectory();
        if (empty($relativePath)) {
            $this->filesystem->remove($defaultDir);
            return !$this->exists($defaultDir);
        } else {
            $path = \ltrim($relativePath, '/');
            $this->filesystem->remove("{$defaultDir}/{$path}");
            return !$this->exists($relativePath);
        }
    }

    public function copy(string $source, string $destination, bool $override = false): bool
    {
        $this->filesystem->copy(
            "{$this->pathManager->getKernelDirectory()}{$source}",
            $this->pathManager->abs($destination),
            $override
        );
        return true;
    }

    public function rename(string $source, string $destination, bool $override = false): bool
    {
        $ext = \pathinfo($source, PATHINFO_EXTENSION);
        $slug = $this->createSlug($destination);
        $newSource = (empty($ext)) ? $slug : "{$slug}.{$ext}";
        $defaultDir = $this->pathManager->getDefaultDirectory();

        $this->filesystem->rename(
            "{$defaultDir}/{$source}",
            "{$defaultDir}/{$newSource}",
            $override
        );

        return !$this->exists("{$defaultDir}/{$source}");
    }

    public function move(string $origine, string $target, bool $overwrite = false): bool
    {
        $kernelDir = $this->pathManager->getKernelDirectory();
        $absoluteOrigine = $kernelDir . '/' . \ltrim($origine, '/');
        $absoluteTarget = $kernelDir . '/' . \ltrim($target, '/');

        $this->filesystem->rename($absoluteOrigine, $absoluteTarget, $overwrite);

        return (!$this->exists($absoluteOrigine) && $this->exists($absoluteTarget));
    }

    public function upload(UploadedFile|array $files, string $folder, string $newName = "", bool $return = false): array|bool
    {
        $uploadedFiles = [];
        $multiple = null;

        $files = \is_array($files) ? $files : [$files];
        if (!empty($newName) && \count($files) > 1) {
            $multiple = true;
        }

        foreach ($files as $key => $file) {
            $fileInfo = (!empty($newName))
                ? [
                    'filename' => ($multiple) ? "{$newName}-" . ($key + 1) : $newName,
                    'extension' => $file->getClientOriginalExtension()
                ]
                : \pathinfo($file->getClientOriginalName());

            $filename = $this->createSlug($fileInfo['filename']) . '.' . \strtolower($fileInfo['extension']);
            $kernelDir = $this->pathManager->getKernelDirectory();
            $relativeDir = $this->pathManager->getRelativeDirectory();

            $output = [
                'absolute' => "{$folder}/{$filename}",
                'relative' => \substr("{$folder}/{$filename}", \strlen($kernelDir . $relativeDir)),
                'filename' => $filename,
                'filesize' => $this->imageManager->getSizeName($file->getSize()),
                'filemtime' => $file->getMTime(),
                'extension' => (!empty($file->getExtension())) ? $file->getExtension() : \pathinfo($filename, PATHINFO_EXTENSION),
            ];

            if (!$file->move($folder, $filename)) {
                throw new \Exception("A problem occurred while uploading this file: {$filename}");
            }

            $output['mime'] = $this->mimeManager->getMimeContent("{$folder}/{$filename}");
            $output['dimensions'] = $this->imageManager->getDimensionsFileInfo("{$folder}/{$filename}");

            $uploadedFiles[] = $output;
        }

        return ($return) ? $uploadedFiles : true;
    }

    public function categorizeFiles(array $files, bool $basename = false, bool $path = false): array
    {
        $categories = [
            'documents' => ['src' => [], 'basename' => [], 'path' => []],
            'images' => ['src' => [], 'basename' => [], 'path' => []],
            'audios' => ['src' => [], 'basename' => [], 'path' => []],
            'videos' => ['src' => [], 'basename' => [], 'path' => []],
            'other' => ['src' => [], 'basename' => [], 'path' => []],
        ];

        foreach ($files as $file) {
            $extension = \strtolower((string) \pathinfo($file, PATHINFO_EXTENSION));
            $categorized = false;

            foreach (['documents', 'images', 'audios', 'videos'] as $type) {
                if (\in_array($extension, MimeManager::getExtByType($type), true)) {
                    $categories[$type]['src'][] = $file;
                    if ($basename) $categories[$type]['basename'][] = \basename($file);
                    if ($path) $categories[$type]['path'][] = $this->getExtractedFolder($file);
                    $categorized = true;
                    break;
                }
            }

            if (!$categorized) {
                $categories['other']['src'][] = $file;
                if ($basename) $categories['other']['basename'][] = \basename($file);
                if ($path) $categories['other']['path'][] = $this->getExtractedFolder($file);
            }
        }

        return $categories;
    }

    public function getExtractedFolder(string $folder): string
    {
        return 'getExtractedFolder'; // To be implemented or kept as is for BC if mocked
    }
}
