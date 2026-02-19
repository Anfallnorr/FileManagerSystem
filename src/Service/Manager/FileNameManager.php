<?php

namespace Anfallnorr\FileManagerSystem\Service\Manager;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileNameManager
{
	public function generate(
		UploadedFile $file,
		?string $customName = null
	): string
	{
		return '';
	}
}
