<?php

// anfallnorr/file-manager-system/src/Controller/FileManagerController.php
namespace Anfallnorr\FileManagerSystem\Controller;

use Anfallnorr\FileManagerSystem\Form\CreateFolderType;
use Anfallnorr\FileManagerSystem\Form\MoveFileType;
// use Anfallnorr\FileManagerSystem\Form\MoveFileType;
// use Anfallnorr\FileManagerSystem\Form\RenameFileType;
use Anfallnorr\FileManagerSystem\Form\RenameFileType;
use Anfallnorr\FileManagerSystem\Form\UploadFileType;
use Anfallnorr\FileManagerSystem\Service\FileManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
// use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FileManagerController extends AbstractController
{
	protected const string FILE_MANAGER = 'app_file_manager';										// self::FILE_MANAGER
	protected const string FILE_MANAGER_SERVE = 'app_file_manager_serve';							// self::FILE_MANAGER_SERVE
	protected const string FILE_MANAGER_DOWNLOAD_FILE = 'app_file_manager_download_file';			// self::FILE_MANAGER_DOWNLOAD_FILE
	protected const string FILE_MANAGER_DOWNLOAD_FOLDER = 'app_file_manager_download_folder';		// self::FILE_MANAGER_DOWNLOAD_FOLDER
	protected const string FILE_MANAGER_DOWNLOAD_BULK_FILE = 'app_file_manager_download_bulk_file';	// self::FILE_MANAGER_DOWNLOAD_BULK_FILE
	protected const string FILE_MANAGER_DELETE_FILE = 'app_file_manager_delete_file';				// self::FILE_MANAGER_DELETE_FILE
	protected const string FILE_MANAGER_DELETE_FOLDER = 'app_file_manager_delete_folder';			// self::FILE_MANAGER_DELETE_FOLDER
	protected const string FILE_MANAGER_MASS_DELETE_FOLDER = 'app_file_manager_mass_delete_folder';	// self::FILE_MANAGER_MASS_DELETE_FOLDER

	public function __construct(
		private FileManagerService $fmService,
		private TranslatorInterface $translator
	) {
		// $fmService
		$this->fmService
			->setDefaultDirectory('/var/uploads')
			->setRelativeDirectory('/var/uploads');
		// $fmService->setDefaultDirectory('/var/uploads');
		// $fmService->setRelativeDirectory('/var/uploads');
		/* if (!is_dir($fmService->getDefaultDirectory())) {
			throw $this->createNotFoundException('Folder ' . $fmService->getRelativeDirectory() . ' not found');
		} */
	}

	// #[Route('/', name: 'app_index')]
	/* #[Route('/files-manager', name: 'app_index')]
	public function index()
	{
		dd('toto');
		return $this->redirectToRoute(self::FILE_MANAGER);
	} */

	// #[Route(path: '/file/serve/{filename}/{folder}', name: self::FILE_MANAGER_SERVE, defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
	#[Route(path: '/file/serve/{filename}/{folder}', name: self::FILE_MANAGER_SERVE, defaults: ['folder' => ''], requirements: ['folder' => Requirement::CATCH_ALL])]
	public function appFileManagerServe(string $filename, string $folder): BinaryFileResponse
	{
		// File directory
		// $fmService = $this->fmService;
		// $fmService->setDefaultDirectory('/var/uploads');

		// Base directory (this should now handle both cases)
		$baseDirectory = $this->fmService->getDefaultDirectory();
		// dd($baseDirectory);


		// Full path of the requested file
		/* if (empty($folder)) {
			$filePath = $baseDirectory . '/' . $filename;
		} else {
			// $filePath = $baseDirectory . '/' . $folder . '/' . $filename;
			$filePath = rtrim(string: $baseDirectory, characters: '/') . '/' . trim(string: $folder, characters: '/') . '/' . ltrim(string: $filename, characters: '/');
		} */

		$cleanBase = \rtrim(string: $baseDirectory, characters: '/');
		$cleanFolder = \trim(string: $folder, characters: '/');
		$cleanFile = \ltrim(string: $filename, characters: '/');

		$filePath = (empty($folder))
			? "{$cleanBase}/{$cleanFile}"
			: "{$cleanBase}/{$cleanFolder}/{$cleanFile}";


		// dd($filePath);
		if (!\file_exists(filename: $filePath)) {
			throw $this->createNotFoundException(message: 'Fichier introuvable.');
			// throw $this->createNotFoundException('File not found');
		}
		// throw $this->createNotFoundException('Fichier introuvable.');

		// Retourne le fichier en tant que réponse
		/* $binary = new BinaryFileResponse($filePath, 200, [
			'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE, // Online display (for images)
		]);
		dd($binary); */
		return new BinaryFileResponse(file: $filePath, status: 200, headers: [
			'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE, // Online display (for images)
		]);
	}

	// #[Route(path: '/file/download/{filename}/{folder}', name: self::FILE_MANAGER_DOWNLOAD_FILE, defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
	#[Route(path: '/file/download/{filename}/{folder}', name: self::FILE_MANAGER_DOWNLOAD_FILE, defaults: ['folder' => ''], methods: ['GET'], requirements: ['folder' => Requirement::CATCH_ALL])]
	public function appFileManagerDownloadFile(string $filename, string $folder): BinaryFileResponse
	{
		/* // File directory
		$baseDirectory = $this->fmService->getDefaultDirectory();

		// Full path of the requested file
		if (empty($folder)) {
			$filePath = $baseDirectory . '/' . $filename;
		} else {
			$filePath = rtrim($baseDirectory, '/') . '/' . trim($folder, '/') . '/' . ltrim($filename, '/');
		}
		// dd($filePath);

		if (!file_exists($filePath)) {
			throw $this->createNotFoundException('Fichier introuvable.');
		}

		// Retourne le fichier en téléchargement
		$response = new BinaryFileResponse($filePath);
		$response->setContentDisposition(
			ResponseHeaderBag::DISPOSITION_ATTACHMENT, // ATTACHMENT => force download
			$filename // nom de fichier suggéré pour le navigateur
		);

		return $response; */
		return $this->fmService->download($filename, $folder);
		/* if ($this->fmService->download($filename, $folder)) {
			return $this->redirectToRoute(self::FILE_MANAGER, [
				'folder' => $folder
			]);
		} */
	}

	// app_file_manager_download_folder
	#[Route(path: '/folder/download/{dirname}/{folder}', name: self::FILE_MANAGER_DOWNLOAD_FOLDER, defaults: ['folder' => ''], methods: ['GET'], requirements: ['folder' => Requirement::CATCH_ALL])]
	public function appFileManagerDownloadFolder(string $dirname, string $folder): BinaryFileResponse
	// public function appFileManagerDownloadFolder(string $dirname, string $folder): Response
	{
		// dump($dirname);
		// dd($folder);


		return $this->fmService->download($dirname, $folder);
	}

	// app_file_manager_download_bulk_file
	#[Route(path: '/file/mass-download/{folder}', name: self::FILE_MANAGER_DOWNLOAD_BULK_FILE, methods: ['POST'], defaults: ['folder' => ''], requirements: ['folder' => Requirement::CATCH_ALL])]
	public function appFileManagerDownloadBulkFile(Request $request, string $folder): BinaryFileResponse
	{
		$files = \json_decode(json: $request->getPayload()->get(key: 'filesToDownload'));
		$folders = \json_decode(json: $request->getPayload()->get(key: 'foldersToDownload'));
		// dd($files);
		// dump($folder);
		// dump($files);
		// dd($folders);
		// dd([...$files, ...$folders]);

		// return $this->fmService->downloadBulk($files, $folders);
		return $this->fmService->downloadBulk([...$files, ...$folders], $folder);
	}

	/* #[Route('/file/move/{filename}/{folder}', name: 'app_file_manager_move', defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
	// #[Route('/file/move/{filename}/{folder}', name: 'app_file_manager_move', defaults: ['folder' => ''], requirements: ['folder' => Requirement::CATCH_ALL])]
	public function appFileManagerMoveFile(string $filename, string $folder): BinaryFileResponse
	{
		$baseDirectory = $this->fmService->getDefaultDirectory();


		// Full path of the requested file
		if (empty($folder)) {
			$filePath = $baseDirectory . '/' . $filename;
		} else {
			// $filePath = $baseDirectory . '/' . $folder . '/' . $filename;
			$filePath = rtrim($baseDirectory, '/') . '/' . trim($folder, '/') . '/' . ltrim($filename, '/');
		}

			// dd($filePath);
		if (!file_exists($filePath)) {
			throw $this->createNotFoundException('Fichier introuvable.');
			// throw $this->createNotFoundException('File not found');
		}


		// Retourne le fichier en tant que réponse
		return new BinaryFileResponse($filePath, 200, [
			'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE, // Online display (for images)
		]);
	} */

	// #[Route(path: '/file/delete/{filename}/{folder}', name: self::FILE_MANAGER_DELETE_FILE, defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	#[Route(path: '/file/delete/{filename}/{folder}', name: self::FILE_MANAGER_DELETE_FILE, defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => Requirement::CATCH_ALL])]
	public function appFileManagerDeleteFile(string $filename, string $folder): Response
	{
		// dd($filename);
		// File directory
		// $fmService = $this->fmService;

		// Relative path of the requested file
		/* if (!empty($folder)) {
			$filePath = $folder . '/' . $filename;
		} else {
			$filePath = $filename;
		} */
		$filePath = (!empty($folder))
			? "{$folder}/{$filename}"
			: $filename;

		// dump($filename);
		// dump($folder);
		// dd($filePath);
		if ($this->fmService->exists($filePath, false)) {
			// dd($filePath);
			$this->fmService->remove($filePath);

			$this->addFlash(
				type: 'success',
				message: $this->translator->trans(id: 'file_manager.file_successfully_deleted')
			);
		} else {
			$this->addFlash(
				type: 'danger',
				message: $this->translator->trans(id: 'file_manager.failed_to_delete_file')
			);
		}


		return $this->redirectToRoute(route: self::FILE_MANAGER, parameters: [
			'folder' => $folder
		]);
	}

	// #[Route(path: '/folder/delete/{dirname}/{folder}', name: self::FILE_MANAGER_DELETE_FOLDER, defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	#[Route(path: '/folder/delete/{dirname}/{folder}', name: self::FILE_MANAGER_DELETE_FOLDER, defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => Requirement::CATCH_ALL])]
	public function appFileManagerDeleteFolder(string $folder, string $dirname): Response
	{
		// File directory
		// $fmService = $this->fmService;

		// Relative path of the requested file
		/* if (!empty($folder)) {
			$filePath = $folder . '/' . $dirname;
		} else {
			$filePath = $dirname;
		} */
		$filePath = (!empty($folder))
			? "{$folder}/{$dirname}"
			: $dirname;

		if ($this->fmService->exists($filePath)) {
			$this->fmService->remove($filePath);

			$this->addFlash(
				type: 'success',
				message: $this->translator->trans(id: 'file_manager.folder_successfully_deleted')
			);
		} else {
			$this->addFlash(
				type: 'danger',
				message: $this->translator->trans(id: 'file_manager.failed_to_delete_folder')
			);
		}


		return $this->redirectToRoute(route: self::FILE_MANAGER, parameters: [
			'folder' => $folder
		]);
	}

	// #[Route(path: '/files/mass-delete/{folder}', name: self::FILE_MANAGER_MASS_DELETE_FOLDER, defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	#[Route(path: '/files/mass-delete/{folder}', name: self::FILE_MANAGER_MASS_DELETE_FOLDER, defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => Requirement::CATCH_ALL])]
	public function appFileManagerMassDeleteFolder(Request $request, string $folder): Response
	{
		$foldersToDelete = \json_decode(json: $request->get(key: 'foldersToDelete'));
		$filesToDelete = \json_decode(json: $request->get(key: 'filesToDelete'));

		if (!empty($foldersToDelete) || !empty($filesToDelete)) {
			if (!empty($foldersToDelete)) {
				foreach ($foldersToDelete as $file) {
					// Chemin complet du fichier demandé
					/* if (!empty($folder)) {
						$folderPath = $folder . '/' . $file;
					} else {
						$folderPath = $file;
					} */
					$folderPath = (!empty($folder))
						? "{$folder}/{$file}"
						: $file;

					if ($this->fmService->exists($folderPath)) {
						$this->fmService->remove($folderPath);
					}
				}

				$this->addFlash(
					type: 'success',
					message: $this->translator->trans(id: 'file_manager.folders_successfully_mass_deleted')
				);
			}

			if (!empty($filesToDelete)) {
				foreach ($filesToDelete as $file) {
					// Chemin complet du fichier demandé
					/* if (!empty($folder)) {
						$filePath = $folder . '/' . $file;
					} else {
						$filePath = $file;
					} */
					$filePath = (!empty($folder))
						? "{$folder}/{$file}"
						: $file;

					if ($this->fmService->exists($filePath)) {
						$this->fmService->remove($filePath);
					}
				}

				$this->addFlash(
					type: 'success',
					message: $this->translator->trans(id: 'file_manager.files_successfully_mass_deleted')
				);
			}
		} else {
			$this->addFlash(
				type: 'warning',
				message: $this->translator->trans(id: 'file_manager.no_files_or_folders_selected')
			);
		}


		return $this->redirectToRoute(route: self::FILE_MANAGER, parameters: [
			'folder' => $folder
		]);
	}

	// #[Route(path: '/{folder}', name: self::FILE_MANAGER, defaults: ['folder' => ''], methods: ['POST', 'GET'], requirements: ['folder' => '.+'])]
	#[Route(path: '/{folder}', name: self::FILE_MANAGER, defaults: ['folder' => ''], methods: ['POST', 'GET'], requirements: ['folder' => Requirement::CATCH_ALL])]
	public function appFileManager(Request $request, string $folder): Response
	{
		// dd(vars: 'toto');
		// $fmService = $this->fmService;

		// dd($this->fmService->getRelativeDirectory());
		// dd($breadcrumb);
		// $fmService = new fmService($this->getParameter('file_manager_system.kernel_directory'), $this->getParameter('file_manager_system.default_directory'), new Filesystem(), new AsciiSlugger());
		// $fmService = new fmService($this->getParameter('kernel.project_dir'), $this->getParameter('kernel.project_dir') . '/var/www/uploads', new Filesystem(), new AsciiSlugger());

		/* $defaultDirectory = $fmService->getDefaultDirectory(); // /path/to/folder/public/uploads
		$directory = $fmService->setDefaultDirectory('/var/www/uploads')->getDefaultDirectory(); // /path/to/folder/var/www/uploads
		$mimeTypes = $fmService->getMimeTypes(); // array
		$mimeType = $fmService->getMimeType('pdf'); // application/pdf
		$string = $fmService->createSlug('Hello World !'); // hello-world

		$fmService->createDir('Hello World !'); // create hello-world directory in default directory path
		$fmService->createFile('Hello World.html', 'Hello World! I\'m Js info'); // create hello-world.html file in default directory path */


		$breadcrumb = \explode(separator: '/', string: $folder);

		// Retrieve global files and folders first before changing the default path
		// $allFolders = $this->fmService->getDirs($path = '/', $excludeDir = "", $depth = null);
		$allFolders = $this->fmService->getDirsTree('/', "", null);
		$allFiles = $this->fmService->getFiles('/', null);

		if (!empty($folder)) {
			// $this->fmService->setDefaultDirectory('/public/uploads/' . $folder); // Example for personnal folder space: '/var/uploads/' . $his->getUser()->getId()
			$this->fmService->setDefaultDirectory($this->fmService->getRelativeDirectory() . '/' . $folder); // Example for personnal folder space: '/var/uploads/' . $his->getUser()->getId()
		}
		// dump($this->fmService->getDefaultDirectory());
		// dump($this->getParameter('kernel.project_dir'));
		// dump($this->getParameter('kernel.project_dir') . $this->fmService->getRelativeDirectory());
		// dd($this->fmService->getRelativeDirectory());


		// Check if path is a valid folder
		/* if (!is_dir($this->fmService->getDefaultDirectory())) {
			throw $this->createNotFoundException('Folder ' . $this->fmService->getRelativeDirectory() . ' not found');
		} */

		$uploadUrl = $this->generateUrl(route: self::FILE_MANAGER, parameters: [
			'folder' => $folder
		]);

		$folders = $this->fmService->getDirs();
		$files = $this->fmService->getFiles();

		// dd($files);

		// Folder creation
		$createFolderForm = $this->createForm(type: CreateFolderType::class);
		$createFolderForm->handleRequest(request: $request);

		if ($createFolderForm->isSubmitted() && $createFolderForm->isValid()) {
			$folderName = $createFolderForm->get(name: 'folderName')->getData();

			if (!$this->fmService->exists($folderName, false)) {
				// $this->fmService->createDir($folderName);
				$created = $this->fmService->createDir($folderName, true);

				$flashType = 'success';
				$flashMessage = $this->translator->trans(id: 'file_manager.folder_created_successfully');
			} else {
				$flashType = 'warning';
				$flashMessage = $this->translator->trans(id: 'file_manager.the_folder_already_exists', parameters: ['%foldername%' => $folderName]);
			}

			$this->addFlash(type: $flashType, message: $flashMessage);


			return new Response(
				// $this->renderView('_partials/elements/folders-list.html.twig', [
				content: $this->renderView(view: '@FileManagerSystem/_partials/elements/folders-list.html.twig', parameters: [
					'folders' => $created,
					'current_folder' => $folder,
					// ]) . $this->renderView(view: '_partials/elements/stream-flash.html.twig'),
				]) . $this->renderView(view: '@FileManagerSystem/_partials/elements/stream-flash.html.twig'),
				status: 200,
				headers: ['Content-Type' => 'text/vnd.turbo-stream.html'] // Le type de contenu pour Turbo Stream
			);
			/* return $this->redirectToRoute(route: self::FILE_MANAGER, parameters: [
				'folder' => $folder
			], status: Response::HTTP_SEE_OTHER); */
		}


		// File upload
		$uploadFileForm = $this->createForm(type: UploadFileType::class, data: null, options: [
			'user' => null, // $user->getId(),
			'route' => $uploadUrl,
			'current_folder' => $folder
		]);
		$uploadFileForm->handleRequest(request: $request);

		if ($uploadFileForm->isSubmitted() && $uploadFileForm->isValid()) {
			$files = $uploadFileForm->get(name: 'file')->getData();

			if ($files) {
				try {
					// $uploaded = $this->fmService->upload($files, $this->fmService->getDefaultDirectory(), "", false);
					$uploaded = $this->fmService->upload($files, $this->fmService->getDefaultDirectory(), "", true);

					$this->addFlash(
						type: 'success',
						message: $this->translator->trans(id: 'file_manager.file_uploaded_successfully')
					);
				} catch (FileException $e) {
					$this->addFlash(
						type: 'danger',
						message: $this->translator->trans(id: 'file_manager.error_while_uploading', parameters: ['%message%' => $e])
					);
				}
			}

			return new Response(
				// $this->renderView('_partials/elements/files-list.html.twig', [
				content: $this->renderView(view: '@FileManagerSystem/_partials/elements/files-list.html.twig', parameters: [
					'files' => $uploaded,
					'current_folder' => $folder,
					// ]) . $this->renderView(view: '_partials/elements/stream-flash.html.twig'),
				]) . $this->renderView(view: '@FileManagerSystem/_partials/elements/stream-flash.html.twig'),
				status: 200,
				headers: ['Content-Type' => 'text/vnd.turbo-stream.html']
			);
			/* return $this->redirectToRoute(route: self::FILE_MANAGER, parameters: [
				'folder' => $folder
			], status: Response::HTTP_SEE_OTHER); */
		}


		// Move File
		// dd($folder);
		$moveFileForm = $this->createForm(type: MoveFileType::class, data: null, options: [
			'current_folder' => $folder
		]);
		$moveFileForm->handleRequest(request: $request);

		/* $moveFileForms = [];

		if (!empty($files)) {
			foreach ($files as $key => $file) {
				$moveFileForms[$key] = $this->createForm(MoveFileType::class)->createView();
			}
			// dd($moveFileForms);
		} */

		// dd($moveFileForm);
		if ($moveFileForm->isSubmitted() && $moveFileForm->isValid()) {
			/* $files = $moveFileForm->get(name: 'file')->getData();

			if ($files) {
				try {
					$uploaded = $this->fmService->upload($files, $this->fmService->getDefaultDirectory(), "", false);

					$this->addFlash(
						type: 'success',
						message: $this->translator->trans(id: 'file_manager.file_uploaded_successfully')
					);
				} catch (FileException $e) {
					$this->addFlash(
						type: 'danger',
						message: $this->translator->trans(id: 'file_manager.error_while_uploading', parameters: ['%message%' => $e])
					);
				}
			}

			return $this->redirectToRoute(route: self::FILE_MANAGER, parameters: [
				'folder' => $folder
			], status: Response::HTTP_SEE_OTHER); */
		}


		// Rename File
		$renameFileForm = $this->createForm(type: RenameFileType::class, data: null, options: [
			'current_folder' => $folder
		]);
		$renameFileForm->handleRequest(request: $request);
		// dd($renameFileForm);
		if ($renameFileForm->isSubmitted() && $renameFileForm->isValid()) {
			$renameFileData = $renameFileForm->getData();
			/* $basePath = ($renameFileData['currentPath'])
				? $renameFileData['currentPath'] . '/'
				: '/'; */

			// dump($renameFileData);
			// dump("{$basePath}{$renameFileData['currentFileName']}");
			// dump("{$basePath}{$renameFileData['newFileName']}");
			// dd($basePath);
			// $renamed = $this->fmService->rename("{$basePath}{$renameFileData['currentFileName']}", "{$basePath}{$renameFileData['newFileName']}", true);
			$renamed = $this->fmService->rename("{$renameFileData['currentFileName']}", "{$renameFileData['newFileName']}", true);
			// dd($renameFileData);

			if ($renamed) {
				$this->addFlash(
					type: 'success',
					// message: $this->translator->trans(id: 'file_manager.file_uploaded_successfully')
					message: "Le fichier à été renommé avec succès"
				);
			} else {
				$this->addFlash(
					type: 'danger',
					// message: $this->translator->trans(id: 'file_manager.error_while_uploading')
					message: "Le fichier n'a pas pu être renommé"
				);
			}

			return $this->redirectToRoute(route: self::FILE_MANAGER, parameters: [
				'folder' => $folder
			], status: Response::HTTP_SEE_OTHER);
		}


		// return $this->render(view: 'home/index.html.twig', [
		return $this->render(view: '@FileManagerSystem/file-manager/index.html.twig', parameters: [
			'folder_form' => $createFolderForm,
			'file_form' => $uploadFileForm,
			'move_file_form' => $moveFileForm,
			'rename_file_form' => $renameFileForm,
			// 'move_file_forms' => $moveFileForms,
			'breadcrumb' => $breadcrumb,
			'breadcrumb_link' => '',
			'current_folder' => $folder,
			'folders' => $folders,
			'files' => $files,
			'allFolders' => $allFolders,
			'allFiles' => $allFiles
		]);
	}
}
