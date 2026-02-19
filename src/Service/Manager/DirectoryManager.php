<?php

namespace Anfallnorr\FileManagerSystem\Service\Manager;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\Slugger\SluggerInterface;

class DirectoryManager
{
    public function __construct(
        private Filesystem $filesystem,
        private PathManager $pathManager,
        private SluggerInterface $slugger,
        private ?FileManager $fileManager = null,
        private ?ImageManager $imageManager = null,
    ) {
    }

    public function setFileManager(FileManager $fileManager): void
    {
        $this->fileManager = $fileManager;
    }

    public function setImageManager(ImageManager $imageManager): void
    {
        $this->imageManager = $imageManager;
    }

    public function createSlug(string $string): string
    {
        return $this->slugger->slug($string)->lower();
    }

    public function createDir(?string $directory = null, bool $returnDetails = false): array|bool
    {
        $defaultDir = $this->pathManager->getDefaultDirectory();
        $kernelDir = $this->pathManager->getKernelDirectory();
        $relativeDir = $this->pathManager->getRelativeDirectory();

        if ($directory) {
            $outputDirectories = [];

            if (\str_contains($directory, '+')) {
                $directories = \explode('+', $directory);
                foreach ($directories as $dir) {
                    $dirs = $this->createSlug($dir);
                    $this->filesystem->mkdir("{$defaultDir}/{$dirs}");
                    $relative = \substr("{$defaultDir}/{$dirs}", \strlen("{$kernelDir}{$relativeDir}"));
                    $outputDirectories[] = [
                        'absolute' => "{$defaultDir}/{$dirs}",
                        'relative' => $relative,
                        'ltrimmed_relative' => \ltrim($relative, '/'),
                        'foldername' => $dirs
                    ];
                }
            } elseif (\str_contains($directory, '/')) {
                $nestedDirectories = "";
                $directories = \explode('/', $directory);
                $firstDir = $this->createSlug($directories[0]);

                foreach ($directories as $dir) {
                    $nestedDirectories .= "/{$this->createSlug($dir)}";
                }

                if (!empty($nestedDirectories)) {
                    $this->filesystem->mkdir("{$defaultDir}{$nestedDirectories}");
                    $relative = \substr("{$defaultDir}/{$firstDir}", \strlen("{$kernelDir}{$relativeDir}"));
                    $outputDirectories[] = [
                        'absolute' => "{$defaultDir}/{$firstDir}",
                        'relative' => $relative,
                        'ltrimmed_relative' => \ltrim($relative, '/'),
                        'foldername' => $firstDir
                    ];
                }
            } else {
                $dir = $this->createSlug($directory);
                $this->filesystem->mkdir("{$defaultDir}/{$dir}");
                $relative = \substr("{$defaultDir}/{$dir}", \strlen("{$kernelDir}{$relativeDir}"));
                $outputDirectories[] = [
                    'absolute' => "{$defaultDir}/{$dir}",
                    'relative' => $relative,
                    'ltrimmed_relative' => \ltrim($relative, '/'),
                    'foldername' => $dir
                ];
            }
        } else {
            $dir = basename($defaultDir);
            if (!$this->filesystem->exists($defaultDir)) {
                $this->filesystem->mkdir($defaultDir);
            }
            $outputDirectories[] = [
                'absolute' => $defaultDir,
                'relative' => "/{$dir}",
                'ltrimmed_relative' => $dir,
                'foldername' => $dir
            ];
        }

        return ($returnDetails) ? $outputDirectories : true;
    }

    public function getDirs(string $path = '/', string $excludeDir = "", string|array|null $depth = '== 0'): array
    {
        $defaultDir = $this->pathManager->getDefaultDirectory();
        $realPath = \realpath($defaultDir . '/' . \trim($path, '/'));

        if (!$realPath || !\is_dir($realPath)) {
            return [];
        }

        $finder = new Finder();
        if ($depth !== null) {
            $finder->depth($depth);
        }
        $finder->directories()->in($realPath);

        $directories = [];
        foreach ($finder as $dir) {
            $dirPath = $dir->getRealPath();
            if ($excludeDir && \str_contains($dirPath, $excludeDir)) {
                continue;
            }

            $relative = \str_replace($defaultDir, '', $dirPath);
            
            $filesize = null;
            $files = null;
            if ($this->fileManager) {
                $files = $this->fileManager->getFiles(\basename($dirPath));
                if ($files && $this->imageManager) {
                    $filesize = $this->imageManager->getSize($files);
                }
            }

            $directories[] = [
                'absolute' => $dirPath,
                'relative' => $relative,
                'absolute_dir' => $defaultDir,
                'relative_dir' => $this->pathManager->getRelativeDirectory(),
                'dirname' => \basename(\dirname($dirPath)),
                'filemtime' => \filemtime($dirPath),
                'filesize' => ($filesize && $this->imageManager) ? $this->imageManager->getSizeName($filesize) : null,
                'files' => $files,
                'ltrimmed_relative' => \ltrim($relative, '/'),
                'foldername' => $dir->getFilename(),
            ];
        }

        return $directories;
    }

    public function getDirsTree(string $path = '/', string $excludeDir = ""): array
    {
        $defaultDir = $this->pathManager->getDefaultDirectory();
        $realPath = \realpath($defaultDir . '/' . \trim($path, '/'));

        if (!$realPath || !\is_dir($realPath)) {
            return [];
        }

        $finder = new Finder();
        $finder->directories()->in($realPath)->depth('== 0');

        $directories = [];
        foreach ($finder as $dir) {
            $dirPath = $dir->getRealPath();
            if ($excludeDir && \str_contains($dirPath, $excludeDir)) {
                continue;
            }

            $relative = \str_replace($defaultDir, '', $dirPath);
            $children = $this->getDirsTree($relative, $excludeDir);
            $files = ($this->fileManager) ? ($this->fileManager->getFiles($relative) ?: []) : [];

            $directories[] = [
                'absolute' => $dirPath,
                'relative' => $relative,
                'ltrimmed_relative' => \ltrim($relative, '/'),
                'foldername' => $dir->getFilename(),
                'children' => $children,
                'files' => $files,
                'dirs_length' => \count($children),
                'files_length' => \count($files),
            ];
        }

        return $directories;
    }

    public function cleanDir(string $dir = ''): void
    {
        if (empty($dir)) {
            $dir = $this->pathManager->getRelativeDirectory();
        }

        $this->pathManager->setDefaultDirectory($dir);

        $dirs = $this->getDirs();
        $files = ($this->fileManager) ? $this->fileManager->getFiles() : [];

        if (empty($files) && empty($dirs)) {
            if ($this->fileManager) {
                $this->fileManager->remove();
            }

            $parentDir = \dirname($dir);
            if ($parentDir !== $dir) {
                $this->cleanDir($parentDir);
            }
        }
    }

    public function hasDir(): bool
    {
        return !empty($this->getDirs());
    }

    public static function getSliceDirs(string|array $dirs, int $slice, bool $implode = false): string|array|bool
    {
        if (\is_array($dirs)) {
            $tree_structure = [];
            foreach ($dirs as $dir) {
                $tree_structure[] = \array_slice(\explode("/", $dir), $slice);
            }
        } else {
            $tree_structure = \array_slice(\explode("/", $dirs), $slice);
        }

        if ($implode === true && !empty($tree_structure)) {
            if (\is_array($dirs)) {
                $tree_structure_imploded = [];
                foreach ($tree_structure as $implode_structure) {
                    $tree_structure_imploded[] = \implode("/", $implode_structure);
                }
            } else {
                $tree_structure_imploded = "/" . \implode("/", $tree_structure);
            }
            return $tree_structure_imploded;
        }

        return empty($tree_structure) ? false : $tree_structure;
    }
}
