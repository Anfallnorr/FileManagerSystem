<?php

namespace Anfallnorr\FileManagerSystem\Service\Manager;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadManager
{
	public function upload(
		UploadedFile|array $files,
		string $targetDir,
		string $newName = ''
	): array
	{
		return [];
	}
}
