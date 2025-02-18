<?php

/**
 * Gestionnaire de fichiers pour récupérer les informations des fichiers et dossiers.
 */

namespace Anfallnorr\FileManagerSystem\Service;

use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;
// use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 * METHODS :
 * @method public getMimeTypes(): array
 * @method public getMimeType(@var string $key): string|array|null
 * @method public createSlug(@var string $text): string
 * @method public createFile(@var string $path, @var string $content = '<!DOCTYPE...'): @return void
 * @method public getDirs(@var string $path = '/', @var string $excludeDir = "", @var string|null $depth = '== 0'): @return array
 * @method public getSliceDirs(@var string|array $dirs, @var int $slice, @var bool $implode = false): @return string|array
 * @method public getFiles(@var string $path = '/', @var string|null $depth = '== 0'): @return array|bool
 * @method public getSize(@var string|array $files, @var int $totalFileSize = 0): @return int|float
 * @method public getSizeName(@var int|float $size): @return string
 * @method public upload(@var UploadedFile|array $files, @var string $folder, @var bool $return = false): @return array|bool
 * @method public resizeImages(@var array $files, @var string $sourceDir, @var string $targetDir, @var int $width, @var int $quality = 100): @return array|bool
 */

class FileManagerService
{
	const EXTENSIONS = array(
		'documents' => array('doc','docx','odf','odp','ods','odt','otf','ppt','csv','pps','pptx','xls','xlsx','rtf','txt','pdf'),
		'images' => array('jpg','jpeg','png','tif','webp','bmp','ico','svg','gif'),
		'audios' => array('mp3','wav','wave','wma','aac','mid','midi','ogg','aif','aiff'),
		'videos' => array('mp4','mpg','mpeg','mov','3gp','avi')
	);

	private array $mimeTypes;
	private array $unite;

	public function __construct(
		private string $kernelDirectory,
		private string $defaultDirectory,
		private string $relativeDirectory,
		private Filesystem $filesystem,
		private AsciiSlugger $slugger
	)
	{
		// Unités de mesure pour la taille des fichiers
		$this->unite = ['o' => "Octets", 'ko' => "Ko", 'mo' => "Mo", 'go' => "Go"];

		// Initialisation des types MIME pour différents formats de fichiers
		$this->mimeTypes = [
			// Formats graphiques vectoriels et images
			'ai'   => 'application/postscript', // Adobe Illustrator
			'eps'  => 'application/postscript', // Encapsulated PostScript
			'svg'  => 'image/svg+xml',          // SVG
			'psd'  => 'image/vnd.adobe.photoshop', // Photoshop
			'indd' => 'application/x-indesign', // InDesign
			'cdr'  => 'application/coreldraw',  // CorelDRAW (non standard, peut varier)
			'sketch' => 'application/sketch',   // Sketch (non standard, peut varier)
			'fig'  => 'application/fig',        // Figma (non standard, peut varier)

			// Formats d'image courants
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'webp' => 'image/webp',
			'bmp'  => 'image/bmp',
			'tiff' => 'image/tiff',
			'ico'  => 'image/x-icon',

			// Formats de développement web
			'html' => 'text/html',
			'htm'  => 'text/html',
			'css'  => 'text/css',
			'js'   => 'application/javascript',
			'json' => 'application/json',
			'xml'  => 'application/xml',
			'php'  => 'application/x-httpd-php', // PHP

			// Formats texte
			'txt'  => 'text/plain', // Fichier texte brut

			// Formats de documents
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls'  => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'ppt'  => 'application/vnd.ms-powerpoint',
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

			// Archives
			'zip'  => 'application/zip',
			'rar'  => 'application/x-rar-compressed',
			'tar'  => 'application/x-tar',
			'gz'   => 'application/gzip',
			'7z'   => 'application/x-7z-compressed',

			// Formats audio et vidéo
			'mp3'  => 'audio/mpeg',
			'wav'  => 'audio/wav',
			'ogg'  => 'audio/ogg',
			'mp4'  => 'video/mp4',
			'avi'  => 'video/x-msvideo',
			'mov'  => 'video/quicktime',
			'webm' => 'video/webm',
		];
	}

	private function getKernelDirectory(): string
	{
		return $this->kernelDirectory;
	}

	public function getDefaultDirectory(): string
	{
		return $this->defaultDirectory;
	}

	public function setDefaultDirectory(string $directory): static
	{
		// $this->relativeDirectory = $directory;
		$this->defaultDirectory = $this->getKernelDirectory() . $directory;
		return $this;
	}

	public function getRelativeDirectory(): string
	{
		return $this->relativeDirectory;
	}

	public function setRelativeDirectory(string $directory): static
	{
		$this->relativeDirectory = $directory;
		return $this;
	}

	public function getMimeTypes(): array
	{
		return $this->mimeTypes;
	}

	public function getMimeType(string $key): string|array|null
	{
		return $this->mimeTypes[$key] ?? null;
	}

	public function exists(string $filePath): bool
	{
		$exist = $this->getDefaultDirectory() . '/' . $filePath;
		return $this->filesystem->exists($exist);
	}

	public function createSlug(string $string): string
	{
		return $this->slugger->slug($string)->lower();
	}

	public function createFile(string $file, string $content = '<!DOCTYPE html><html lang="en"><body style="background: #ffffff;"></body></html>'): void
	{
		$filename = pathinfo($file, PATHINFO_FILENAME);
		$extension = pathinfo($file, PATHINFO_EXTENSION);

		$this->filesystem->dumpFile($this->getDefaultDirectory() . '/' . $this->createSlug($filename) . '.' . $extension, $content);
	}

	public function createDir(string $directory): void
	{
		// $directories = explode('/', $directory);
		$this->filesystem->mkdir($this->getDefaultDirectory() . '/' . $this->createSlug($directory));
	}

	/**
	 * Cette fonction prend un tableau de fichiers en entrée et les catégorise selon leur extension.
	 * Elle retourne un tableau associatif avec les catégories de fichiers, qui contiennent chacune 
	 * trois tableaux avec les chemins d'accès (src), les noms de fichiers (basename) et les dossiers 
	 * parent (path).
	 *
	 * @param array $files Le tableau des fichiers à catégoriser
	 * @param bool $basename Un booléen pour spécifier si le tableau doit contenir les noms de fichiers (true) ou non (false)
	 * @param bool $path Un booléen pour spécifier si le tableau doit contenir les dossiers parent (true) ou non (false)
	 * @return array Le tableau des catégories de fichiers
	 */
	public static function categorizeFiles(array $files, bool $basename = false, bool $path = false): array
	{
		/* // Initialisation du tableau des catégories de fichiers avec les tableaux vides pour chaque catégorie
		$categories = array(
			'documents' => array(
				'src' => array(), 'basename' => array(), 'path' => array()
			),
			'images' => array(
				'src' => array(), 'basename' => array(), 'path' => array()
			),
			'audios' => array(
				'src' => array(), 'basename' => array(), 'path' => array()
			),
			'videos' => array(
				'src' => array(), 'basename' => array(), 'path' => array()
			),
			'other' => array(
				'src' => array(), 'basename' => array(), 'path' => array()
			)
		);

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
	 * Extrait le dossier parent du fichier à partir d'un chemin complet de fichier.
	 *
	 * Cette fonction prend en entrée un chemin complet de fichier et retourne le dossier parent du fichier.
	 * Elle cherche d'abord le dossier personnel de l'utilisateur, qui se trouve immédiatement après le répertoire "uploads/datas/".
	 * Ensuite, elle extrait tous les dossiers jusqu'à ce qu'elle trouve un nom de fichier (qui contient un point ".").
	 * Elle retourne ensuite tous les dossiers précédant ce fichier.
	 * Si aucun fichier n'est trouvé dans le chemin, la fonction retourne une chaîne vide.
	 * 
	 * @param string $folder Le chemin complet du fichier.
	 * @return string Le dossier parent du fichier.
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
	 * Récupère les extensions de fichier associées à un type donné.
	 * 
	 * @param string $type Le type de fichier pour lequel récupérer les extensions.
	 * @return array Les extensions de fichier associées au type spécifié, ou un tableau vide si le type n'existe pas.
	 */
	public static function getExtByType(string $type): array
	{
		/* if (array_key_exists($type, self::EXTENSIONS)) {
			return self::EXTENSIONS[$type];
		} */

		return array();
	}

	public function getDirs(string $path = '/', string $excludeDir = "", string|null $depth = '== 0'): array
	{
		$realPath = realpath($this->getDefaultDirectory() . '/' . trim($path, '/'));

		if (!$realPath || !is_dir($realPath)) {
			return [];
		}

		$finder = new Finder();
		if ($depth) {
			$finder->depth($depth); // Search only folders at the given depth $depth
		}
		$finder->directories()->in($realPath); // Search only folders at the root

		$directories = [];
		foreach ($finder as $dir) {
			$dirPath = $dir->getRealPath();

			if ($excludeDir && str_contains($dirPath, $excludeDir)) {
				continue;
			}

			$relative = str_replace($this->getDefaultDirectory(), '', $dirPath);
			// $relative = str_replace($this->getKernelDirectory(), '', $dirPath);

			$directories[] = [
				'absolute' => $dirPath,
				'relative' => $relative,
				'ltrimed_relative' => ltrim($relative, '/'),
				'foldername' => $dir->getFilename(),
			];
		}

		return $directories;
	}

	/**
	 * Récupère des parties spécifiques des répertoires fournis et les concatène si nécessaire.
	 *
	 * @param string|array $dirs Les répertoires en tant que chaîne unique ou tableau de chaînes.
	 * @param int $slice Le point de départ pour extraire les parties du répertoire.
	 * @param bool $implode Détermine si les parties extraites doivent être concaténées en une chaîne.
	 *
	 * @return string|array Les parties extraites des répertoires ou leur concaténation si demandée, ou false si vide.
	 */
	public static function getSliceDirs(string|array $dirs, int $slice, bool $implode = false): string|array
	{
		/* if (is_array($dirs)) {
			$tree_structure = array();

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
				$tree_structure_imploded = array();

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
	 * Récupère la liste des fichiers d'un répertoire donné.
	 *
	 * @param string $path Le chemin relatif au projet vers le répertoire à analyser.
	 *
	 * @return array|bool Retourne un tableau contenant les informations des fichiers trouvés ou `false` si le dossier est introuvable ou vide.
	 */
	public function getFiles(string $path = '/', string|null $depth = '== 0'): array|bool
	{
		$realPath = realpath(rtrim($this->getDefaultDirectory(), '/') . '/' . trim($path, '/'));

		if (!$realPath || !is_dir($realPath)) {
			return false;
		}

		$finder = new Finder();
		if ($depth) {
			$finder->depth($depth); // $finder->depth(['== 0']);
		}
		$finder->files()->in($realPath); // $finder->files()->in($realPath)->depth('== 0');

		if (!$finder->hasResults()) {
			return false;
		}

		$fileList = [];

		foreach ($finder as $file) {
			$fileList[] = $this->getFileInfo($file);
		}

		return $fileList;
	}

	private function getFileInfo(SplFileInfo $file): array
	{
		$filePath = $file->getRealPath();
		// $imageSize = @getimagesize($filePath); // Avoid error if it is not an image

		// dump($this->getKernelDirectory());
		// dump($filePath);
		// print_r($filePath);
		// echo "<pre>";
		// print_r($this->getRelativeDirectory());
		// echo "</pre>";
		// dd($this->getRelativeDirectory());
		// dd($this->getParameter('kernel.project_dir'));


		return [
			'absolute' => $filePath,
			'relative' => substr($filePath, strlen($this->getKernelDirectory() . $this->getRelativeDirectory())), // 'relative' => str_replace($this->getKernelDirectory() . $this->getRelativeDirectory(), '', $filePath), // 'relative' => strstr($filePath, $this->getRelativeDirectory(), false),
			'filename' => $file->getFilename(),
			'filesize' => $this->getSizeName($file->getSize()),
			'filemtime' => $file->getMTime(),
			/* 'dimensions' => [
				'width' => $imageSize[0] ?? null,
				'height' => $imageSize[1] ?? null
			], */
			'dimensions' => $this->getDimensionsFileInfo($filePath),
			'extension' => $file->getExtension(),
			'mime' => mime_content_type($file->getPathname()) // 'mime' => $imageSize['mime'] ?? null
		];
	}

	private function getDimensionsFileInfo(string $filePath): array
	{
		// $filePath = $file->getRealPath();
		$imageSize = @getimagesize($filePath); // Avoid error if it is not an image

		return [
			'width' => $imageSize[0] ?? null,
			'height' => $imageSize[1] ?? null
		];
	}

	/**
	 * Récupère la taille d'un tableau de fichiers ou d'un seul fichier en octets.
	 *
	 * @param string|array $files chemin absolu
	 * @param int $totalFileSize compteur incrémental
	 *
	 * @return int|float
	 */
	public static function getSize(string|array $files, int $totalFileSize = 0): int|float
	{
		/* if (is_string($files)) {
			$totalFileSize = $totalFileSize + filesize($files);
		} else {
			foreach ($files as $size) {
				$totalFileSize = $totalFileSize + filesize($size);
			}
		} */

		return $totalFileSize;
	}

	/**
	 * Renvoie la taille en format lisible d'un fichier en octets, Ko, Mo ou Go.
	 *
	 * @param int|float $size La taille du fichier en octets.
	 *
	 * @return string La taille en format lisible.
	 */
	public function getSizeName(int|float $size): string
	{
		if ($size < 1024) { // Octets
			return $size . ' ' . $this->unite['o'];
		}
		else {
			if ($size < 10485760) { // Ko
				$ko = round($size / 1024, 2);
				return $ko . ' ' . $this->unite['ko'];
			}
			else {
				if ($size < 1073741824) { // Mo
					$mo = round($size / (1024 * 1024), 2);
					return $mo . ' ' . $this->unite['mo'];
				}
				else { // Go
					$go = round($size / (1024 * 1024 * 1024), 2);
					return $go . ' ' . $this->unite['go'];
				}
			}
		}
	}

	public function upload(UploadedFile|array $files, string $folder, string $newName = "", bool $return = false): array|bool
	{
		$uploadedFiles = [];

		// Check if $files is an array (multiple upload) or a single file
		$files = is_array($files) ? $files : [$files];

		foreach ($files as $file) {
			/* $filename = $this->createSlug($file->getClientOriginalName());
			$filename = str_replace('-' . $file->getClientOriginalExtension(), '.' . $file->getClientOriginalExtension(), $filename); */
			$fileInfo = pathinfo($file->getClientOriginalName());
			// $filename = $this->createSlug($fileInfo['filename']) . '.' . strtolower($fileInfo['extension']);
			if (!empty($newName)) {
				$filename = $this->createSlug($newName) . '.' . strtolower($fileInfo['extension']);
			} else {
				$filename = $this->createSlug($fileInfo['filename']) . '.' . strtolower($fileInfo['extension']);
			}
			// dd($folder);
			$output = [
				'absolute' => $folder . '/' . $filename,
				'relative' => substr($folder . '/' . $filename, strlen($this->getKernelDirectory() . $this->getRelativeDirectory())), // 'relative' => str_replace($this->getKernelDirectory(), '', $folder . '/' . $filename),
				'filename' => $filename,
				'filesize' => $this->getSizeName($file->getSize()),
				'filemtime' => $file->getMTime(),
				'extension' => (!empty($file->getExtension())) ? $file->getExtension() : pathinfo($filename, PATHINFO_EXTENSION),
				'mime' => mime_content_type($file->getPathname())
			];

			if (!$file->move($folder, $filename)) {
				throw new \Exception("A problem occurred while uploading this file: " . $filename);
			}

			// $imageSize = @getimagesize($folder . '/' . $filename); // Avoid error if it is not an image
			/* $output['dimensions'] = [
				'width' => $imageSize[0] ?? null,
				'height' => $imageSize[1] ?? null
			]; */
			$output['dimensions'] = $this->getDimensionsFileInfo($folder . '/' . $filename);

			$uploadedFiles[] = $output;
		}
		// dd($uploadedFiles);
		return ($return) ? $uploadedFiles : true;
	}

	/**
	 * Redimensionne des images spécifiées dans le répertoire source et les enregistre dans le répertoire cible.
	 *
	 * @param array $files Liste des noms de fichiers image à redimensionner.
	 * @param string $sourceDir Répertoire source contenant les images à redimensionner.
	 * @param string $targetDir Répertoire cible où les images redimensionnées seront enregistrées.
	 * @param int $width Largeur souhaitée pour les images redimensionnées.
	 * @param int $quality Qualité de l'image redimensionnée (uniquement pour JPEG/PNG).
	 *
	 * @throws \Exception En cas d'erreur lors du traitement des images.
	 *
	 * @return bool Indique si le redimensionnement s'est effectué avec succès.
	 */
	public static function resizeImages(array $files, string $sourceDir, string $targetDir, int $width, int $quality = 100): array|bool
	{
		/* if ($width <= 0 || $quality <= 0 || $quality > 100) {
			throw new \InvalidArgumentException("Les valeurs de largeur et de qualité doivent être valides.");
		}

		$errors = [];
		$processed = [];

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

				// if (!in_array($type, ['jpeg', 'jpg', 'png'])) {
				if (!in_array($type, ['jpeg', 'jpg', 'png', 'webp'])) {
					throw new \Exception("Le format de fichier image n'est pas supporté : " . $file);
				}

				$old_width = $info[0];
				$old_height = $info[1];

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

				if (imagecopyresampled($output_image, $source, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height)) {
					// if (!is_dir($targetDir)) {
					// 	mkdir($targetDir, 0777, true);
					// }

					switch ($type) {
						case 'jpg':
						case 'jpeg':
							imagejpeg($output_image, $targetDir . '/' . $file, $quality);
							break;
						case 'webp':
							imagewebp($output_image, $targetDir . '/' . $file, $quality);
							break;
						case 'png':
							imagepng($output_image, $targetDir . '/' . $file, (int)((9 - ($quality / 100) * 9)));
							break;
					}
				} else {
					throw new \Exception("Impossible de redimensionner l'image : " . $file);
				}

				imagedestroy($source);
				imagedestroy($output_image);

				$processed[] = $file;
			} catch (\Exception $e) {
				$errors[] = $e->getMessage();
			}
		}

		// return ['success' => $processed, 'errors' => $errors];
		return true; */
		return ['resizeImages'];
	}

	public function remove(string $relativePath): bool
	{
		$this->filesystem->remove($this->getDefaultDirectory() . '/' . $relativePath);

		if ($this->exists($relativePath)) {
			return false;
		} else {
			return true;
		}
	}

	public function move(string $newName, bool $override = false): bool
	{
		dd($newName);
		// renames a file
		$filesystem->rename('/tmp/processed_video.ogg', '/path/to/store/video_647.ogg');
		// renames a directory
		$filesystem->rename('/tmp/files', '/path/to/store/files');

		// if ($this->filesystem->rename($this->getDefaultDirectory() . '/' . $relativePath)) {
		// 	return true;
		// } else {
		// 	return false;
		// }
		return true;
	}
}
