<?php

// anfallnorr/file-manager-system/src/Controller/FileManagerController.php
namespace Anfallnorr\FileManagerSystem\Controller;

use Anfallnorr\FileManagerSystem\Form\CreateFolderType;
use Anfallnorr\FileManagerSystem\Form\MoveFileType;
// use Anfallnorr\FileManagerSystem\Form\MoveFileType;
// use Anfallnorr\FileManagerSystem\Form\RenameFileType;
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
// use Symfony\Component\Routing\Requirement\Requirement;
// use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FileManagerController extends AbstractController
{
	protected const FILE_MANAGER = 'app_file_manager';											// self::FILE_MANAGER
	protected const FILE_MANAGER_SERVE = 'app_file_manager_serve';								// self::FILE_MANAGER_SERVE
	protected const FILE_MANAGER_DOWNLOAD_FILE = 'app_file_manager_download_file';				// self::FILE_MANAGER_DOWNLOAD_FILE
	protected const FILE_MANAGER_DOWNLOAD_BULK_FILE = 'app_file_manager_download_bulk_file';	// self::FILE_MANAGER_DOWNLOAD_BULK_FILE
	protected const FILE_MANAGER_DELETE_FILE = 'app_file_manager_delete_file';					// self::FILE_MANAGER_DELETE_FILE
	protected const FILE_MANAGER_DELETE_FOLDER = 'app_file_manager_delete_folder';				// self::FILE_MANAGER_DELETE_FOLDER
	protected const FILE_MANAGER_MASS_DELETE_FOLDER = 'app_file_manager_mass_delete_folder';	// self::FILE_MANAGER_MASS_DELETE_FOLDER

	public function __construct(
		private FileManagerService $fmService,
		private TranslatorInterface $translator
	) {
		$fmService
			->setDefaultDirectory('/var/uploads') // ->setDefaultDirectory($directory = '/var/uploads')
			->setRelativeDirectory('/var/uploads'); // ->setRelativeDirectory($directory = '/var/uploads');
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

	#[Route('/file/serve/{filename}/{folder}', name: self::FILE_MANAGER_SERVE, defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
	public function appFileManagerServe(string $filename, string $folder): BinaryFileResponse
	{
		// File directory
		// $fmService = $this->fmService;
		// $fmService->setDefaultDirectory('/var/uploads');

		// Base directory (this should now handle both cases)
		$baseDirectory = $this->fmService->getDefaultDirectory();
		// dd($baseDirectory);


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
// throw $this->createNotFoundException('Fichier introuvable.');

		// Retourne le fichier en tant que réponse
		/* $binary = new BinaryFileResponse($filePath, 200, [
			'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE, // Online display (for images)
		]);
		dd($binary); */
		return new BinaryFileResponse($filePath, 200, [
			'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE, // Online display (for images)
		]);
	}

	#[Route('/file/download/{filename}/{folder}', name: self::FILE_MANAGER_DOWNLOAD_FILE, defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
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

	// #[Route('/file/download/bulk/{folder}', name: 'app_file_manager_download_bulk_file', methods: ['POST'], defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
	#[Route('/file/mass-download/{folder}', name: self::FILE_MANAGER_DOWNLOAD_BULK_FILE, methods: ['POST'], defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
	public function appFileManagerDownloadBulkFile(Request $request): BinaryFileResponse
	{
		$files = json_decode($request->get('filesToDownload'));
		$folders = json_decode($request->get('foldersToDownload'));
		// $files = $request->request->get('files', []); // tableau de noms de fichiers
		// $folders = $request->request->get('folder', null);
		// dd($files);
		dump($files);
		dd($folders);

		return $this->fmService->downloadBulk($files, $folder);
	}

	/* #[Route('/file/move/{filename}/{folder}', name: 'app_file_manager_move', defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
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

	#[Route('/file/delete/{filename}/{folder}', name: self::FILE_MANAGER_DELETE_FILE, defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	public function appFileManagerDeleteFile(string $filename, string $folder): Response
	{
		// dd($filename);
		// File directory
		// $fmService = $this->fmService;
		
		// Relative path of the requested file
		if (!empty($folder)) {
			$filePath = $folder . '/' . $filename;
		} else {
			$filePath = $filename;
		}

		// dump($filename);
		// dump($folder);
		// dd($filePath);
		if ($this->fmService->exists($filePath, false)) { // if ($this->fmService->exists($filePath = $filePath, $absolute = false)) { 
			// dd($filePath);
			$this->fmService->remove($filePath); // $this->fmService->remove($relativePath = $filePath);

			$this->addFlash(
				'success',
				$this->translator->trans('file_manager.file_successfully_deleted')
			);
		} else {
			$this->addFlash(
				'danger',
				$this->translator->trans('file_manager.failed_to_delete_file')
			);
		}


		return $this->redirectToRoute(self::FILE_MANAGER, [
			'folder' => $folder
		]);
	}

	#[Route('/folder/delete/{dirname}/{folder}', name: self::FILE_MANAGER_DELETE_FOLDER, defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	public function appFileManagerDeleteFolder(string $folder, string $dirname): Response
	{
		// File directory
		// $fmService = $this->fmService;

		// Relative path of the requested file
		if (!empty($folder)) {
			$filePath = $folder . '/' . $dirname;
		} else {
			$filePath = $dirname;
		}
		
		if ($this->fmService->exists($filePath)) { // if ($this->fmService->exists($filePath = $filePath, $absolute = false)) {
			$this->fmService->remove($filePath); // $this->fmService->remove($relativePath = $filePath);

			$this->addFlash(
				'success',
				$this->translator->trans('file_manager.folder_successfully_deleted')
			);
		} else {
			$this->addFlash(
				'danger',
				$this->translator->trans('file_manager.failed_to_delete_folder')
			);
		}


		return $this->redirectToRoute(self::FILE_MANAGER, [
			'folder' => $folder
		]);
	}

	#[Route('/files/mass-delete/{folder}', name: self::FILE_MANAGER_MASS_DELETE_FOLDER, defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	public function appFileManagerMassDeleteFolder(Request $request, string $folder): Response
	{
		$foldersToDelete = json_decode($request->get('foldersToDelete'));
		$filesToDelete = json_decode($request->get('filesToDelete'));

		if (!empty($foldersToDelete) || !empty($filesToDelete)) {
			if (!empty($foldersToDelete)) {
				foreach ($foldersToDelete as $file) {
					// Chemin complet du fichier demandé
					if (!empty($folder)) {
						$folderPath = $folder . '/' . $file;
					} else {
						$folderPath = $file;
					}

					if ($this->fmService->exists($folderPath)) { // if ($this->fmService->exists($filePath = $folderPath, $absolute = false)) {
						$this->fmService->remove($folderPath); // $this->fmService->remove($relativePath = $folderPath);
					}
				}

				$this->addFlash(
					'success',
					$this->translator->trans('file_manager.folders_successfully_mass_deleted')
				);
			}

			if (!empty($filesToDelete)) {
				foreach ($filesToDelete as $file) {
					// Chemin complet du fichier demandé
					if (!empty($folder)) {
						$filePath = $folder . '/' . $file;
					} else {
						$filePath = $file;
					}

					if ($this->fmService->exists($filePath)) { // if ($this->fmService->exists($filePath = $filePath, $absolute = false)) {
						$this->fmService->remove($filePath); // $this->fmService->remove($relativePath = $filePath);
					}
				}

				$this->addFlash(
					'success',
					$this->translator->trans('file_manager.files_successfully_mass_deleted')
				);
			}
		} else {
			$this->addFlash(
				'warning',
				$this->translator->trans('file_manager.no_files_or_folders_selected')
			);
		}


		return $this->redirectToRoute(self::FILE_MANAGER, [
			'folder' => $folder
		]);
	}

	#[Route('/{folder}', name: self::FILE_MANAGER, defaults: ['folder' => ''], methods: ['POST', 'GET'], requirements: ['folder' => '.+'])]
	public function appFileManager(Request $request, string $folder): Response
	{
		// dd('toto');
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


		$breadcrumb = explode('/', $folder);

		// Retrieve global files and folders first before changing the default path
		// $allFolders = $this->fmService->getDirs($path = '/', $excludeDir = "", $depth = null);
		$allFolders = $this->fmService->getDirsTree($path = '/', $excludeDir = "", $depth = null);
		$allFiles = $this->fmService->getFiles($path = '/', $depth = null);
		
		if (!empty($folder)) {
			// $this->fmService->setDefaultDirectory('/public/uploads/' . $folder); // Example for personnal folder space: '/var/uploads/' . $his->getUser()->getId()
			$this->fmService->setDefaultDirectory($this->fmService->getRelativeDirectory() . '/' . $folder); // $this->fmService->setDefaultDirectory($directory = $this->fmService->getRelativeDirectory() . '/' . $folder); // Example for personnal folder space: '/var/uploads/' . $his->getUser()->getId()
		}
		// dump($this->fmService->getDefaultDirectory());
		// dump($this->getParameter('kernel.project_dir'));
		// dump($this->getParameter('kernel.project_dir') . $this->fmService->getRelativeDirectory());
		// dd($this->fmService->getRelativeDirectory());


		// Check if path is a valid folder
		/* if (!is_dir($this->fmService->getDefaultDirectory())) {
			throw $this->createNotFoundException('Folder ' . $this->fmService->getRelativeDirectory() . ' not found');
		} */

		$uploadUrl = $this->generateUrl(self::FILE_MANAGER, [
			'folder' => $folder
		]);

		$folders = $this->fmService->getDirs();
		$files = $this->fmService->getFiles();

		// Folder creation
		$createFolderForm = $this->createForm(CreateFolderType::class);
		$createFolderForm->handleRequest($request);

		if ($createFolderForm->isSubmitted() && $createFolderForm->isValid()) {
			$folderName = $createFolderForm->get('folderName')->getData();

			if (!$this->fmService->exists($folderName, false)) { // if (!$this->fmService->exists($filePath = $folderName, $absolute = false)) {
				// $this->fmService->createDir($folderName);
				$created = $this->fmService->createDir($folderName, true); // $created = $this->fmService->createDir($directory = $folderName, $return = true);

				$flashType = 'success';
				$flashMessage = $this->translator->trans('file_manager.folder_created_successfully');
			} else {
				$flashType = 'warning';
				$flashMessage = $this->translator->trans('file_manager.the_folder_already_exists', ['%foldername%' => $folderName]);
			}

			$this->addFlash($flashType, $flashMessage);


			return new Response(
				// $this->renderView('_partials/elements/folders-list.html.twig', [
				$this->renderView('@FileManagerSystem/_partials/elements/folders-list.html.twig', [
					'folders' => $created,
					'current_folder' => $folder,
				// ]) . $this->renderView('_partials/elements/stream-flash.html.twig'),
				]) . $this->renderView('@FileManagerSystem/_partials/elements/stream-flash.html.twig'),
				200,
				['Content-Type' => 'text/vnd.turbo-stream.html'] // Le type de contenu pour Turbo Stream
			);
			/* return $this->redirectToRoute(self::FILE_MANAGER, [
				'folder' => $folder
			]); */
		}


		// File upload
		$uploadFileForm = $this->createForm(UploadFileType::class, null, [
			'user' => null, // $user->getId(),
			'route' => $uploadUrl,
			'current_folder' => $folder
		]);
		$uploadFileForm->handleRequest($request);

		if ($uploadFileForm->isSubmitted() && $uploadFileForm->isValid()) {
			$files = $uploadFileForm->get('file')->getData();
			
			if ($files) {
				try {
					// $uploaded = $this->fmService->upload($files, $this->fmService->getDefaultDirectory(), "", false);
					$uploaded = $this->fmService->upload($files, $this->fmService->getDefaultDirectory(), "", true); // $uploaded = $this->fmService->upload($files = $files, $folder = $this->fmService->getDefaultDirectory(), $newName = "", $return = true);
					
					$this->addFlash(
						'success',
						$this->translator->trans('file_manager.file_uploaded_successfully')
					);
				} catch (FileException $e) {
					$this->addFlash(
						'danger',
						$this->translator->trans('file_manager.error_while_uploading', ['%message%' => $e])
					);
				}
			}

			return new Response(
				// $this->renderView('_partials/elements/files-list.html.twig', [
				$this->renderView('@FileManagerSystem/_partials/elements/files-list.html.twig', [
					'files' => $uploaded,
					'current_folder' => $folder,
				// ]) . $this->renderView('_partials/elements/stream-flash.html.twig'),
				]) . $this->renderView('@FileManagerSystem/_partials/elements/stream-flash.html.twig'),
				200,
				['Content-Type' => 'text/vnd.turbo-stream.html']
			);
			/* return $this->redirectToRoute(self::FILE_MANAGER, [
				'folder' => $folder
			]); */
		}


		// Move File
		$moveFileForm = $this->createForm(MoveFileType::class, null);
		$moveFileForm->handleRequest($request);

		/* $moveFileForms = [];

		if (!empty($files)) {
			foreach ($files as $key => $file) {
				$moveFileForms[$key] = $this->createForm(MoveFileType::class)->createView();
			}
			// dd($moveFileForms);
		} */

		// dd($moveFileForm);
		if ($moveFileForm->isSubmitted() && $moveFileForm->isValid()) {
			/* $files = $moveFileForm->get('file')->getData();
			
			if ($files) {
				try {
					$uploaded = $this->fmService->upload($files, $this->fmService->getDefaultDirectory(), "", false);
					
					$this->addFlash(
						'success',
						$this->translator->trans('file_manager.file_uploaded_successfully')
					);
				} catch (FileException $e) {
					$this->addFlash(
						'danger',
						$this->translator->trans('file_manager.error_while_uploading', ['%message%' => $e])
					);
				}
			}

			return $this->redirectToRoute(self::FILE_MANAGER, [
				'folder' => $folder
			]); */
		}


		// return $this->render('home/index.html.twig', [
		return $this->render('@FileManagerSystem/file-manager/index.html.twig', [
			'folder_form' => $createFolderForm,
			'file_form' => $uploadFileForm,
			'move_file_form' => $moveFileForm,
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
