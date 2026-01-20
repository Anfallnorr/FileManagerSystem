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

	/* // ************************ GETFILTERS
	public function getFilters(): array
	{
		return [
			new TwigFilter('basename', [$this, 'basename']),
			new TwigFilter('dirname', [$this, 'dirname']),
		];
	} */

	/**
	 * Get basename of a path
	 */
	/* public function basename(string $path): string
	{
		return \basename($path);
	} */

	/**
	 * Get dirname of a path
	 */
	/* public function dirname(string $path): string
	{
		return \dirname($path);
	} */

	// ************************ GETFUNCTIONS
	public function getFunctions(): array
	{
		return [
			new TwigFunction(name: 'listDirs', callable: [$this, 'listDirectories']),
			new TwigFunction(name: 'listFiles', callable: [$this, 'listFiles'])
		];
	}

	/**
	 * Retourne la liste des sous-dossiers d’un répertoire.
	 *
	 * @param string $path Chemin absolu du dossier à analyser
	 * @return array Liste des noms de dossiers
	 */
	public function listDirectories(string $path): array
	{
		return \array_values(
			\array_filter(
				$this->scanDirectory($path),
				fn($item): bool => \is_dir($path . DIRECTORY_SEPARATOR . $item)
			)
		);
	}

	/**
	 * Retourne la liste des fichiers d’un répertoire.
	 *
	 * @param string $path Chemin absolu du dossier à analyser
	 * @return array Liste des noms de fichiers
	 */
	public function listFiles(string $path): array
	{
		return \array_values(
			\array_filter(
				$this->scanDirectory($path),
				fn($item): bool => \is_file($path . DIRECTORY_SEPARATOR . $item)
			)
		);
	}

	private function scanDirectory(string $filePath): array
	{
		return \array_filter(\scandir($filePath), fn($ar): bool => !\in_array($ar, ['.', '..']));
	}
}
