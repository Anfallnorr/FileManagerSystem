<?php

namespace Anfallnorr\FileManagerSystem\Service\Manager;

// use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageResizeManager
{
	/**
	 * Redimensionne des images spécifiées dans le répertoire source et les enregistre dans le répertoire cible.
	 *
	 * @param array $files Liste des noms de fichiers image à redimensionner.
	 * @param string $sourceDir Répertoire source contenant les images à redimensionner.
	 * @param string $targetDir Répertoire cible où les images redimensionnées seront enregistrées.
	 * @param int $width Largeur souhaitée pour les images redimensionnées.
	 * @param int $quality Qualité de l'image redimensionnée (uniquement pour JPEG/PNG/WEBP).
	 * @param string|null $suffix Suffixe optionnel à ajouter au nom du fichier (ex: 'fhd', '4k').
	 *
	 * @throws \Exception En cas d'erreur lors du traitement des images.
	 *
	 * @return array Tableau contenant les informations détaillées des images redimensionnées et les erreurs.
	 */
	public function resizeImages(array $files, string $sourceDir, string $targetDir, int $width, int $quality = 100, ?string $suffix = null): array
	{
		/* if ($width <= 0 || $quality <= 0 || $quality > 100) {
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

		return ['success' => $processed, 'errors' => $errors]; */
		return [];
	}
}
