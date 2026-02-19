<?php

namespace Anfallnorr\FileManagerSystem\Service\Manager;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ArchiveManager
{
    public function __construct(
        private PathManager $pathManager,
    ) {
    }

    public function download(string $name, ?string $directory = null): BinaryFileResponse
    {
        $defaultDir = $this->pathManager->getDefaultDirectory();
        $path = $directory ? "{$defaultDir}/{$directory}/{$name}" : "{$defaultDir}/{$name}";

        if (!\file_exists($path)) {
            throw new \RuntimeException("Fichier introuvable : {$path}");
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $name);

        return $response;
    }

    public function downloadBulk(array $names, ?string $directory = null): BinaryFileResponse
    {
        $defaultDir = $this->pathManager->getDefaultDirectory();
        $baseDir = $directory ? "{$defaultDir}/{$directory}" : $defaultDir;
        $paths = [];

        foreach ($names as $name) {
            $paths[] = "{$baseDir}/{$name}";
        }

        $prepared = $this->prepareDownload($paths, $baseDir);

        $response = new BinaryFileResponse($prepared['path']);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $prepared['name']);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function prepareDownload(array $paths, string $baseDir): array
    {
        if (\count($paths) === 1 && \is_file($paths[0])) {
            return [
                'path' => $paths[0],
                'name' => \basename($paths[0])
            ];
        }

        $tempFile = \tempnam(\sys_get_temp_dir(), 'fms_zip');
        $zip = new \ZipArchive();

        if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Impossible de créer l'archive ZIP");
        }

        foreach ($paths as $path) {
            if (\is_dir($path)) {
                $this->addDirectoryToZip($zip, $path, \basename($path));
            } elseif (\is_file($path)) {
                $zip->addFile($path, \basename($path));
            }
        }

        $zip->close();

        return [
            'path' => $tempFile,
            'name' => 'archive_' . \date('YmdHis') . '.zip'
        ];
    }

    private function addDirectoryToZip(\ZipArchive $zip, string $dir, string $baseName): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $localPath = $baseName . DIRECTORY_SEPARATOR . \substr($item->getRealPath(), \strlen($dir) + 1);

            if ($item->isDir()) {
                $zip->addEmptyDir($localPath);
            } else {
                $zip->addFile($item->getRealPath(), $localPath);
            }
        }
    }
}
