<?php

namespace Anfallnorr\FileManagerSystem\Service\Manager;

class PathManager
{
    public function __construct(
        private string $kernelDirectory,
        private string $defaultDirectory,
        private string $relativeDirectory,
    ) {
    }

    /**
     * Génère un chemin absolu à partir d'un chemin relatif.
     */
    public function abs(string $relative): string
    {
        return $this->getKernelDirectory() . '/' . \ltrim($relative, '/');
    }

    /**
     * Un chemin est considéré comme "absolu" uniquement s'il se situe
     * sous la racine du projet (kernel directory).
     */
    public function isAbsolute(string $path): bool
    {
        return \str_starts_with($path, $this->getKernelDirectory());
    }

    /**
     * Retourne le répertoire principal (kernel) de l'application Symfony.
     */
    public function getKernelDirectory(): string
    {
        return \rtrim($this->kernelDirectory, '/');
    }

    /**
     * Retourne le répertoire par défaut utilisé par le service.
     */
    public function getDefaultDirectory(): string
    {
        return \rtrim($this->defaultDirectory, '/');
    }

    /**
     * Définit le répertoire par défaut utilisé par le service.
     */
    public function setDefaultDirectory(string $directory): static
    {
        $this->defaultDirectory = $this->abs($directory);
        $this->relativeDirectory = \rtrim($directory, '/');

        return $this;
    }

    /**
     * Retourne le répertoire relatif configuré dans le service.
     */
    public function getRelativeDirectory(): string
    {
        return \rtrim($this->relativeDirectory, '/');
    }

    /**
     * Définit le répertoire relatif utilisé par le service.
     */
    public function setRelativeDirectory(string $directory): static
    {
        $this->relativeDirectory = \rtrim($directory, '/');

        return $this;
    }
}
