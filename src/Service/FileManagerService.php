<?php

/**
 * Update 20260219
 * Gestionnaire de fichiers pour récupérer les informations des fichiers et dossiers.
 */

namespace Anfallnorr\FileManagerSystem\Service;

// use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\String\Slugger\AsciiSlugger;
// use Symfony\Contracts\Translation\TranslatorInterface;

// use function Symfony\Component\Deprecation\trigger_deprecation;

/**
 * ============================================================================
 * METHODS INDEX (for navigation & quick search)
 * ============================================================================
 *
 * private abs(string $relative): string
 * private isAbsolute(string $path): bool
 * private getKernelDirectory(): string
 * public getDefaultDirectory(): string
 * public setDefaultDirectory(string $directory): static
 * public getRelativeDirectory(): string
 * public setRelativeDirectory(string $directory): static
 *
 * public getMimeTypes(): array
 * public getMimeType(string $key): string|array|null
 * public getMimeContent(string $filename): ?string
 * public getFileContent(string $relativeFile): string
 * public exists(?string $filePath = null): bool
 *
 * public createSlug(string $string): string
 * public createFile(string $filename, string $content = '...'): void
 * public createDir(?string $directory = null, bool $returnDetails = false): array|bool
 *
 * static categorizeFiles(array $files, bool $basename = false, bool $path = false): array
 * static getExtractedFolder(string $folder): string
 * static getExtByType(string $type): array
 *
 * public getDirs(string $path = '/', string $excludeDir = "", string|array|null $depth = '== 0'): array
 * public getDirsTree(string $path = '/', string $excludeDir = ""): array
 * static getSliceDirs(string|array $dirs, int $slice, bool $implode = false): string|array
 *
 * public cleanDir(?string $dir = null): void
 * // public cleanDir(string $dir = ''): void
 * public getFiles(string $path = '/', string|array|null $depth = '== 0', ?string $folder = null, ?string $ext = null): array|bool
 *
 * public getImageSize(string $filePath): ?array
 * private getFileInfo(\SplFileInfo $file): array
 * private getDimensionsFileInfo(string $filePath): array
 *
 * static getSize(string|array $files, int $totalFileSize = 0): int|float
 * public getSizeName(int|float $size): string
 *
 * public upload(UploadedFile|array $files, string $folder, string $newName = "", bool $return = false): array|bool
 * public resizeImages(array $files, string $sourceDir, string $targetDir, int $width, int $quality = 100, ?string $suffix = null): array
 *
 * public hasDir(): bool
 *
 * public download(string $name, ?string $directory = null): BinaryFileResponse
 * public downloadBulk(array $names, ?string $directory = null): BinaryFileResponse
 * private prepareDownload(array $paths, string $baseDir): array
 * private addDirectoryToZip(\ZipArchive $zip, string $dir, string $baseName): void
 *
 * public remove(string $relativePath = ''): bool
 * public copy(string $source, string $destination, bool $override = false): bool
 * public rename(string $source, string $destination, bool $override = false): bool
 * public move(string $origine, string $target, bool $overwrite = false): bool
 *
 * ============================================================================
 *
 * @todo
 * if (!$finder->hasResults()) { <- getFiles
 *		return false;
 *		// return []; // pourquoi false ? Ça, c’est le truc qui va faire chier un jour.
 * }
 *
 * @todo
 * public function getDirsTree(string $path = '/', string $excludeDir = ""): array <- $excludeDir = null par défaut
 *
 * @todo
 * public function cleanDir(?string $dir = null): void <- A surveiller
 *
 * @todo
 * Dédoublonner `getDimensionsFileInfo` et `getImageSize`
 *
 * @todo
 * public function upload(UploadedFile|array $files, string $folder, string $newName = "", bool $return = false): array|bool <- $return -> $returnDetails
 *
 * @todo
 * public function remove(string $relativePath = ''): bool <- ?string $relativePath = null
 */
class FileManagerService
{
	final protected const array EXTENSIONS = [
		'documents' => ['doc', 'docx', 'odf', 'odp', 'ods', 'odt', 'otf', 'ppt', 'csv', 'pps', 'pptx', 'xls', 'xlsx', 'rtf', 'txt', 'pdf'],
		'images' => ['jpg', 'jpeg', 'png', 'tif', 'webp', 'bmp', 'ico', 'svg', 'gif'],
		'audios' => ['mp3', 'wav', 'wave', 'wma', 'aac', 'mid', 'midi', 'ogg', 'aif', 'aiff'],
		'videos' => ['mp4', 'mpg', 'mpeg', 'mov', '3gp', 'avi']
	];

	// Initialisation des types MIME pour différents formats de fichiers
	protected const array MIME_TYPES = [
		// Formats graphiques vectoriels et images
		'ai' => 'application/postscript',     // Adobe Illustrator
		'eps' => 'application/postscript',    // Encapsulated PostScript
		'svg' => 'image/svg+xml',             // SVG
		'psd' => 'image/vnd.adobe.photoshop', // Photoshop
		'indd' => 'application/x-indesign',   // InDesign
		'cdr' => 'application/coreldraw',     // CorelDRAW (non standard, peut varier)
		'sketch' => 'application/sketch',     // Sketch (non standard, peut varier)
		'fig' => 'application/fig',           // Figma (non standard, peut varier)

		// Formats d'image courants
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png' => 'image/png',
		'gif' => 'image/gif',
		'webp' => 'image/webp',
		'bmp' => 'image/bmp',
		'tiff' => 'image/tiff',
		'ico' => 'image/x-icon',

		// Formats de développement web
		'html' => 'text/html',
		'htm' => 'text/html',
		'css' => 'text/css',
		'js' => 'application/javascript',
		'json' => 'application/json',
		'xml' => 'application/xml',
		'php' => 'application/x-httpd-php', // PHP

		// Formats texte
		'txt' => 'text/plain', // Fichier texte brut

		// Formats de documents
		'pdf' => 'application/pdf',
		'doc' => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls' => 'application/vnd.ms-excel',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'ppt' => 'application/vnd.ms-powerpoint',
		'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

		// Archives
		'zip' => 'application/zip',
		'rar' => 'application/x-rar-compressed',
		'tar' => 'application/x-tar',
		'gz' => 'application/gzip',
		'7z' => 'application/x-7z-compressed',

		// Formats audio et vidéo
		'mp3' => 'audio/mpeg',
		'wav' => 'audio/wav',
		'ogg' => 'audio/ogg',
		'mp4' => 'video/mp4',
		'avi' => 'video/x-msvideo',
		'mov' => 'video/quicktime',
		'webm' => 'video/webm',
	];

	// private array $mimeTypes;
	private array $unite;

	public function __construct(
		private string $kernelDirectory,
		private string $defaultDirectory,
		private string $relativeDirectory,
		private MimeTypes $mime,
		private Filesystem $filesystem,
		private AsciiSlugger $slugger
	) {
		// Unités de mesure pour la taille des fichiers
		$this->unite = ['o' => "Octets", 'ko' => "Ko", 'mo' => "Mo", 'go' => "Go"];
	}

	/**
	 * Génère un chemin absolu à partir d'un chemin relatif.
	 *
	 * Cette méthode combine le répertoire racine du projet (kernel) avec le chemin relatif
	 * fourni, en normalisant les séparateurs de dossier.
	 *
	 * @param string $relative Le chemin relatif à convertir en chemin absolu.
	 *
	 * @return string Le chemin absolu résultant.
	 *
	 * @example
	 * ```php
	 * $absolutePath = $this->abs('uploads/images'); 
	 * // Retourne quelque chose comme '/var/www/project/uploads/images'
	 * ```
	 */
	private function abs(string $relative): string
	{
		/* $return = (!$absolute)
			? \rtrim($this->kernelDirectory, '/') . '/' . \ltrim($path, '/')
			: $path;

		return $return; */
		// return \rtrim($this->kernelDirectory, '/') . '/' . \ltrim($relative, '/');
		return $this->getKernelDirectory() . '/' . \ltrim($relative, '/');
		/* $basePath = \realpath($this->kernelDirectory);

		if ($basePath === false) {
			throw new \RuntimeException("Kernel directory introuvable");
		}

		$fullPath = \realpath(
			$basePath . DIRECTORY_SEPARATOR . \ltrim($relative, '/')
		);

		if ($fullPath === false) {
			throw new \RuntimeException("Chemin introuvable");
		}

		if (!\str_starts_with($fullPath, $basePath)) {
			throw new \RuntimeException("Accès interdit");
		}

		return $fullPath; */
	}

	/**
	 * Vérifie si un chemin est absolu au sein du projet.
	 * 
	 * Un chemin est considéré comme "absolu" s'il se situe explicitement
	 * sous la racine du projet (kernel directory). Les chemins système externes
	 * sont rejetés.
	 *
	 * @param string $path Le chemin à vérifier.
	 *
	 * @return bool True si le chemin est absolu (dans le projet).
	 */
	private function isAbsolute(string $path): bool
	{
		// return str_starts_with($path, $this->getKernelDirectory());
		return \str_starts_with(
			\ltrim($path, '/'),
			\ltrim($this->getKernelDirectory(), '/')
		);
	}

	/**
	 * Retourne le chemin absolu du répertoire racine du projet (kernel).
	 *
	 * @return string Le chemin absolu du kernel projet.
	 */
	private function getKernelDirectory(): string
	{
		// return $this->kernelDirectory;
		return \rtrim($this->kernelDirectory, '/');
	}

	/**
	 * Retourne le répertoire de travail par défaut utilisé par le service.
	 *
	 * Cette méthode fournit le chemin absolu vers le répertoire configuré 
	 * comme cible prioritaire pour les opérations sur les fichiers.
	 *
	 * @return string Le chemin du répertoire par défaut.
	 *
	 * @example
	 * ```php
	 * $defaultDir = $service->getDefaultDirectory();
	 * // Retourne quelque chose comme '/var/www/project/uploads/images'
	 * ```
	 */
	public function getDefaultDirectory(): string
	{
		// return $this->defaultDirectory;
		return \rtrim($this->defaultDirectory, '/');
	}

	/**
	 * Définit le répertoire de travail par défaut utilisé par le service.
	 *
	 * Cette méthode configure le chemin absolu cible. Le chemin fourni est 
	 * automatiquement converti en format absolu via la méthode `abs()`.
	 * Elle permet le chaînage des méthodes (fluent interface).
	 *
	 * @param string $directory Le chemin relatif ou absolu à définir comme répertoire par défaut.
	 *
	 * @return static L'instance du service pour le chaînage de méthodes.
	 *
	 * @example
	 * ```php
	 * $service->setDefaultDirectory('uploads/images')
	 *         ->createFile('test.html');
	 * // Définit le répertoire par défaut à '/var/www/project/uploads/images'
	 * ```
	 */
	public function setDefaultDirectory(string $directory): static
	{
		// $this->relativeDirectory = $directory;
		// $this->defaultDirectory = $this->getKernelDirectory() . $directory;
		$this->defaultDirectory = $this->abs($directory);
		$this->relativeDirectory = \rtrim($directory, '/');
		return $this;
	}

	/**
	 * Retourne le chemin relatif du répertoire de travail.
	 *
	 * Cette méthode fournit le chemin relatif actuellement défini par rapport
	 * à la racine du projet, utile pour construire des URLs ou des chemins logiques.
	 *
	 * @return string Le chemin relatif du répertoire.
	 *
	 * @example
	 * ```php
	 * $relativeDir = $service->getRelativeDirectory();
	 * // Retourne quelque chose comme 'uploads/images'
	 * ```
	 */
	public function getRelativeDirectory(): string
	{
		// return $this->relativeDirectory;
		return \rtrim($this->relativeDirectory, '/');
	}

	/**
	 * Définit le chemin relatif du répertoire de travail.
	 *
	 * Cette méthode configure la valeur du chemin relatif utilisé pour
	 * les opérations logiques, sans le transformer en chemin physique absolu.
	 * Elle permet le chaînage des méthodes.
	 *
	 * @param string $directory Le chemin relatif à définir pour le service.
	 *
	 * @return static L'instance du service pour le chaînage de méthodes.
	 *
	 * @deprecated Depuis la version 1.0.45, cette fonction sera supprimée dans la version 1.0.46
	 *             Utilisez plutôt `setDefaultDirectory()`.
	 *
	 * @example
	 * ```php
	 * $service->setRelativeDirectory('uploads/images')
	 *         ->createFile('test.html');
	 * // Définit le répertoire relatif à 'uploads/images'
	 * ```
	 */
	/* #[\Deprecated(
		message: 'La méthode `%class%::setRelativeDirectory()` est obsolète, utilisez plutôt `setDefaultDirectory()`.',
		since: '1.0.44',
	)] */
	public function setRelativeDirectory(string $directory): static
	{
		trigger_deprecation(
			'anfallnorr/file-manager-system',
			'1.0.45',
			// 'The `%class%::setRelativeDirectory()` method is deprecated, use `setDefaultDirectory()` instead. It will be removed in 1.0.46'
			'La méthode `%class%::setRelativeDirectory()` est obsolète ; utilisez plutôt `setDefaultDirectory()`. Elle sera supprimée dans la version 1.0.46'
		);
		// $this->relativeDirectory = $directory;
		$this->relativeDirectory = \rtrim($directory, '/');
		return $this;
	}

	/**
	 * Liste les types MIME supportés par le service.
	 *
	 * Cette méthode fournit un tableau associatif complet mappant les 
	 * extensions de fichiers à leurs types MIME respectifs.
	 *
	 * @return array Un tableau associatif des extensions et de leurs types MIME.
	 *
	 * @example
	 * ```php
	 * $mimeTypes = $service->getMimeTypes();
	 * // Retourne quelque chose comme ['jpg' => 'image/jpeg', 'png' => 'image/png', ...]
	 * ```
	 */
	public function getMimeTypes(): array
	{
		// return $this->mimeTypes;
		return self::MIME_TYPES;
	}

	/**
	 * Récupère le type MIME associé à une extension donnée.
	 *
	 * Cette méthode recherche dans la configuration interne le type MIME 
	 * correspondant à une extension. Certaines extensions peuvent retourner un tableau.
	 *
	 * Si l’extension demandée n'existe pas dans la liste, la méthode renvoie `null`.
	 *
	 * @param string $key L’extension du fichier (ex : 'jpg', 'png', 'pdf').
	 *
	 * @return string|array|null Le type MIME associé, un tableau de types MIME, ou null si non trouvé.
	 *
	 * @example
	 * ```php
	 * $mime = $service->getMimeType('jpg'); 
	 * // Retourne 'image/jpeg'
	 *
	 * $mime = $service->getMimeType('svg');
	 * // Peut retourner ['image/svg+xml', 'text/xml']
	 *
	 * $mime = $service->getMimeType('unknown');
	 * // Retourne null
	 * ```
	 */
	public function getMimeType(string $key): string|array|null
	{
		// return $this->mimeTypes[$key] ?? null;
		return self::MIME_TYPES[$key] ?? null;
	}

	/**
	 * Détecte le type MIME réel d’un fichier physique à partir de son contenu.
	 *
	 * Cette méthode utilise le composant Symfony `MimeTypes` pour analyser 
	 * le contenu du fichier et en déduire son type MIME exact.
	 *
	 * Si `$absolute` est défini à `false`, le chemin fourni est considéré comme relatif
	 * et converti en chemin absolu via la méthode `abs()`.  
	 * S'il est défini à `true`, le chemin est directement utilisé tel quel.
	 *
	 * @param string $filename Le chemin du fichier.
	 *
	 * @return string|null Le type MIME détecté pour le fichier, ou null en cas d'échec.
	 *
	 * @example
	 * ```php
	 * // Fichier dans le répertoire par défaut
	 * $mime = $service->getMimeContent('uploads/photo.jpg');
	 * // Retourne par exemple 'image/jpeg'
	 *
	 * // Chemin absolu (détection automatique via isAbsolute)
	 * $mime = $service->getMimeContent('/var/www/project/uploads/photo.jpg');
	 * ```
	 */
	// public function getMimeContent(string $filename, bool $absolute = false): string
	public function getMimeContent(string $filename): ?string
	{
		// return \mime_content_type($this->getKernelDirectory() . $relativeFile);
		// return \mime_content_type($this->abs($relativeFile));
		// return $this->mime->guessMimeType((!$absolute)
		return $this->mime->guessMimeType((!$this->isAbsolute($filename))
			? $this->abs($filename)
			: $filename
		);
	}

	/**
	 * Lit et retourne le contenu complet d'un fichier.
	 *
	 * Cette méthode récupère la chaîne de caractères brute d'un fichier 
	 * situé via un chemin relatif converti en absolu.
	 *
	 * @param string $relativeFile Le chemin relatif du fichier à lire.
	 *
	 * @return string Le contenu du fichier.
	 *
	 * @example
	 * ```php
	 * $content = $service->getFileContent('storage/data.json');
	 * // Retourne le contenu du fichier sous forme de chaîne
	 * ```
	 */
	public function getFileContent(string $relativeFile): string
	{
		// return $this->filesystem->readFile($this->getKernelDirectory() . $relativeFile);
		// return \file_get_contents($this->getKernelDirectory() . $relativeFile);
		return \file_get_contents($this->abs($relativeFile));
	}

	/**
	 * Vérifie la présence physique d'un fichier ou d'un dossier.
	 *
	 * Cette méthode contrôle l'existence d'un élément sur le disque en 
	 * gérant automatiquement les chemins relatifs (via defaultDirectory) ou absolus.
	 *
	 * @param ?string $filePath Le chemin absolu ou relatif vers le fichier ou dossier. 
	 *                          Si null, utilise le répertoire par défaut.
	 *
	 * @return bool True si le fichier ou répertoire existe, sinon false.
	 *
	 * @example
	 * ```php
	 * // Chemin relatif (dans le répertoire par défaut)
	 * if ($service->exists('images/photo.jpg')) {
	 *     // Le fichier existe
	 * }
	 *
	 * // Chemin absolu (détection automatique via isAbsolute)
	 * $service->exists('/var/www/project/uploads/photo.jpg');
	 * ```
	 */
	// public function exists(?string $filePath = null, bool $absolute = false): bool
	public function exists(?string $filePath = null): bool
	{
		$filePath ??= $this->getDefaultDirectory();
		// $absolute = str_starts_with($filePath, $this->getKernelDirectory());
		// $absolute = $this->isAbsolute($filePath);

		// dump($this->getKernelDirectory());
		// dump($absolute);
		// dump($filePath);

		/* if ($absolute) {
			return $this->filesystem->exists($filePath);
		} else {
			$filePath = \ltrim($filePath, '/');
			// $exist = $this->getDefaultDirectory() . '/' . \ltrim($filePath, '/');
			$exist = ($filePath !== $this->getDefaultDirectory())
				? $this->getDefaultDirectory() . '/' . $filePath
				: $filePath;

			// dump($exist);
			return $this->filesystem->exists($exist);
		} */
		// if ($absolute) {
		if ($this->isAbsolute($filePath)) {
			return $this->filesystem->exists($filePath);
		}

		$exist = $this->getDefaultDirectory() . '/' . \ltrim($filePath, '/');

		// dump($exist);
		return $this->filesystem->exists($exist);
	}

	/**
	 * Génère un slug URL-safe à partir d’une chaîne de caractères.
	 *
	 * Cette méthode transforme un texte en format nettoyé (minuscules, sans caractères spéciaux),
	 * idéal pour les noms de fichiers et URLs.
	 *
	 * @param string $string La chaîne à convertir en slug.
	 *
	 * @return string Le slug généré en minuscules.
	 *
	 * @example
	 * ```php
	 * $slug = $service->createSlug('Mon Super Fichier.JPG');
	 * // Retourne : 'mon-super-fichier-jpg'
	 * ```
	 */
	public function createSlug(string $string): string
	{
		return $this->slugger->slug($string)->lower();
	}

	/**
	 * Crée un nouveau fichier avec un contenu spécifique.
	 *
	 * Cette méthode génère un fichier dans le répertoire par défaut. Elle slugifie
	 * le nom fourni tout en préservant son extension. Si le fichier existe, il est écrasé.
	 *
	 * @param string $filename Le nom du fichier à créer (peut inclure un chemin relatif).
	 * @param string $content  Le contenu à écrire dans le fichier. Par défaut, un
	 *                         template HTML minimal est utilisé.
	 *
	 * @return void
	 *
	 * @example
	 * ```php
	 * // Crée un fichier 'mon-fichier.html' dans le répertoire par défaut
	 * $service->createFile('Mon Fichier.html', '<h1>Bonjour</h1>');
	 * ```
	 */
	public function createFile(string $filename, string $content = '<!DOCTYPE html><html lang="en"><body style="background: #ffffff;"></body></html>'): void
	{
		$extension = \pathinfo($filename, PATHINFO_EXTENSION);
		$filename = \pathinfo($filename, PATHINFO_FILENAME);

		$slug = $this->createSlug($filename);
		$this->filesystem->dumpFile($this->getDefaultDirectory() . '/' . $slug . '.' . $extension, $content);
		// $this->filesystem->dumpFile("{$this->getDefaultDirectory()}/{$slug}.{$extension}", $content);
		// $this->filesystem->dumpFile($this->getDefaultDirectory() . '/' . $this->createSlug($filename) . '.' . $extension, $content);
		// $this->filesystem->dumpFile("{$this->getDefaultDirectory()}/{$this->createSlug($filename)}.{$extension}", $content);
	}

	/**
	 * Crée un ou plusieurs répertoires physiquement sur le disque.
	 *
	 * Cette méthode supporte la création simple, multiple (via '+') ou imbriquée (via '/')
	 * de dossiers. Les noms sont automatiquement slugifiés pour la sécurité du système de fichiers.
	 *
	 * @param string|null $directory Le ou les répertoires à créer (supporte `+` pour plusieurs et `/` pour imbriqués).
	 * @param bool        $returnDetails Indique si la méthode doit retourner les informations des répertoires créés.
	 *
	 * @return array|bool Un tableau des répertoires créés avec leurs détails si $returnDetails est true, sinon un booléen.
	 *
	 * @example
	 * ```php
	 * // Création de plusieurs répertoires séparés par '+'
	 * $dirs = $service->createDir('images+docs+temp', true);
	 *
	 * // Création d'un chemin imbriqué
	 * $dirs = $service->createDir('uploads/photos/2025', true);
	 *
	 * // Création d'un répertoire simple (sans détails en retour)
	 * $success = $service->createDir('temp');
	 * ```
	 */
	// public function createDir(string $directory, bool $return = false): array
	public function createDir(?string $directory = null, bool $returnDetails = false): array|bool
	{
		if ($directory) {
			$outputDirectories = [];

			if (\str_contains($directory, '+')) {
				$directories = \explode('+', $directory);

				foreach ($directories as $dir) {
					$dirs = $this->createSlug($dir);
					// $this->filesystem->mkdir($this->getDefaultDirectory() . '/' . $dirs);
					$this->filesystem->mkdir("{$this->getDefaultDirectory()}/{$dirs}");

					// $relative = \substr($this->getDefaultDirectory() . '/' . $dirs, \strlen($this->getKernelDirectory() . $this->getRelativeDirectory()));
					$relative = \substr("{$this->getDefaultDirectory()}/{$dirs}", \strlen("{$this->getKernelDirectory()}{$this->getRelativeDirectory()}"));
					$outputDirectories[] = [
						// 'absolute' => $this->getDefaultDirectory() . '/' . $dirs,
						'absolute' => "{$this->getDefaultDirectory()}/{$dirs}",
						'relative' => $relative,
						// 'ltrimed_relative' => \ltrim($relative, '/'),
						'ltrimmed_relative' => \ltrim($relative, '/'),
						'foldername' => $dirs
					];
				}
			} elseif (\str_contains($directory, '/')) {
				$nestedDirectories = "";
				$directories = \explode('/', $directory);
				$firstDir = $this->createSlug($directories[0]);

				foreach ($directories as $dir) {
					// $nestedDirectories .= '/' . $this->createSlug($dir);
					$nestedDirectories .= "/{$this->createSlug($dir)}";
				}

				if (!empty($nestedDirectories)) {
					// $this->filesystem->mkdir($this->getDefaultDirectory() . $nestedDirectories);
					$this->filesystem->mkdir("{$this->getDefaultDirectory()}{$nestedDirectories}");

					// $relative = \substr($this->getDefaultDirectory() . '/' . $firstDir, \strlen($this->getKernelDirectory() . $this->getRelativeDirectory()));
					$relative = \substr("{$this->getDefaultDirectory()}/{$firstDir}", \strlen("{$this->getKernelDirectory()}{$this->getRelativeDirectory()}"));
					$outputDirectories[] = [
						// 'absolute' => $this->getDefaultDirectory() . '/' . $firstDir,
						'absolute' => "{$this->getDefaultDirectory()}/{$firstDir}",
						'relative' => $relative,
						// 'ltrimed_relative' => \ltrim($relative, '/'),
						'ltrimmed_relative' => \ltrim($relative, '/'),
						'foldername' => $firstDir
					];
				}
			} else {
				$dir = $this->createSlug($directory);
				// $this->filesystem->mkdir($this->getDefaultDirectory() . '/' . $dir);
				$this->filesystem->mkdir("{$this->getDefaultDirectory()}/{$dir}");

				// $relative = \substr($this->getDefaultDirectory() . '/' . $dir, \strlen($this->getKernelDirectory() . $this->getRelativeDirectory()));
				$relative = \substr("{$this->getDefaultDirectory()}/{$dir}", \strlen("{$this->getKernelDirectory()}{$this->getRelativeDirectory()}"));
				$outputDirectories[] = [
					// 'absolute' => $this->getDefaultDirectory() . '/' . $dir,
					'absolute' => "{$this->getDefaultDirectory()}/{$dir}",
					'relative' => $relative,
					// 'ltrimed_relative' => \ltrim($relative, '/'),
					'ltrimmed_relative' => \ltrim($relative, '/'),
					'foldername' => $dir
				];
			}
		} else {
			$dir = basename($this->getDefaultDirectory());

			if (!$this->filesystem->exists($this->getDefaultDirectory())) {
				$this->filesystem->mkdir($this->getDefaultDirectory());
			}

			$outputDirectories[] = [
				'absolute' => $this->getDefaultDirectory(),
				'relative' => "/{$dir}",
				'ltrimmed_relative' => $dir,
				'foldername' => $dir
			];
		}

		// return $outputDirectories;
		return ($returnDetails) ? $outputDirectories : true;
	}
	/* public function createDir(string $directory): void
	{
		if (str_contains($directory, '+')) {
			$directories = explode('+', $directory);

			foreach ($directories as $dir) {
				$this->filesystem->mkdir($this->getDefaultDirectory() . '/' . $this->createSlug($dir));
			}
		} elseif (str_contains($directory, '/')) {
			$nestedDirectories = "";
			$directories = explode('/', $directory);

			foreach ($directories as $dir) {
				$nestedDirectories .= '/' . $this->createSlug($dir);
			}

			if (!empty($nestedDirectories)) {
				$this->filesystem->mkdir($this->getDefaultDirectory() . $nestedDirectories);
			}
		} else {
			$this->filesystem->mkdir($this->getDefaultDirectory() . '/' . $this->createSlug($directory));
		}
		// $directories = explode('/', $directory);
		// $this->filesystem->mkdir($this->getDefaultDirectory() . '/' . $this->createSlug($directory));
	} */

	/**
	 * Regroupe une liste de fichiers par catégories d'extensions.
	 *
	 * Cette méthode trie les fichiers (documents, images, etc.) et extrait 
	 * optionnellement leurs noms de base (basename) et dossiers parents.
	 *
	 * @param array $files    Le tableau des fichiers à catégoriser.
	 * @param bool  $basename Un booléen pour spécifier si le tableau doit contenir les noms de fichiers (true) ou non (false).
	 * @param bool  $path     Un booléen pour spécifier si le tableau doit contenir les dossiers parent (true) ou non (false).
	 *
	 * @return array Le tableau des catégories de fichiers.
	 *
	 * @example
	 * ```php
	 * $categories = FileManagerService::categorizeFiles(['photo.jpg', 'doc.pdf'], true);
	 * ```
	 */
	public static function categorizeFiles(array $files, bool $basename = false, bool $path = false): array
	{
		/* // Préparation des catégories de base
		$categories = [
			'documents' => ['src' => [], 'basename' => [], 'path' => []],
			'images'    => ['src' => [], 'basename' => [], 'path' => []],
			'audios'    => ['src' => [], 'basename' => [], 'path' => []],
			'videos'    => ['src' => [], 'basename' => [], 'path' => []],
			'other'     => ['src' => [], 'basename' => [], 'path' => []],
		];

		// Cache pour éviter de rappeler getExtByType 50 fois
		$extCache = [];
		foreach ($categories as $type => $_) {
			$extCache[$type] = self::getExtByType($type);
		}

		// Fonction interne pour factoriser l'ajout
		$addToCategory = function(string $cat, string $file) use (&$categories, $basename, $path) {
			$categories[$cat]['src'][] = $file;

			if ($basename) {
				$categories[$cat]['basename'][] = basename($file);
			}
			if ($path) {
				$categories[$cat]['path'][] = self::getExtractedFolder($file);
			}
		};

		foreach ($files as $file) {
			$extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
			$categorized = false;

			foreach ($categories as $type => $_) {
				if ($type !== 'other' && in_array($extension, $extCache[$type], true)) {
					$addToCategory($type, $file);
					$categorized = true;
					break;
				}
			}

			if (!$categorized) {
				$addToCategory('other', $file);
			}
		}

		return $categories; */
		/* // Initialisation du tableau des catégories de fichiers avec les tableaux vides pour chaque catégorie
		$categories = [
			'documents' => [
				'src' => [], 'basename' => [], 'path' => []
			],
			'images' => [
				'src' => [], 'basename' => [], 'path' => []
			],
			'audios' => [
				'src' => [], 'basename' => [], 'path' => []
			],
			'videos' => [
				'src' => [], 'basename' => [], 'path' => []
			],
			'other' => [
				'src' => [], 'basename' => [], 'path' => []
			]
		];

		// Boucle sur chaque fichier dans le tableau des fichiers
		foreach ($files as $file) {
			// Obtention de l'extension du fichier
			$extension = pathinfo($file, PATHINFO_EXTENSION);
			// Variable pour savoir si le fichier est catégorisé
			$categorized = false;

			// Boucle sur chaque catégorie de fichiers
			foreach ($categories as $type => $category) {
				// Si l'extension du fichier est dans la liste des extensions pour cette catégorie
				if (in_array($extension, self::getExtByType($type))) {
					// Ajout du fichier dans la catégorie correspondante
					$categories[$type]['src'][] = $file;

					// Ajout du nom de fichier dans la catégorie correspondante si demandé
					if ($basename) {
						$categories[$type]['basename'][] = basename($file);
					}

					// Ajout du dossier parent dans la catégorie correspondante si demandé
					if ($path) {
						$categories[$type]['path'][] = self::getExtractedFolder($file);
					}

					// Le fichier est catégorisé, on sort de la boucle
					$categorized = true;
					break;
				}
			}

			// Si le fichier n'a pas été catégorisé, on l'ajoute dans la catégorie "other"
			if (!$categorized) {
				// Ajout du fichier dans la catégorie "other"
				$categories['other']['src'][] = $file;

				// Ajout du nom de fichier dans la catégorie "other" si demandé
				if ($basename) {
					$categories['other']['basename'][] = basename($file);
				}

				// Ajout du dossier parent dans la catégorie "other" si demandé
				if ($path) {
					$categories['other']['path'][] = self::getExtractedFolder($file);
				}
			}
		}

		return $categories; */
		return ['categorizeFiles'];
	}

	/**
	 * Extrait le dossier parent logique d'un chemin de fichier.
	 *
	 * Cette méthode analyse la structure du chemin pour isoler les dossiers 
	 * précédant le nom du fichier, en tenant compte de la structure du projet.
	 * 
	 * @param string $folder Le chemin complet du fichier.
	 *
	 * @return string Le dossier parent du fichier.
	 *
	 * @example
	 * ```php
	 * $folder = FileManagerService::getExtractedFolder('/var/www/uploads/datas/user/folder/file.jpg');
	 * ```
	 */
	public static function getExtractedFolder(string $folder): string
	{
		/* $uploads_datas = "datas";
		$folder_parts = explode("/", $folder);
		$uploads_datas_index = array_search($uploads_datas, $folder_parts);
		$personal_folder_index = $uploads_datas_index + 1;

		$next_is_dir = true;
		$i = $personal_folder_index + 1;
		$extracted_folder = "";

		while ($next_is_dir && $i < count($folder_parts) - 1) {
			if (!str_contains($folder_parts[$i], ".")) {
				$extracted_folder .= "/" . $folder_parts[$i];
			} else {
				$next_is_dir = false;
			}

			$i++;
		}

		return $extracted_folder; */
		return 'getExtractedFolder';
	}

	/**
	 * Liste les extensions associées à une catégorie de fichiers.
	 * 
	 * Cette méthode retourne les extensions (ex: jpg, pdf) correspondant à un type
	 * général de média (ex: images, documents) défini en configuration.
	 * 
	 * @param string $type Le type de fichier pour lequel récupérer les extensions.
	 *
	 * @return array Les extensions de fichier associées au type spécifié, ou un tableau vide si le type n'existe pas.
	 *
	 * @example
	 * ```php
	 * $extensions = FileManagerService::getExtByType('images');
	 * ```
	 */
	public static function getExtByType(string $type): array
	{
		/* if (array_key_exists($type, self::EXTENSIONS)) {
			return self::EXTENSIONS[$type];
		} */

		return [];
	}

	/**
	 * Récupère les dossiers d’un chemin donné dans le répertoire par défaut.
	 *
	 * Cette méthode liste les dossiers situés sous un chemin relatif, avec options
	 * d'exclusion et de limitation de profondeur. Elle fournit des métadonnées détaillées.
	 *
	 * Chaque dossier retourné est représenté par un tableau associatif contenant :
	 * - `absolute` : chemin absolu du dossier,
	 * - `relative` : chemin relatif depuis le répertoire par défaut,
	 * - `ltrimmed_relative` : chemin relatif sans le slash initial,
	 * - `foldername` : nom du dossier.
	 *
	 * @param string            $path       Chemin relatif à partir du répertoire par défaut. '/' par défaut.
	 * @param string            $excludeDir Nom ou motif des dossiers à exclure. Chaîne vide par défaut.
	 * @param string|array|null $depth      Profondeur des dossiers à récupérer. '== 0' par défaut.
	 *
	 * @return array Tableau des dossiers trouvés avec informations détaillées.
	 *
	 * @example
	 * ```php
	 * $dirs = $service->getDirs('uploads', 'temp', '== 1');
	 * // Récupère tous les dossiers à profondeur 1 sous 'uploads', sauf ceux contenant 'temp'
	 * ```
	 */
	// public function getDirs(string $path = '/', string $excludeDir = "", string|null $depth = '== 0'): array
	public function getDirs(string $path = '/', string $excludeDir = "", string|array|null $depth = '== 0'): array
	{
		$realPath = \realpath($this->getDefaultDirectory() . '/' . \trim($path, '/'));

		if (!$realPath || !\is_dir($realPath)) {
			return [];
		}

		$finder = new Finder();
		// if ($depth) {
		if ($depth !== null) {
			$finder->depth($depth); // Search only folders at the given depth $depth
		}
		$finder->directories()->in($realPath); // Search only folders at the root

		$directories = [];
		foreach ($finder as $dir) {
			$dirPath = $dir->getRealPath();

			if ($excludeDir && \str_contains($dirPath, $excludeDir)) {
				continue;
			}

			$relative = \str_replace($this->getDefaultDirectory(), '', $dirPath);

			$files = $this->getFiles(\basename($dirPath));
			$filesize = ($files)
				? $this->getSize($files)
				: null;
			// $relative = \str_replace($this->getKernelDirectory(), '', $dirPath);
// dd($dirPath);
// dd($this->getFiles(\basename($dirPath)));
			$directories[] = [
				'absolute' => $dirPath,
				'relative' => $relative,
				'absolute_dir' => $this->getDefaultDirectory(),
				'relative_dir' => $this->getRelativeDirectory(),
				'dirname' => \basename(\dirname($dirPath)),
				'filemtime' => \filemtime($dirPath),
				'filesize' => ($filesize) ? $this->getSizeName($filesize) : null,
				// 'nb_file' => ($files) ? \count($files) : 0,
				'files' => $files ?: null,
				// 'ltrimed_relative' => \ltrim($relative, '/'),
				'ltrimmed_relative' => \ltrim($relative, '/'),
				'foldername' => $dir->getFilename(),
			];
		}

		return $directories;
	}

	/**
	 * Récupère les dossiers sous forme d’arborescence récursive.
	 *
	 * Cette méthode génère une structure hiérarchique complète incluant les 
	 * sous-dossiers et les métadonnées de fichiers pour chaque nœud.
	 *
	 * Fonctionnalités :
	 * - Exclut les dossiers dont le nom contient `$excludeDir`.
	 * - Permet de limiter la profondeur de recherche avec `$depth`.
	 *
	 * Chaque dossier retourné est un tableau associatif avec :
	 * - `absolute` : chemin absolu du dossier,
	 * - `relative` : chemin relatif depuis le répertoire par défaut,
	 * - `ltrimmed_relative` : chemin relatif sans le slash initial,
	 * - `foldername` : nom du dossier,
	 * - `children` : tableau des sous-dossiers sous le même format (récursif).
	 *
	 * @param string      $path       Chemin relatif à partir du répertoire par défaut. '/' par défaut.
	 * @param string      $excludeDir Nom ou motif des dossiers à exclure. Chaîne vide par défaut.
	 *
	 * @return array Tableau arborescent des dossiers avec informations détaillées et enfants.
	 *
	 * @example
	 * ```php
	 * $tree = $service->getDirsTree('uploads', 'temp');
	 * // Récupère tous les dossiers sous 'uploads' sauf ceux contenant 'temp', avec leurs sous-dossiers.
	 * ```
	 */
	// public function getDirsTree(string $path = '/', string $excludeDir = "", string|null $depth = '== 0'): array
	// public function getDirsTree(string $path = '/', string $excludeDir = "", string|array|null $depth = '== 0'): array
	public function getDirsTree(string $path = '/', string $excludeDir = ""): array
	{
		// $trimedPath = \trim($path, '/');
		// $realPath = \realpath($this->getDefaultDirectory() . '/' . $trimedPath);
		$realPath = \realpath($this->getDefaultDirectory() . '/' . \trim($path, '/'));

		if (!$realPath || !\is_dir($realPath)) {
			return [];
		}

		$finder = new Finder();
		/* if ($depth !== null) {
			$finder->depth($depth); // Search only folders at the given depth $depth
		}
		$finder->directories()->in($realPath); // Search only folders at the root */
		$finder
			->directories()
			->in($realPath)
			->depth('== 0'); // seulement 1er niveau

		$directories = [];

		foreach ($finder as $dir) {
			$dirPath = $dir->getRealPath();

			if ($excludeDir && \str_contains($dirPath, $excludeDir)) {
				continue;
			}

			$relative = \str_replace($this->getDefaultDirectory(), '', $dirPath);
			$children = $this->getDirsTree($relative, $excludeDir);
			$files = $this->getFiles($relative) ?: [];

			$directories[] = [
				'absolute' => $dirPath,
				'relative' => $relative,
				// 'ltrimed_relative' => \ltrim($relative, '/'),
				'ltrimmed_relative' => \ltrim($relative, '/'),
				'foldername' => $dir->getFilename(),
				// appel récursif pour sous-dossiers
				'children' => $children,
				'files' => $files,
				'dirs_length' => \count($children),
				'files_length' => \count($files),
			];
		}

		return $directories;
	}

	/**
	 * Extrait des segments de chemins de répertoires.
	 *
	 * Cette méthode découpe les chemins selon le séparateur '/' et extrait les parties
	 * à partir d'un index donné, avec option de concaténation.
	 *
	 * @param string|array $dirs    Les répertoires en tant que chaîne unique ou tableau de chaînes.
	 * @param int          $slice   Le point de départ pour extraire les parties du répertoire.
	 * @param bool         $implode Détermine si les parties extraites doivent être concaténées en une chaîne.
	 *
	 * @return string|array Les parties extraites des répertoires ou leur concaténation si demandée, ou false si vide.
	 *
	 * @example
	 * ```php
	 * $slice = FileManagerService::getSliceDirs('uploads/images/photo.jpg', 1, true);
	 * // "images/photo.jpg"
	 * ```
	 */
	public static function getSliceDirs(string|array $dirs, int $slice, bool $implode = false): string|array
	{
		/* if (is_array($dirs)) {
			$tree_structure = [];

			foreach ($dirs as $dir) {
				// Divise le répertoire en parties en utilisant le séparateur "/" et extrait les parties à partir de l'index $slice
				$tree_structure[] = array_slice(explode("/", $dir), $slice);
			}
		} else {
			$tree_structure = array_slice(explode("/", $dirs), $slice);
		}

		// Si l'option $implode est activée et la structure n'est pas vide
		if ($implode === true && !empty($tree_structure)) {
			if (is_array($dirs)) {
				$tree_structure_imploded = [];

				// Parcourt chaque structure d'arbre et concatène les parties en utilisant le séparateur "/"
				foreach ($tree_structure as $implode_structure) {
					$tree_structure_imploded[] = implode("/", $implode_structure);
				}
			} else {
				$tree_structure_imploded = "/". implode("/", $tree_structure);
			}

			return $tree_structure_imploded;
		}

		return empty($tree_structure) ? false : $tree_structure; */
		return 'getSliceDirs';
	}

	/**
	 * Nettoie un répertoire en supprimant les dossiers vides de manière récursive.
	 *
	 * Cette méthode remonte l'arborescence à partir d'un dossier cible et supprime
	 * chaque répertoire parent s'il ne contient plus aucun fichier ou sous-dossier.
	 *
	 * @param string $dir Chemin relatif du répertoire à nettoyer. Par défaut,
	 *                    le répertoire relatif courant est utilisé.
	 *
	 * @return void
	 *
	 * @example
	 * ```php
	 * $service->cleanDir('uploads/temp');
	 * // Supprime 'uploads/temp' s’il est vide, puis remonte et supprime
	 * // ses parents si eux aussi sont vides.
	 * ```
	 */
	public function cleanDir(?string $dir = null): void
	// public function cleanDir(string $dir = ''): void
	{
		/* // 🔥 sauvegarde de l'état
		$originalDefault = $this->getDefaultDirectory();
		$originalRelative = $this->getRelativeDirectory(); */

		if (\is_null($dir)/*  || empty($dir) */ || $dir === '') {
		// if (empty($dir)) {
			$dir = $this->getRelativeDirectory();
		}

		// Définit le chemin du répertoire pour les méthodes getDirs et getFiles
		$this->setDefaultDirectory($dir);

		// Récupère les sous-dossiers et fichiers
		$dirs = $this->getDirs();
		$files = $this->getFiles();

		// $dirs = $this->getDirs($dir);
		// $files = $this->getFiles($dir);

		// Si aucun fichier et aucun sous-dossier trouvé, supprime le répertoire
		if (empty($files) && empty($dirs)) {
			$this->remove();

			// Appelle récursivement la fonction sur le dossier parent
			$parentDir = \dirname($dir);
			if ($parentDir !== $dir) { // Évite la récursion infinie
				$this->cleanDir($parentDir);
			}
		}

		/* // 🔥 restauration de l'état
		$this->defaultDirectory = $originalDefault;
		$this->relativeDirectory = $originalRelative; */
	}

	/**
	 * Récupère les fichiers d’un répertoire avec des filtres optionnels.
	 *
	 * Cette méthode liste les fichiers situés sous un chemin donné, permettant de filtrer
	 * par profondeur, par nom de dossier ou par extension. Elle retourne des métadonnées détaillées.
	 *
	 * @param string            $path  Chemin relatif à partir du répertoire par défaut. '/' par défaut.
	 * @param string|array|null $depth Profondeur des fichiers à récupérer. '== 0' par défaut.
	 * @param string|null       $folder Filtre optionnel sur le chemin des fichiers.
	 * @param string|null       $ext    Filtre optionnel sur l'extension des fichiers.
	 *
	 * @return array|bool Tableau des fichiers avec informations détaillées, ou false si aucun fichier trouvé.
	 *
	 * @example
	 * ```php
	 * $files = $service->getFiles('uploads', '== 1');
	 * // Récupère tous les fichiers situés à profondeur 1 sous 'uploads'
	 * ```
	 */
	// public function getFiles(string $path = '/', ?string $depth = '== 0', ?string $folder = null, ?string $ext = null): array|bool
	public function getFiles(string $path = '/', string|array|null $depth = '== 0', ?string $folder = null, ?string $ext = null): array|bool
	{
		// $trimedPath = \trim($path, '/');
		// $realPath = \realpath(\rtrim($this->getDefaultDirectory(), '/') . '/' . $trimedPath);
		$realPath = \realpath(\rtrim($this->getDefaultDirectory(), '/') . '/' . \trim($path, '/'));
		// dd($realPath);
		if (!$realPath || !is_dir($realPath)) {
			return false;
		}

		$finder = new Finder();
		// if ($depth) {
		if ($depth !== null) {
			$finder->depth($depth); // $finder->depth(['== 0']);
		}
		$finder->files()->in($realPath); // $finder->files()->in($realPath)->depth('== 0');

		if ($folder) {
			// $finder->path('data'); // matches files that contain "data" anywhere in their paths (files or directories)
			// $finder->path('data')->name('*.xml'); // for example this will match data/*.xml and data.xml if they exist
			// $finder->path('foo/bar');
			// $finder->path('/^foo\/bar/');
			$finder->path($folder);
		}

		if ($ext) {
			// $finder->files()->name('*.php');
			// $finder->files()->name('/\.php$/');
			// $finder->files()->name('/\.php$/');
			// $finder->files()->name('*.php')->name('*.twig');
			// $finder->files()->name(['*.php', '*.twig']);
			$finder->files()->name("*.{$ext}");
		}


		if (!$finder->hasResults()) {
			return false;
			// return []; // pourquoi false ? Ça, c’est le truc qui va faire chier un jour.
		}

		$fileList = [];

		foreach ($finder as $file) {
			$fileList[] = $this->getFileInfo($file);
		}

		return $fileList;
	}

	/**
	 * Récupère les dimensions (largeur et hauteur) d’un fichier image.
	 *
	 * Cette méthode extrait la résolution d'une image stockée localement,
	 * en gérant la résolution automatique des chemins absolus ou relatifs.
	 *
	 * @param string $filePath Chemin du fichier image (relatif ou absolu).
	 *
	 * @return array|null Tableau associatif ['width' => int, 'height' => int] si l’image existe, ou null sinon.
	 *
	 * @example
	 * ```php
	 * $size = $service->getImageSize('uploads/photo.jpg');
	 * // $size = ['width' => 800, 'height' => 600]
	 *
	 * // Le chemin absolu est détecté automatiquement
	 * $sizeAbsolute = $service->getImageSize('/var/www/images/photo.jpg');
	 * ```
	 */
	// public function getImageSize(string $filePath, bool $absolute = false): ?array
	public function getImageSize(string $filePath): ?array
	{
		/* if ($absolute) {
			$imageSize = @\getimagesize($filePath);
		} else {
			// $imageSize = @\getimagesize($this->getKernelDirectory() . $filePath);
			$imageSize = @\getimagesize($this->abs($filePath));
		} */
		// $imageSize = ($absolute)
		$imageSize = ($this->isAbsolute($filePath))
			? @\getimagesize($filePath)
			: @\getimagesize($this->abs($filePath));

		if ($imageSize) {
			return [
				'width' => $imageSize[0] ?? null,
				'height' => $imageSize[1] ?? null
			];
		} else {
			return null;
			/* return [
				'width' => null,
				'height' => null
			]; */
		}
	}

	/**
	 * Compile des informations détaillées pour un fichier donné.
	 *
	 * Cette méthode extrait les métadonnées techniques (taille, modification, dimensions image,
	 * type MIME, etc.) à partir d'un objet `SplFileInfo`.
	 *
	 * @param \SplFileInfo $file Objet représentant le fichier.
	 *
	 * @return array Tableau associatif contenant les informations détaillées du fichier.
	 *
	 * @example
	 * ```php
	 * $fileInfo = $service->getFileInfo($file);
	 * // [
	 * //   'absolute' => '/var/www/project/uploads/photo.jpg',
	 * //   'relative' => 'uploads/photo.jpg',
	 * //   'filename' => 'photo.jpg',
	 * //   'filesize' => '1.2 MB',
	 * //   'filemtime' => 1698200000,
	 * //   'dimensions' => ['width' => 800, 'height' => 600],
	 * //   'extension' => 'jpg',
	 * //   'mime' => 'image/jpeg'
	 * // ]
	 * ```
	 */
	private function getFileInfo(\SplFileInfo $file): array
	{
		$filePath = $file->getRealPath();
		// $imageSize = @getimagesize($filePath); // Avoid error if it is not an image
		// dd($filePath);

		// dump($this->getKernelDirectory());
		// dump($filePath);
		// print_r($filePath);
		// echo "<pre>";
		// print_r($this->getRelativeDirectory());
		// echo "</pre>";
		// dd($this->getRelativeDirectory());
		// dd($this->getParameter('kernel.project_dir'));
		// dd($file);


		return [
			'absolute' => $filePath,
			'relative' => \substr($filePath, \strlen($this->getKernelDirectory() . $this->getRelativeDirectory())), // 'relative' => str_replace($this->getKernelDirectory() . $this->getRelativeDirectory(), '', $filePath), // 'relative' => strstr($filePath, $this->getRelativeDirectory(), false),
			'absolute_dir' => $this->getDefaultDirectory(),
			'relative_dir' => $this->getRelativeDirectory(),
			'dirname' => \basename(\dirname($filePath)),
			'filename' => $file->getFilename(),
			'filesize' => $this->getSizeName($file->getSize()),
			'filemtime' => $file->getMTime(),
			/* 'dimensions' => [
				'width' => $imageSize[0] ?? null,
				'height' => $imageSize[1] ?? null
			], */
			'dimensions' => $this->getDimensionsFileInfo($filePath),
			'extension' => $file->getExtension(),
			// 'mime' => \mime_content_type($file->getPathname()) // 'mime' => $imageSize['mime'] ?? null
			// 'mime' => $this->mime->guessMimeType($file->getPathname())
			// 'mime' => $this->getMimeContent($file->getPathname(), true)
			'mime' => $this->getMimeContent($file->getPathname())
		];
	}

	/**
	 * Récupère les dimensions brutes d'un fichier image.
	 *
	 * Cette méthode fournit la largeur et la hauteur en pixels. Les valeurs 
	 * sont retournées comme `null` si le fichier n'est pas une image valide.
	 *
	 * @param string $filePath Chemin absolu vers le fichier image.
	 *
	 * @return array Tableau associatif avec les clés :
	 *               - 'width'  => int|null Largeur de l’image en pixels
	 *               - 'height' => int|null Hauteur de l’image en pixels
	 *
	 * @example
	 * ```php
	 * $dimensions = $service->getDimensionsFileInfo('/var/www/uploads/photo.jpg');
	 * // ['width' => 800, 'height' => 600]
	 * ```
	 */
	private function getDimensionsFileInfo(string $filePath): array
	{
		// $filePath = $file->getRealPath();
		$imageSize = @\getimagesize($filePath); // Avoid error if it is not an image

		return [
			'width' => $imageSize[0] ?? null,
			'height' => $imageSize[1] ?? null
		];
	}

	/**
	 * Calcule la taille totale d'un ou plusieurs fichiers en octets.
	 *
	 * @param string|array $files          Un chemin absolu ou un tableau de chemins/métadonnées.
	 * @param int          $totalFileSize  Compteur incrémental initial.
	 *
	 * @return int|float La taille totale en octets.
	 */
	public static function getSize(string|array $files, int $totalFileSize = 0): int|float
	{
		if (\is_string($files)) {
			$totalFileSize = $totalFileSize + filesize($files);
		} else {
			foreach ($files as $size) {
				if (\is_array($size) && $size['absolute']) {
					// dd($size);
					$totalFileSize = $totalFileSize + filesize($size['absolute']);
				} else {
					$totalFileSize = $totalFileSize + filesize($size);
				}
			}
		}

		return $totalFileSize;
	}

	/**
	 * Convertit une taille en octets en un format lisible (o, Ko, Mo, Go).
	 *
	 * Cette méthode formate une valeur numérique en chaîne de caractères avec 
	 * l'unité de mesure la plus appropriée et deux décimales.
	 *
	 * @param int|float $size Taille en octets.
	 *
	 * @return string Taille formatée avec l’unité appropriée.
	 *
	 * @example
	 * ```php
	 * echo $service->getSizeName(500);         // "500 o"
	 * echo $service->getSizeName(2048);        // "2.00 Ko"
	 * echo $service->getSizeName(10485760);    // "10.00 Mo"
	 * echo $service->getSizeName(2147483648);  // "2.00 Go"
	 * ```
	 */
	public function getSizeName(int|float $size): string
	{
		if ($size < 1024) { // Octets
			return "{$size} {$this->unite['o']}"; // return $size . ' ' . $this->unite['o'];
		} else {
			if ($size < 10485760) { // Ko
				$ko = \round($size / 1024, 2);
				return "{$ko} {$this->unite['ko']}"; // return $ko . ' ' . $this->unite['ko'];
			} else {
				if ($size < 1073741824) { // Mo
					$mo = \round($size / (1024 * 1024), 2);
					return "{$mo} {$this->unite['mo']}"; // return $mo . ' ' . $this->unite['mo'];
				} else { // Go
					$go = \round($size / (1024 * 1024 * 1024), 2);
					return "{$go} {$this->unite['go']}"; // return $go . ' ' . $this->unite['go'];
				}
			}
		}
	}

	/**
	 * Transfère un ou plusieurs fichiers vers un répertoire spécifique.
	 *
	 * Cette méthode gère l'upload, le renommage slugifié, et la génération 
	 * de métadonnées pour les fichiers envoyés via une requête HTTP.
	 *
	 * @param UploadedFile|UploadedFile[] $files   Fichier unique ou tableau de fichiers à uploader.
	 * @param string                      $folder  Dossier cible où les fichiers seront uploadés (chemin absolu recommandé).
	 * @param string                      $newName Nouveau nom de fichier optionnel (pour plusieurs fichiers, un suffixe sera ajouté).
	 * @param bool                        $return  Si `true`, retourne un tableau détaillé des fichiers uploadés ; sinon retourne `true`.
	 *
	 * @return array|bool Tableau d’informations des fichiers uploadés si `$return` est `true`, sinon `true`.
	 *
	 * @throws \Exception Si un fichier n’a pas pu être déplacé dans le dossier cible.
	 *
	 * @example
	 * ```php
	 * $uploaded = $service->upload($file, '/var/www/uploads', 'nouveau-nom', true);
	 * // [
	 * //   [
	 * //     'absolute' => '/var/www/uploads/nouveau-nom.jpg',
	 * //     'relative' => 'uploads/nouveau-nom.jpg',
	 * //     'filename' => 'nouveau-nom.jpg',
	 * //     'filesize' => '1.2 MB',
	 * //     'filemtime' => 1698200000,
	 * //     'extension' => 'jpg',
	 * //     'mime' => 'image/jpeg',
	 * //     'dimensions' => ['width' => 800, 'height' => 600]
	 * //   ]
	 * // ]
	 * ```
	 */
	public function upload(UploadedFile|array $files, string $folder, string $newName = "", bool $return = false): array|bool
	{
		$uploadedFiles = [];
		$multiple = null;

		// Check if $files is an array (multiple upload) or a single file
		$files = \is_array($files)
			? $files
			: [$files];

		if (!empty($newName) && \count($files) > 1) {
			$multiple = true;
		}

		foreach ($files as $key => $file) {
			/* $filename = $this->createSlug($file->getClientOriginalName());
			$filename = str_replace('-' . $file->getClientOriginalExtension(), '.' . $file->getClientOriginalExtension(), $filename); */
			// $fileInfo = pathinfo($file->getClientOriginalName());
			// $filename = $this->createSlug($fileInfo['filename']) . '.' . strtolower($fileInfo['extension']);
			/* if (!empty($newName)) {
				$fileInfo = [
					// 'filename' => ($multiple) ? $newName . '-' . ($key + 1) : $newName,
					'filename' => ($multiple) ? "{$newName}-{($key + 1)}" : $newName,
					'extension' => $file->getClientOriginalExtension()
				];
			} else {
				$fileInfo = \pathinfo($file->getClientOriginalName());
			} */
			$fileInfo = (!empty($newName))
				? $fileInfo = [
					'filename' => ($multiple)
						// ? "{$newName}-{($key + 1)}"
						? "{$newName}-" . ($key + 1)
						: $newName,
					'extension' => $file->getClientOriginalExtension()
				]
				: \pathinfo($file->getClientOriginalName());

			$filename = $this->createSlug($fileInfo['filename']) . '.' . \strtolower($fileInfo['extension']);

			$output = [
				// 'absolute' => $folder . '/' . $filename,
				'absolute' => "{$folder}/{$filename}",
				// 'relative' => \substr($folder . '/' . $filename, \strlen($this->getKernelDirectory() . $this->getRelativeDirectory())), // 'relative' => str_replace($this->getKernelDirectory(), '', $folder . '/' . $filename),
				'relative' => \substr("{$folder}/{$filename}", \strlen($this->getKernelDirectory() . $this->getRelativeDirectory())), // 'relative' => str_replace($this->getKernelDirectory(), '', $folder . '/' . $filename),
				'filename' => $filename,
				'filesize' => $this->getSizeName($file->getSize()),
				'filemtime' => $file->getMTime(),
				'extension' => (!empty($file->getExtension()))
					? $file->getExtension()
					: \pathinfo($filename, PATHINFO_EXTENSION),
				// 'mime' => \mime_content_type($file->getPathname())
				// 'mime' => $this->getMimeContent($file->getPathname(), true)
				// 'mime' => $this->getMimeContent($file->getPathname())
			];

			// Upload file
			if (!$file->move($folder, $filename)) {
				// throw new \Exception("A problem occurred while uploading this file: " . $filename);
				throw new \Exception("A problem occurred while uploading this file: {$filename}");
			}

			/* $imageSize = @getimagesize($folder . '/' . $filename); // Avoid error if it is not an image
			$output['dimensions'] = [
				'width' => $imageSize[0] ?? null,
				'height' => $imageSize[1] ?? null
			]; */
			// $output['dimensions'] = $this->getDimensionsFileInfo($folder . '/' . $filename);
			$output['mime'] = $this->getMimeContent("{$folder}/{$filename}");
			$output['dimensions'] = $this->getDimensionsFileInfo("{$folder}/{$filename}");

			$uploadedFiles[] = $output;
		}
		// dd($uploadedFiles);
		return ($return) ? $uploadedFiles : true;
	}

	/**
	 * Redimensionne une liste d'images et les enregistre dans une destination.
	 *
	 * Cette méthode traite les images sources pour ajuster leur largeur tout en 
	 * conservant le ratio, gère la qualité et peut ajouter un suffixe aux noms créés.
	 *
	 * @param array       $files    Liste des noms de fichiers image à redimensionner.
	 * @param string      $sourceDir Répertoire source contenant les images à redimensionner.
	 * @param string      $targetDir Répertoire cible où les images redimensionnées seront enregistrées.
	 * @param int         $width     Largeur souhaitée pour les images redimensionnées.
	 * @param int         $quality   Qualité de l'image redimensionnée (uniquement pour JPEG/PNG/WEBP).
	 * @param string|null $suffix    Suffixe optionnel à ajouter au nom du fichier (ex: 'fhd', '4k').
	 *
	 * @throws \Exception En cas d'erreur lors du traitement des images.
	 *
	 * @return array Tableau contenant les clés 'success' (détails des images traitées) et 'errors' (messages d'erreur).
	 *
	 * @example
	 * ```php
	 * $result = $service->resizeImages(['img1.jpg', 'img2.png'], '/source', '/target', 800, 90, 'thumb');
	 * ```
	 */
	public function resizeImages(array $files, string $sourceDir, string $targetDir, int $width, int $quality = 100, ?string $suffix = null): array
	{
		if ($width <= 0 || $quality <= 0 || $quality > 100) {
			throw new \InvalidArgumentException("Les valeurs de largeur et de qualité doivent être valides.");
		}

		$errors = [];
		$processed = [];

		// Créer le répertoire cible s'il n'existe pas
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

				// Ne pas redimensionner si la largeur cible est >= à la largeur originale
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

				// Générer le nouveau nom de fichier avec suffixe
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

				// Récupérer les informations complètes du fichier redimensionné
				$fileDetails = [
					'absolute' => $targetPath,
					'relative' => substr($targetPath, strlen($this->getKernelDirectory() . $this->getRelativeDirectory())),
					'filename' => $newFilename,
					'filesize' => $this->getSizeName(filesize($targetPath)),
					'filemtime' => filemtime($targetPath),
					'extension' => $fileInfo['extension'],
					'mime' => $this->getMimeContent($targetPath),
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
	// public static function resizeImages(array $file, string $sourceDir, string $targetDir, int $width, int $quality = 100): array|bool
	// {
	// 	/* if ($width <= 0 || $quality <= 0 || $quality > 100) {
	// 		throw new \InvalidArgumentException("Les valeurs de largeur et de qualité doivent être valides.");
	// 	}

	// 	$errors = [];
	// 	$processed = [];

	// 	foreach ($files as $file) {
	// 		try {
	// 			$image_path = $sourceDir . '/' . $file;

	// 			if (!file_exists($image_path)) {
	// 				throw new \Exception("Le fichier image n'existe pas : " . $file);
	// 			}

	// 			$info = getimagesize($image_path);
	// 			if (!$info) {
	// 				throw new \Exception("Le fichier image est corrompu ou n'est pas une image : " . $file);
	// 			}

	// 			$mime = $info['mime'];
	// 			$type = strtolower(substr($mime, strpos($mime, '/') + 1));

	// 			// if (!in_array($type, ['jpeg', 'jpg', 'png'])) {
	// 			if (!in_array($type, ['jpeg', 'jpg', 'png', 'webp'])) {
	// 				throw new \Exception("Le format de fichier image n'est pas supporté : " . $file);
	// 			}

	// 			$old_width = $info[0];
	// 			$old_height = $info[1];

	// 			$ratio = $width / $old_width;
	// 			$new_width = $width;
	// 			$new_height = intval($ratio * $old_height);

	// 			if ($old_width > 9000 || $old_height > 9000) {
	// 				throw new \Exception("Le fichier image est trop grand : " . $old_width . "x" . $old_height);
	// 			}

	// 			switch ($type) {
	// 				case 'jpg':
	// 				case 'jpeg':
	// 					$source = imagecreatefromjpeg($image_path);
	// 					break;
	// 				case 'webp':
	// 					$source = imagecreatefromwebp($image_path);
	// 					break;
	// 				case 'png':
	// 					$source = imagecreatefrompng($image_path);
	// 					imagealphablending($source, false);
	// 					imagesavealpha($source, true);
	// 					break;
	// 			}

	// 			$output_image = imagecreatetruecolor($new_width, $new_height);
	// 			if ($type === 'png') {
	// 				imagealphablending($output_image, false);
	// 				imagesavealpha($output_image, true);
	// 			}

	// 			if (imagecopyresampled($output_image, $source, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height)) {
	// 				// if (!is_dir($targetDir)) {
	// 				// 	mkdir($targetDir, 0777, true);
	// 				// }

	// 				switch ($type) {
	// 					case 'jpg':
	// 					case 'jpeg':
	// 						imagejpeg($output_image, $targetDir . '/' . $file, $quality);
	// 						break;
	// 					case 'webp':
	// 						imagewebp($output_image, $targetDir . '/' . $file, $quality);
	// 						break;
	// 					case 'png':
	// 						imagepng($output_image, $targetDir . '/' . $file, (int)((9 - ($quality / 100) * 9)));
	// 						break;
	// 				}
	// 			} else {
	// 				throw new \Exception("Impossible de redimensionner l'image : " . $file);
	// 			}

	// 			imagedestroy($source);
	// 			imagedestroy($output_image);

	// 			$processed[] = $file;
	// 		} catch (\Exception $e) {
	// 			$errors[] = $e->getMessage();
	// 		}
	// 	}

	// 	// return ['success' => $processed, 'errors' => $errors];
	// 	return true; */
	// 	return ['resizeImages'];
	// 	if ($width <= 0 || $quality <= 0 || $quality > 100) {
	// 		throw new \InvalidArgumentException("Les valeurs de largeur et de qualité doivent être valides.");
	// 	}

	// 	$errors = [];
	// 	$processed = [];

	// 	// foreach ($files as $file) {
	// 		[$originalWidth, $originalHeight] = getimagesize($file);
	// 		$newHeight = (int) round(($width / $originalWidth) * $originalHeight);

	// 		$canvas = imagecreatetruecolor($width, $newHeight);

	// 		switch ($imageType) {
	// 			case IMAGETYPE_PNG:
	// 				$source = imagecreatefrompng($file);
	// 				break;
	// 			case IMAGETYPE_JPEG:
	// 				$source = imagecreatefromjpeg($file);
	// 				break;
	// 			case IMAGETYPE_WEBP:
	// 				$source = imagecreatefromwebp($file);
	// 				break;
	// 			default:
	// 				return null;
	// 		}

	// 		imagecopyresampled(
	// 			$canvas,
	// 			$source,
	// 			0, 0, 0, 0,
	// 			$width,
	// 			$newHeight,
	// 			$originalWidth,
	// 			$originalHeight
	// 		);

	// 		$filename = pathinfo($file, PATHINFO_FILENAME);
	// 		$newPath = $targetDir.'/'.$filename.'-'.$label;

	// 		switch ($imageType) {
	// 			case IMAGETYPE_PNG:
	// 				$newPath .= '.png';
	// 				$compression = 9 - round(($quality / 100) * 9);
	// 				imagepng($canvas, $newPath, $compression);
	// 				break;

	// 			case IMAGETYPE_JPEG:
	// 				$newPath .= '.jpg';
	// 				imagejpeg($canvas, $newPath, $quality);
	// 				break;

	// 			case IMAGETYPE_WEBP:
	// 				$newPath .= '.webp';
	// 				imagewebp($canvas, $newPath, $quality);
	// 				break;
	// 		}

	// 		imagedestroy($canvas);
	// 		imagedestroy($source);

	// 		// $processed[] = $file;
	// 	// }

	// 	return $newPath;
	// }

	/**
	 * Vérifie si le répertoire par défaut contient au moins un sous-dossier.
	 *
	 * Cette méthode contrôle la présence de dossiers enfants au sein du 
	 * répertoire de travail actuel.
	 *
	 * @return bool `true` si au moins un sous-dossier existe, sinon `false`.
	 *
	 * @example
	 * ```php
	 * if ($service->hasDir()) {
	 *     echo "Il y a des sous-dossiers dans le répertoire par défaut.";
	 * } else {
	 *     echo "Aucun sous-dossier trouvé.";
	 * }
	 * ```
	 */
	public function hasDir(): bool
	{
		$dirs = $this->getDirs();

		/* if (empty($dirs)) {
			// dd('return false');
			return false;
		} else {
			// dd('return true');
			return true;
		} */
		return !empty($dirs);
	}

	/* **************************************************************************************************************************************************************** */
	/**
	 * Initialise le téléchargement d'un fichier unique ou délégué.
	 *
	 * Cette méthode prépare une réponse Symfony `BinaryFileResponse` pour forcer 
	 * le téléchargement d'un élément. Si l'élément n'est pas un fichier simple,
	 * elle bascule automatiquement sur un téléchargement groupé.
	 *
	 * @param string      $name       Nom du fichier à télécharger.
	 * @param string|null $directory  Sous-répertoire optionnel dans lequel se trouve le fichier.
	 *
	 * @return BinaryFileResponse     Réponse Symfony configurée pour forcer le téléchargement.
	 *
	 * @throws \RuntimeException      Si le fichier est introuvable.
	 *
	 * @example
	 * ```php
	 * // Téléchargement d'un fichier unique
	 * return $service->download('document.pdf');
	 *
	 * // Téléchargement d'un fichier dans un sous-dossier
	 * return $service->download('image.png', 'uploads/images');
	 * ```
	 */
	public function download(string $name, ?string $directory = null): BinaryFileResponse
	{
		// if (!\is_file($name)) {
			return $this->downloadBulk([$name], $directory);
		// }

		/* // Si aucun répertoire n'est fourni, on prend le defaultDirectory
		$baseDir = $directory
			? $this->getDefaultDirectory() . DIRECTORY_SEPARATOR . \ltrim($directory, DIRECTORY_SEPARATOR)
			: $this->getDefaultDirectory();

		$filePath = $baseDir . DIRECTORY_SEPARATOR . $name;
		// dump($this->getKernelDirectory()); */

		/* if (\is_dir($filePath)) {
			// dump(\is_dir($filePath));
			$tmpDir = $this->getKernelDirectory() . '/var/tmp';
			if (!\is_dir($tmpDir)) {
				\mkdir($tmpDir, 0775, true);
			}

			$zipName = \basename($filePath) . '.zip';
			$zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;

			$zip = new \ZipArchive();
			if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
				throw new \RuntimeException("Impossible de créer l'archive ZIP : {$zipName}");
			}

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($filePath, \RecursiveDirectoryIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::SELF_FIRST
			);
			// dump($zipName);
			// dump(\basename($zipName));
			// dd($iterator);

			foreach ($iterator as $item) {
				$localPath = \substr($item->getRealPath(), \strlen($filePath) + 1);

				if ($item->isDir()) {
					$zip->addEmptyDir($localPath);
				} else {
					$zip->addFile($item->getRealPath(), $localPath);
				}
			}

			$zip->close();

			if (!\file_exists($zipPath)) {
				throw new \RuntimeException(
					\sprintf("Le fichier \"%s\" est introuvable.", \str_replace($this->getDefaultDirectory(), "", $zipPath))
				);
			}

			$response = new BinaryFileResponse($zipPath);
			$response->setContentDisposition(
				ResponseHeaderBag::DISPOSITION_ATTACHMENT,
				$zipName
			);

			// Supprime le fichier ZIP après envoi (clean)
			$response->deleteFileAfterSend(true);

			return $response;
		} */
		// dump(is_file($filePath));
		// dump($this->getDefaultDirectory());
		// dd($filePath);

		/* if (!\file_exists($filePath)) {
			throw new \RuntimeException(
				\sprintf("Le fichier \"%s\" est introuvable.", \str_replace($this->getDefaultDirectory(), "", $filePath))
			);
		}

		$response = new BinaryFileResponse($filePath);
		$response->setContentDisposition(
			ResponseHeaderBag::DISPOSITION_ATTACHMENT,
			\basename($filePath)
		);

		return $response; */
	}

	/**
	 * Regroupe et télécharge plusieurs fichiers sous forme d'archive ZIP.
	 *
	 * Cette méthode crée une archive temporaire contenant l'ensemble des fichiers 
	 * et dossiers demandés, puis génère une réponse de téléchargement groupé.
	 *
	 * @param array       $names      Liste des noms de fichiers ou dossiers à inclure.
	 * @param string|null $directory  Sous-répertoire optionnel contenant les éléments.
	 *
	 * @return BinaryFileResponse     Réponse Symfony contenant l’archive ZIP à télécharger.
	 *
	 * @throws \RuntimeException      Si un élément est introuvable ou si l’archive ZIP
	 *                                ne peut pas être créée.
	 *
	 * @example
	 * ```php
	 * // Téléchargement de plusieurs fichiers
	 * return $service->downloadBulk(
	 *     ['document.pdf', 'image.png'],
	 *     'uploads/documents'
	 * );
	 * ```
	 */
	public function downloadBulk(array $names, ?string $directory = null): BinaryFileResponse
	{
		$baseDir = $directory
			? $this->getDefaultDirectory() . DIRECTORY_SEPARATOR . ltrim($directory, DIRECTORY_SEPARATOR)
			: $this->getDefaultDirectory();

		$paths = [];

		foreach ($names as $name) {
			$path = $baseDir . DIRECTORY_SEPARATOR . $name;

			if (!\file_exists($path)) {
				throw new \RuntimeException(
					\sprintf("`%s` est introuvable.", $name)
				);
			}

			$paths[] = $path;
		}

		[$finalPath, $finalName, $deleteAfter] = $this->prepareDownload($paths, $baseDir);

		$response = new BinaryFileResponse($finalPath);
		$response->setContentDisposition(
			ResponseHeaderBag::DISPOSITION_ATTACHMENT,
			$finalName
		);

		if ($deleteAfter) {
			$response->deleteFileAfterSend(true);
		}

		return $response;
	}

	/**
	 * Prépare la liste des chemins pour une mise en archive ou un envoi direct.
	 *
	 * cette méthode résout les destinations finales et organise la création 
	 * d'un fichier ZIP si plusieurs éléments sont impliqués.
	 *
	 * @param array  $paths    Liste des chemins absolus des fichiers ou dossiers à traiter.
	 * @param string $baseDir  Répertoire de base utilisé pour la résolution des chemins.
	 *
	 * @return array{
	 *     0: string,  // Chemin du fichier final (fichier ou ZIP)
	 *     1: string,  // Nom du fichier à présenter au téléchargement
	 *     2: bool     // Indique si le fichier doit être supprimé après l’envoi
	 * }
	 *
	 * @throws \RuntimeException Si l’archive ZIP ne peut pas être créée.
	 */
	private function prepareDownload(array $paths, string $baseDir): array
	{
		// Cas simple : un seul fichier
		if (\count($paths) === 1 && \is_file($paths[0])) {
			return [
				$paths[0],
				\basename($paths[0]),
				false,
			];
		}

		// Sinon → ZIP
		$tmpDir = $this->getKernelDirectory() . '/var/tmp';
		if (!\is_dir($tmpDir)) {
			\mkdir($tmpDir, 0775, true);
		}

		$zipName = 'download_' . \date('Ymd_His') . '.zip';
		$zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;

		$zip = new \ZipArchive();
		if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
			throw new \RuntimeException('Impossible de créer l’archive ZIP.');
		}

		foreach ($paths as $path) {
			if (\is_dir($path)) {
				$this->addDirectoryToZip($zip, $path, \basename($path));
			} else {
				$zip->addFile($path, \basename($path));
			}
		}

		$zip->close();

		return [$zipPath, $zipName, true];
	}

	/**
	 * Ajoute récursivement le contenu d'un dossier dans une archive ZIP.
	 *
	 * Cette méthode parcourt l'arborescence complète d'un dossier pour inclure 
	 * tous ses fichiers et sous-dossiers au sein du fichier compressé.
	 *
	 * @param \ZipArchive $zip       Instance de l’archive ZIP cible.
	 * @param string      $dir       Chemin absolu du dossier à ajouter.
	 * @param string      $baseName  Nom racine utilisé dans l’archive ZIP.
	 *
	 * @return void
	 */
	private function addDirectoryToZip(\ZipArchive $zip, string $dir, string $baseName): void
	{
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);
		// dd($iterator);

		foreach ($iterator as $item) {
			// $localPath = $baseName . '/' . \substr($item->getRealPath(), \strlen($dir) + 1);
			$localPath = $baseName . DIRECTORY_SEPARATOR . \substr($item->getRealPath(), \strlen($dir) + 1);

			if ($item->isDir()) {
				$zip->addEmptyDir($localPath);
			} else {
				$zip->addFile($item->getRealPath(), $localPath);
			}
		}
	}
	/* **************************************************************************************************************************************************************** */

	/**
	 * Supprime définitivement un fichier ou un répertoire.
	 *
	 * Cette méthode efface l'élément ciblé du système de fichiers. Pour un 
	 * dossier, la suppression porte sur l'ensemble de son contenu.
	 *
	 * @param string $relativePath Chemin relatif du fichier ou du sous-répertoire à supprimer.
	 *                             Si vide, le répertoire par défaut entier sera supprimé.
	 *
	 * @return bool `true` si la suppression a réussi, `false` sinon.
	 *
	 * @example
	 * ```php
	 * // Supprimer un fichier spécifique
	 * $success = $service->remove('uploads/documents/file.txt');
	 *
	 * // Supprimer tout le répertoire par défaut
	 * $success = $service->remove();
	 * ```
	 */
	public function remove(string $relativePath = ''): bool
	{
		if (empty($relativePath)) {
			$this->filesystem->remove($this->getDefaultDirectory());

			// if ($this->exists($this->getDefaultDirectory(), true)) {
			if ($this->exists($this->getDefaultDirectory())) {
				return false;
			} else {
				return true;
			}
		} else {
			$path = \ltrim($relativePath, '/');
			// $this->filesystem->remove($this->getDefaultDirectory() . '/' . $relativePath);
			// $this->filesystem->remove("{$this->getDefaultDirectory()}/{$relativePath}");
			$this->filesystem->remove("{$this->getDefaultDirectory()}/{$path}");

			if ($this->exists($relativePath)) {
				return false;
			} else {
				return true;
			}
		}
		// $this->filesystem->remove($this->getDefaultDirectory() . '/' . $relativePath);

		/* if ($this->exists($relativePath)) {
			return false;
		} else {
			return true;
		} */
	}

	/**
	 * Copie un fichier ou un répertoire vers une nouvelle destination.
	 *
	 * Cette méthode duplique l'élément source vers la cible spécifiée, avec 
	 * une option pour écraser le fichier de destination s'il existe déjà.
	 *
	 * @param string $source      Chemin relatif du fichier source à copier.
	 * @param string $destination Chemin relatif de destination pour le fichier copié.
	 * @param bool   $override    Si `true`, écrase le fichier de destination s'il existe déjà.
	 *
	 * @return bool `true` si la copie est effectuée avec succès.
	 *
	 * @example
	 * ```php
	 * // Copier un fichier existant dans le répertoire 'uploads' vers 'backup'
	 * $service->copy('uploads/file.txt', 'backup/file.txt');
	 *
	 * // Copier et écraser si le fichier existe déjà
	 * $service->copy('uploads/file.txt', 'backup/file.txt', true);
	 * ```
	 */
	public function copy(string $source, string $destination, bool $override = false): bool
	{
		// $this->filesystem->copy($this->getKernelDirectory() . $source, $this->getKernelDirectory() . $destination, $override);
		// $this->filesystem->copy("{$this->getKernelDirectory()}{$source}", "{$this->getKernelDirectory()}{$destination}", $override);
		$this->filesystem->copy("{$this->getKernelDirectory()}{$source}", $this->abs($destination), $override);
		return true;
	}

	/**
	 * Renomme un fichier ou un répertoire au sein du répertoire par défaut.
	 *
	 * Cette méthode modifie le nom d'un élément sur le disque en appliquant 
	 * automatiquement une slugification pour garantir un nom URL-safe.
	 *
	 * @param string $source      Nom du fichier/répertoire source (relatif au defaultDirectory).
	 * @param string $destination Nouveau nom (sera slugifié, sans extension).
	 * @param bool   $override    Si true, écrase le fichier de destination s'il existe (par défaut: false).
	 *
	 * @return bool True si le renommage a réussi, false sinon.
	 *
	 * @example
	 * ```php
	 * // Renommer un fichier (l'extension est préservée automatiquement)
	 * $service->setDefaultDirectory('/uploads/images');
	 * $service->rename('Photo Vacances.jpg', 'nouvelle photo');
	 * // Résultat : photo-vacances.jpg → nouvelle-photo.jpg
	 *
	 * // Renommer avec des caractères spéciaux (slugification automatique)
	 * $service->rename('image 2024.png', 'Mon Été à Paris');
	 * // Résultat : image-2024.png → mon-ete-a-paris.png
	 *
	 * // Renommer un répertoire
	 * $service->rename('old folder', 'new folder');
	 * // Résultat : old-folder → new-folder
	 *
	 * // Renommer avec écrasement
	 * $service->rename('temp.jpg', 'final', true);
	 * // Écrasera final.jpg s'il existe déjà
	 * ```
	 */
	public function rename(string $source, string $destination, bool $override = false): bool
	{
		$ext = \pathinfo($source, PATHINFO_EXTENSION);
		// dd($ext);
		$slug = $this->createSlug($destination);

		$newSource = (empty($ext))
			? $slug
			: "{$slug}.{$ext}";

		// $path = $this->getDefaultDirectory();
		/* $origin = "{$this->getDefaultDirectory()}/{$source}";
		$target = "{$this->getDefaultDirectory()}/{$slugDestination}.{$ext}"; */
		// dump($origin);
		// dump($target);
		// dd($override);
		/* // renames a file
		$filesystem->rename('/tmp/processed_video.ogg', '/path/to/store/video_647.ogg', false);
		// renames a directory
		$filesystem->rename('/tmp/files', '/path/to/store/files', false); */
		// $this->filesystem->rename($origin, $target, $override);
		$this->filesystem->rename(
			"{$this->getDefaultDirectory()}/{$source}",
			"{$this->getDefaultDirectory()}/{$newSource}",
			$override
		);

		// if (!$this->exists($origin, true)) {
		// if (!$this->exists("{$this->getDefaultDirectory()}/{$source}", true)) {
		if (!$this->exists("{$this->getDefaultDirectory()}/{$source}")) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Déplace un fichier ou un répertoire vers un nouvel emplacement.
	 *
	 * Cette méthode transfère physiquement l'élément de sa source vers sa cible 
	 * avec option d'écrasement, en résolvant les chemins par rapport à la racine.
	 *
	 * @param string $origine   Chemin relatif du fichier/répertoire source.
	 * @param string $target    Chemin relatif du fichier/répertoire de destination.
	 * @param bool   $overwrite Si true, écrase la cible si elle existe déjà (par défaut: false).
	 *
	 * @return bool True si le déplacement a réussi, false sinon.
	 *
	 * @example
	 * ```php
	 * // Déplacer un fichier
	 * $service->move(
	 *     '/uploads/temp/image.jpg',
	 *     '/uploads/user/1/image/image.jpg'
	 * );
	 *
	 * // Renommer un fichier
	 * $service->move(
	 *     '/uploads/image.jpg',
	 *     '/uploads/image-renamed.jpg'
	 * );
	 *
	 * // Déplacer un répertoire
	 * $service->move(
	 *     '/uploads/temp',
	 *     '/uploads/permanent'
	 * );
	 *
	 * // Déplacer avec écrasement
	 * $service->move(
	 *     '/uploads/new.jpg',
	 *     '/uploads/old.jpg',
	 *     true  // Écrasera old.jpg s'il existe
	 * );
	 * ```
	 */
	public function move(string $origine, string $target, bool $overwrite = false): bool
	{
		// $absoluteOrigine = $this->getDefaultDirectory() . '/' . \ltrim($origine, '/');
		$absoluteOrigine = $this->getKernelDirectory() . '/' . \ltrim($origine, '/');
		$absoluteTarget = $this->getKernelDirectory() . '/' . \ltrim($target, '/');

		// dump($this->getRelativeDirectory()); // {filename (ex: img.jpg)} or {directory name (ex: folder)}
		// dump($absoluteOrigine);
		// dump($absoluteTarget);
		// dump($origine); // {filename (ex: img.jpg)} or {directory name (ex: folder)}
		// dump($target); // relative path to the project (ex: /relative/to/the/project/{filename|directory})
		// dd($overwrite);

		$this->filesystem->rename(
			$absoluteOrigine,
			$absoluteTarget,
			$overwrite
		);

		if (!$this->exists($absoluteOrigine) && $this->exists($absoluteTarget)) {
			return true;
		} else {
			return false;
		}
		// $this->rename($origine, $target, $overwrite);
		// return $this->rename($origine, $target, $overwrite);

		/* $origineFile = "/tmp/processed_video.ogg";
		$targetFile = "/path/to/store/video_647.ogg";

		$origineDir = "/tmp/files";
		$targetDir = "/path/to/store/files";

		$overwrite = true; */

		/* // renames a file
		$this->filesystem->rename('/tmp/processed_video.ogg', '/path/to/store/video_647.ogg');
		// renames a directory
		$this->filesystem->rename('/tmp/files', '/path/to/store/files');
		// if the target already exists, a third boolean argument is available to overwrite.
		$this->filesystem->rename('/tmp/processed_video2.ogg', '/path/to/store/video_647.ogg', true);

		// $origine = "";
		// $target = "";
		// $iterator = null;
		// $options = [];
		// $this->filesystem->mirror($origine, $target, $iterator, $options);

		return true; */
	}

	/**
	 * $this->remindDirectoryInitialization();
	 */
	private function remindDirectoryInitialization(): void
	{
		trigger_error(
			"⚠️ Rappel : pensez à initialiser correctement le répertoire cible avec setDefaultDirectory() avant toute opération sur les fichiers.",
			E_USER_NOTICE
		);
	}
}


/* namespace Anfallnorr\FileManagerSystem\Service;

use Anfallnorr\FileManagerSystem\Service\Manager\ArchiveManager;
use Anfallnorr\FileManagerSystem\Service\Manager\DirectoryManager;
use Anfallnorr\FileManagerSystem\Service\Manager\FileManager;
use Anfallnorr\FileManagerSystem\Service\Manager\ImageManager;
use Anfallnorr\FileManagerSystem\Service\Manager\MimeManager;
use Anfallnorr\FileManagerSystem\Service\Manager\PathManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileManagerService
{
	public function __construct(
		private PathManager $pathManager,
		private MimeManager $mimeManager,
		private DirectoryManager $directoryManager,
		private FileManager $fileManager,
		private ImageManager $imageManager,
		private ArchiveManager $archiveManager,
	) {
		$this->directoryManager->setFileManager($this->fileManager);
		$this->directoryManager->setImageManager($this->imageManager);
	}

	// --- Path Management ---

	public function getKernelDirectory(): string
	{
		return $this->pathManager->getKernelDirectory();
	}

	public function getDefaultDirectory(): string
	{
		return $this->pathManager->getDefaultDirectory();
	}

	public function setDefaultDirectory(string $directory): static
	{
		$this->pathManager->setDefaultDirectory($directory);
		return $this;
	}

	public function getRelativeDirectory(): string
	{
		return $this->pathManager->getRelativeDirectory();
	}

	public function setRelativeDirectory(string $directory): static
	{
		$this->pathManager->setRelativeDirectory($directory);
		return $this;
	}

	private function abs(string $relative): string
	{
		return $this->pathManager->abs($relative);
	}

	private function isAbsolute(string $path): bool
	{
		return $this->pathManager->isAbsolute($path);
	}

	// --- Mime Management ---

	public function getMimeTypes(): array
	{
		return $this->mimeManager->getMimeTypes();
	}

	public function getMimeType(string $key): string|array|null
	{
		return $this->mimeManager->getMimeType($key);
	}

	public function getMimeContent(string $filename): string
	{
		return $this->mimeManager->getMimeContent($filename);
	}

	public static function getExtByType(string $type): array
	{
		return MimeManager::getExtByType($type);
	}

	// --- File Management ---

	public function exists(?string $filePath = null): bool
	{
		return $this->fileManager->exists($filePath);
	}

	public function getFileContent(string $relativeFile): string
	{
		return $this->fileManager->getFileContent($relativeFile);
	}

	public function createFile(string $filename, string $content = '<!DOCTYPE html><html lang="en"><body style="background: #ffffff;"></body></html>'): void
	{
		$this->fileManager->createFile($filename, $content);
	}

	public function getFiles(string $path = '/', string|array|null $depth = '== 0', ?string $folder = null, ?string $ext = null): array|bool
	{
		return $this->fileManager->getFiles($path, $depth, $folder, $ext);
	}

	public function getFileInfo(\SplFileInfo $file): array
	{
		return $this->fileManager->getFileInfo($file);
	}

	public function createSlug(string $string): string
	{
		return $this->fileManager->createSlug($string);
	}

	public function upload(UploadedFile|array $files, string $folder, string $newName = "", bool $return = false): array|bool
	{
		return $this->fileManager->upload($files, $folder, $newName, $return);
	}

	public static function categorizeFiles(array $files, bool $basename = false, bool $path = false): array
	{
		// This is a bit tricky for static methods needing services, 
		// but since categorizeFiles was static and didn't really need services in the original (besides other static methods),
		// we can keep the logic or instantiate a manager if really needed.
		// Actually the original was using self::getExtByType and self::getExtractedFolder.
		
		$categories = [
			'documents' => ['src' => [], 'basename' => [], 'path' => []],
			'images'    => ['src' => [], 'basename' => [], 'path' => []],
			'audios'    => ['src' => [], 'basename' => [], 'path' => []],
			'videos'    => ['src' => [], 'basename' => [], 'path' => []],
			'other'     => ['src' => [], 'basename' => [], 'path' => []],
		];

		foreach ($files as $file) {
			$extension = \strtolower((string) \pathinfo($file, PATHINFO_EXTENSION));
			$categorized = false;

			foreach (['documents', 'images', 'audios', 'videos'] as $type) {
				if (\in_array($extension, MimeManager::getExtByType($type), true)) {
					$categories[$type]['src'][] = $file;
					if ($basename) $categories[$type]['basename'][] = \basename($file);
					if ($path) $categories[$type]['path'][] = self::getExtractedFolder($file);
					$categorized = true;
					break;
				}
			}

			if (!$categorized) {
				$categories['other']['src'][] = $file;
				if ($basename) $categories['other']['basename'][] = \basename($file);
				if ($path) $categories['other']['path'][] = self::getExtractedFolder($file);
			}
		}

		return $categories;
	}

	public static function getExtractedFolder(string $folder): string
	{
		return 'getExtractedFolder'; 
	}

	// --- Directory Management ---

	public function createDir(?string $directory = null, bool $returnDetails = false): array|bool
	{
		return $this->directoryManager->createDir($directory, $returnDetails);
	}

	public function getDirs(string $path = '/', string $excludeDir = "", string|array|null $depth = '== 0'): array
	{
		return $this->directoryManager->getDirs($path, $excludeDir, $depth);
	}

	public function getDirsTree(string $path = '/', string $excludeDir = ""): array
	{
		return $this->directoryManager->getDirsTree($path, $excludeDir);
	}

	public function cleanDir(string $dir = ''): void
	{
		$this->directoryManager->cleanDir($dir);
	}

	public function hasDir(): bool
	{
		return $this->directoryManager->hasDir();
	}

	public static function getSliceDirs(string|array $dirs, int $slice, bool $implode = false): string|array|bool
	{
		return DirectoryManager::getSliceDirs($dirs, $slice, $implode);
	}

	// --- Image Management ---

	public function getImageSize(string $filePath): ?array
	{
		return $this->imageManager->getImageSize($filePath);
	}

	public function getDimensionsFileInfo(string $filePath): array
	{
		return $this->imageManager->getDimensionsFileInfo($filePath);
	}

	public static function getSize(string|array $files, int $totalFileSize = 0): int|float
	{
		return ImageManager::getSize($files, $totalFileSize);
	}

	public function getSizeName(int|float $size): string
	{
		return $this->imageManager->getSizeName($size);
	}

	public function resizeImages(array $files, string $sourceDir, string $targetDir, int $width, int $quality = 100, ?string $suffix = null): array
	{
		return $this->imageManager->resizeImages($files, $sourceDir, $targetDir, $width, $quality, $suffix);
	}

	// --- Archive & Download Management ---

	public function download(string $name, ?string $directory = null): BinaryFileResponse
	{
		return $this->archiveManager->download($name, $directory);
	}

	public function downloadBulk(array $names, ?string $directory = null): BinaryFileResponse
	{
		return $this->archiveManager->downloadBulk($names, $directory);
	}

	// --- Compound Operations ---

	public function remove(string $relativePath = ''): bool
	{
		return $this->fileManager->remove($relativePath);
	}

	public function copy(string $source, string $destination, bool $override = false): bool
	{
		return $this->fileManager->copy($source, $destination, $override);
	}

	public function rename(string $source, string $destination, bool $override = false): bool
	{
		return $this->fileManager->rename($source, $destination, $override);
	}

	public function move(string $origine, string $target, bool $overwrite = false): bool
	{
		return $this->fileManager->move($origine, $target, $overwrite);
	}
} */

/* <!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Documentation Service Fichiers</title>
<style>
body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; background: #f9f9f9; }
h1 { color: #2c3e50; }
h2 { color: #34495e; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
pre { background: #ecf0f1; padding: 10px; border-radius: 5px; overflow-x: auto; }
code { color: #c0392b; }
.method { margin-bottom: 30px; padding: 15px; background: #fff; border-radius: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
.params, .return, .example { margin-left: 20px; margin-top: 5px; }
.toggle-btn { cursor: pointer; color: #2980b9; text-decoration: underline; margin-bottom: 5px; display: inline-block; }
.hidden { display: none; }
</style>
<script>
function toggleVisibility(id) {
    const el = document.getElementById(id);
    if(el.classList.contains('hidden')) {
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
}
</script>
</head>
<body>
<h1>Documentation du Service de Gestion de Fichiers</h1>
<p>Liste complète des méthodes avec leurs paramètres, retours et exemples d'utilisation.</p>

<div class="method">
<h2>getDefaultDirectory()</h2>
<div class="params"><strong>Paramètres:</strong> aucun</div>
<div class="return"><strong>Retour:</strong> <code>string</code> Chemin absolu du répertoire par défaut</div>
<div class="example">
<pre><code>$defaultDir = $service->getDefaultDirectory();
echo $defaultDir;
</code></pre>
</div>
</div>

<div class="method">
<h2>setDefaultDirectory(string $directory)</h2>
<div class="params"><strong>Paramètres:</strong></div>
<ul class="params">
<li><code>$directory</code> : Chemin relatif ou absolu à définir</li>
</ul>
<div class="return"><strong>Retour:</strong> <code>self</code> Instance courante</div>
<div class="example">
<pre><code>$service->setDefaultDirectory('/var/www/uploads');
</code></pre>
</div>
</div>

<div class="method">
<h2>getDirs(string $path = '/', string $excludeDir = "", string|null $depth = '== 0')</h2>
<div class="params"><strong>Paramètres:</strong></div>
<ul class="params">
<li><code>$path</code> : chemin relatif où chercher les dossiers</li>
<li><code>$excludeDir</code> : dossier à exclure</li>
<li><code>$depth</code> : profondeur à rechercher (ex. '== 0')</li>
</ul>
<div class="return"><strong>Retour:</strong> <code>array</code> Liste des dossiers trouvés</div>
<div class="example">
<pre><code>$dirs = $service->getDirs('/', 'tmp');
foreach($dirs as $dir){
    echo $dir['foldername'];
}
</code></pre>
</div>
</div>

<div class="method">
<h2>getDirsTree(string $path = '/', string $excludeDir = "", string|null $depth = '== 0')</h2>
<div class="params"><strong>Paramètres:</strong></div>
<ul class="params">
<li><code>$path</code> : chemin relatif où chercher les dossiers</li>
<li><code>$excludeDir</code> : dossier à exclure</li>
<li><code>$depth</code> : profondeur maximale</li>
</ul>
<div class="return"><strong>Retour:</strong> <code>array</code> Arborescence complète</div>
<div class="example">
<pre><code>$tree = $service->getDirsTree('/');
print_r($tree);
</code></pre>
</div>
</div>

<div class="method">
<h2>getFiles(string $path = '/', string|null $depth = '== 0')</h2>
<div class="params"><strong>Paramètres:</strong></div>
<ul class="params">
<li><code>$path</code> : chemin relatif où chercher les fichiers</li>
<li><code>$depth</code> : profondeur à rechercher</li>
</ul>
<div class="return"><strong>Retour:</strong> <code>array|bool</code> Liste des fichiers ou false si aucun</div>
<div class="example">
<pre><code>$files = $service->getFiles('/');
if($files){
    foreach($files as $file){
        echo $file['filename'];
    }
}
</code></pre>
</div>
</div>

<div class="method">
<h2>upload(UploadedFile|array $files, string $folder, string $newName = "", bool $return = false)</h2>
<div class="params"><strong>Paramètres:</strong></div>
<ul class="params">
<li><code>$files</code> : fichier ou tableau de fichiers</li>
<li><code>$folder</code> : dossier cible</li>
<li><code>$newName</code> : nouveau nom de fichier (optionnel)</li>
<li><code>$return</code> : si true, retourne infos détaillées</li>
</ul>
<div class="return"><strong>Retour:</strong> <code>array|bool</code> Tableau d’infos ou true si succès</div>
<div class="example">
<pre><code>$service->upload($_FILES['file'], '/uploads', 'monfichier', true);
</code></pre>
</div>
</div>

<div class="method">
<h2>download(string $filename, ?string $directory = null)</h2>
<div class="params"><strong>Paramètres:</strong></div>
<ul class="params">
<li><code>$filename</code> : nom du fichier à télécharger</li>
<li><code>$directory</code> : sous-dossier optionnel</li>
</ul>
<div class="return"><strong>Retour:</strong> <code>BinaryFileResponse</code> réponse HTTP</div>
<div class="example">
<pre><code>return $service->download('document.pdf', 'docs');
</code></pre>
</div>
</div>

<div class="method">
<h2>downloadBulk(array $filenames, ?string $directory = null)</h2>
<div class="params"><strong>Paramètres:</strong></div>
<ul class="params">
<li><code>$filenames</code> : liste des fichiers</li>
<li><code>$directory</code> : sous-dossier optionnel</li>
</ul>
<div class="return"><strong>Retour:</strong> <code>BinaryFileResponse</code> réponse HTTP ZIP</div>
<div class="example">
<pre><code>return $service->downloadBulk(['file1.pdf','file2.pdf'], 'docs');
</code></pre>
</div>
</div>

<!-- Ajoute ici toutes les autres méthodes de la même manière -->

</body>
</html> */

