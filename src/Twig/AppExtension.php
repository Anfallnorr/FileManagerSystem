<?php

// src/Twig/AppExtension.php
namespace Anfallnorr\FileManagerSystem\Twig;

use Anfallnorr\FileManagerSystem\Service\FileManagerService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private FileManagerService $fmService
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('fm_exists', [$this, 'exists']),
        ];
    }

    public function exists(string $path): bool
    {
        // return file_exists($path);
        return $this->fmService->exists($path);
    }
}
