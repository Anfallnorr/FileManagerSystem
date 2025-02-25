<?php

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

class FileManagerController extends AbstractController
{
	public function __construct(
		private FileManagerService $fmService,
		private TranslatorInterface $translator
	) {
		$fmService
			->setDefaultDirectory('/var/uploads')
			->setRelativeDirectory('/var/uploads');
	}

	/* #[Route('/', name: 'app_index')]
	public function index()
	{
		return $this->redirectToRoute('app_file_manager');
	} */

	#[Route('/file-manager/{folder}', name: 'app_file_manager', defaults: ['folder' => ''], methods: ['POST', 'GET'], requirements: ['folder' => '.+'])]
	public function home(Request $request, string $folder): Response
	{
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
		$allFolders = $this->fmService->getDirs($path = '/', $excludeDir = "", $depth = null);
		$allFiles = $this->fmService->getFiles($path = '/', $depth = null);
		
		if (!empty($folder)) {
			$this->fmService->setDefaultDirectory('/var/uploads/' . $folder); // Example for personnal folder space: '/var/uploads/' . $his->getUser()->getId()
		}
		
		// Check if path is a valid folder
		/* if (!is_dir($this->fmService->getDefaultDirectory())) {
			throw $this->createNotFoundException('Folder not found');
		} */

		$uploadUrl = $this->generateUrl('app_file_manager', [
			'folder' => $folder
		]);

		$folders = $this->fmService->getDirs();
		$files = $this->fmService->getFiles();
		
		// Folder creation
		$createFolderForm = $this->createForm(CreateFolderType::class);
		$createFolderForm->handleRequest($request);
		
		if ($createFolderForm->isSubmitted() && $createFolderForm->isValid()) {
			$folderName = $createFolderForm->get('folderName')->getData();

			if (!$this->fmService->exists($folderName)) {
				// $this->fmService->createDir($folderName);
				$created = $this->fmService->createDir($folderName, true);
				
				$this->addFlash(
					'success',
					$this->translator->trans('file_manager.folder_created_successfully')
				);
			} else {
				$this->addFlash(
					'warning',
					$this->translator->trans('file_manager.the_folder_already_exists', ['%foldername%' => $folderName])
				);
			}


			return new Response(
				$this->renderView('@FileManagerSystem/_partials/elements/folders-list.html.twig', [
					'folders' => $created,
					'current_folder' => $folder,
				]) . $this->renderView('@FileManagerSystem/_partials/elements/stream-flash.html.twig'),
				200,
				['Content-Type' => 'text/vnd.turbo-stream.html'] // Le type de contenu pour Turbo Stream
			);
			/* return $this->redirectToRoute('app_file_manager', [
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
					$uploaded = $this->fmService->upload($files, $this->fmService->getDefaultDirectory(), "", true);
					
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
				$this->renderView('@FileManagerSystem/_partials/elements/files-list.html.twig', [
					'files' => $uploaded,
					'current_folder' => $folder,
				]) . $this->renderView('@FileManagerSystem/_partials/elements/stream-flash.html.twig'),
				200,
				['Content-Type' => 'text/vnd.turbo-stream.html']
			);
			/* return $this->redirectToRoute('app_file_manager', [
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

			return $this->redirectToRoute('app_file_manager', [
				'folder' => $folder
			]); */
		}


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

	#[Route('/file-manager/file/serve/{filename}/{folder}', name: 'app_file_manager_serve', defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
	public function serveFile(string $filename, string $folder): BinaryFileResponse
	{
		// File directory
		// $fmService = $this->fmService;

		// Base directory (this should now handle both cases)
		$baseDirectory = $this->fmService->getDefaultDirectory();


		// Full path of the requested file
		if (empty($folder)) {
			$filePath = $baseDirectory . '/' . $filename;
		} else {
			$filePath = rtrim($baseDirectory, '/') . '/' . trim($folder, '/') . '/' . ltrim($filename, '/');
		}

		if (!file_exists($filePath)) {
			throw $this->createNotFoundException('Fichier introuvable.');
			// throw $this->createNotFoundException('File not found');
		}


		// Retourne le fichier en tant que réponse
		return new BinaryFileResponse($filePath, 200, [
			'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE, // Online display (for images)
		]);
	}

	/* #[Route('/file/move/{filename}/{folder}', name: 'app_file_manager_move', defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
	public function moveFile(string $filename, string $folder): BinaryFileResponse
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

	#[Route('/file-manager/file/delete/{filename}/{folder}', name: 'app_file_manager_delete_file', defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	public function deleteFile(string $filename, string $folder): Response
	{
		// File directory
		// $fmService = $this->fmService;
		
		// Relative path of the requested file
		if (!empty($folder)) {
			$filePath = $folder . '/' . $filename;
		} else {
			$filePath = $filename;
		}

		if ($this->fmService->exists($filePath)) {
			$this->fmService->remove($filePath);

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


		return $this->redirectToRoute('app_file_manager', [
			'folder' => $folder
		]);
	}

	#[Route('/file-manager/folder/delete/{dirname}/{folder}', name: 'app_file_manager_delete_folder', defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	public function deleteFolder(string $folder, string $dirname): Response
	{
		// File directory
		// $fmService = $this->fmService;

		// Relative path of the requested file
		if (!empty($folder)) {
			$filePath = $folder . '/' . $dirname;
		} else {
			$filePath = $dirname;
		}
		
		if ($this->fmService->exists($filePath)) {
			$this->fmService->remove($filePath);

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


		return $this->redirectToRoute('app_file_manager', [
			'folder' => $folder
		]);
	}

	#[Route('/file-manager/files/mass-delete/{folder}', name: 'app_file_manager_mass_delete_folder', defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	public function massDelete(Request $request, string $folder): Response
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
	
					if ($this->fmService->exists($folderPath)) {
						$this->fmService->remove($folderPath);
					}
				}
	
				$this->addFlash(
					'success',
					$this->translator->trans('file_manager.folders_successfully_mass_deleted')
				);
			}/* else {
				$this->addFlash(
					'warning',
					$this->translator->trans('file_manager.no_folders_selected')
				);
			} */
	
			if (!empty($filesToDelete)) {
				foreach ($filesToDelete as $file) {
					// Chemin complet du fichier demandé
					if (!empty($folder)) {
						$filePath = $folder . '/' . $file;
					} else {
						$filePath = $file;
					}
	
					if ($this->fmService->exists($filePath)) {
						$this->fmService->remove($filePath);
					}
				}
	
				$this->addFlash(
					'success',
					$this->translator->trans('file_manager.files_successfully_mass_deleted')
				);
			}/* else {
				$this->addFlash(
					'warning',
					$this->translator->trans('file_manager.no_files_selected')
				);
			} */
		} else {
			$this->addFlash(
				'warning',
				$this->translator->trans('file_manager.no_files_or_folders_selected')
			);
		}


		return $this->redirectToRoute('app_file_manager', [
			'folder' => $folder
		]);
	}
}
