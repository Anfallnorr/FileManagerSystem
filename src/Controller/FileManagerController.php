<?php

namespace Anfallnorr\FileManagerSystem\Controller;

use Anfallnorr\FileManagerSystem\Form\CreateFolderType;
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
	public function __construct(
		private FileManagerService $fileManagerService,
		private TranslatorInterface $translator
	) {
		$fileManagerService->setDefaultDirectory('/var/uploads');
	}

	#[Route('/', name: 'app_index')]
	public function index()
	{
		return $this->redirectToRoute('app_file_manager');
	}

	#[Route('/home/{folder}', name: 'app_file_manager', defaults: ['folder' => ''], methods: ['POST', 'GET'], requirements: ['folder' => '.+'])]
	public function home(Request $request, string $folder): Response
	{
		// $fmService = $this->fileManagerService;

		// $fmService = new FileManagerService($this->getParameter('file_manager_system.kernel_directory'), $this->getParameter('file_manager_system.default_directory'), new Filesystem(), new AsciiSlugger());
		// $fmService = new FileManagerService($this->getParameter('kernel.project_dir'), $this->getParameter('kernel.project_dir') . '/var/www/uploads', new Filesystem(), new AsciiSlugger());

		/* $defaultDirectory = $fmService->getDefaultDirectory(); // /path/to/folder/public/uploads
		$directory = $fmService->setDefaultDirectory('/var/www/uploads')->getDefaultDirectory(); // /path/to/folder/var/www/uploads
		$mimeTypes = $fmService->getMimeTypes(); // array
		$mimeType = $fmService->getMimeType('pdf'); // application/pdf
		$string = $fmService->createSlug('Hello World !'); // hello-world

		$fmService->createDir('Hello World !'); // create hello-world directory in default directory path
		$fmService->createFile('Hello World.html', 'Hello World! I\'m Js info'); // create hello-world.html file in default directory path */


		$breadcrumb = explode('/', $folder);

		// Retrieve global files and folders first before changing the default path
		$allFolders = $this->fileManagerService->getDirs($path = '/', $excludeDir = "", $depth = null);
		$allFiles = $this->fileManagerService->getFiles($path = '/', $depth = null);

		if (!empty($folder)) {
			$this->fileManagerService->setDefaultDirectory('/var/uploads/' . $folder); // Example for personnal folder space: '/var/uploads/' . $his->getUser()->getId()
		}

		// Check if path is a valid folder
		/* if (!is_dir($this->fileManagerService->getDefaultDirectory())) {
			throw $this->createNotFoundException('Folder not found');
		} */

		$uploadUrl = $this->generateUrl('app_file_manager', [
			'folder' => $folder
		]);

		$folders = $this->fileManagerService->getDirs();
		$files = $this->fileManagerService->getFiles();

		// Folder creation
		$createFolderForm = $this->createForm(CreateFolderType::class);
		$createFolderForm->handleRequest($request);

		if ($createFolderForm->isSubmitted() && $createFolderForm->isValid()) {
			$folderName = $createFolderForm->get('folderName')->getData();

			if (!$this->fileManagerService->exists($folderName)) {
				$this->fileManagerService->createDir($folderName);

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

			return $this->redirectToRoute('app_file_manager', [
				'folder' => $folder
			]);
		}

		// File upload
		$uploadFileForm = $this->createForm(UploadFileType::class, null, [
			'user' => null, // $user->getId() for example,
			'route' => $uploadUrl,
			'current_folder' => $folder
		]);
		$uploadFileForm->handleRequest($request);

		if ($uploadFileForm->isSubmitted() && $uploadFileForm->isValid()) {
			$files = $uploadFileForm->get('file')->getData();

			if ($files) {
				try {
					$uploaded = $this->fileManagerService->upload($files, $this->fileManagerService->getDefaultDirectory(), false);

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
			]);
		}


		return $this->render('home/index.html.twig', [
			'folder_form' => $createFolderForm,
			'file_form' => $uploadFileForm,
			'breadcrumb' => $breadcrumb,
			'breadcrumb_link' => '',
			'current_folder' => $folder,
			'folders' => $folders,
			'files' => $files,
			'allFolders' => $allFolders,
			'allFiles' => $allFiles
		]);
	}

	#[Route('/file/serve/{filename}/{folder}', name: 'app_file_manager_serve', defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
	public function serveFile(string $filename, string $folder): BinaryFileResponse
	{
		// $fmService = $this->fileManagerService;
		
		$baseDirectory = $this->fileManagerService->getDefaultDirectory();

		// Full path of the requested file
		if (empty($folder)) {
			$filePath = $baseDirectory . '/' . $filename;
		} else {
			$filePath = rtrim($baseDirectory, '/') . '/' . trim($folder, '/') . '/' . ltrim($filename, '/');
		}

		if (!file_exists($filePath)) {
			throw $this->createNotFoundException('Fichier introuvable.');
		}


		// Returns the file as a response
		return new BinaryFileResponse($filePath, 200, [
			'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE, // Online display (for images)
		]);
	}
	
	#[Route('/file/delete/{filename}/{folder}', name: 'app_file_manager_delete_file', defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	public function deleteFile(string $filename, string $folder): Response
	{
		// $fmService = $this->fileManagerService;

		// Relative path of the requested file
		if (!empty($folder)) {
			$filePath = $folder . '/' . $filename;
		} else {
			$filePath = $filename;
		}

		if ($this->fileManagerService->exists($filePath)) {
			$this->fileManagerService->remove($filePath);

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

	#[Route('/folder/delete/{dirname}/{folder}', name: 'app_file_manager_delete_folder', defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	public function deleteFolder(string $folder, string $dirname): Response
	{
		// $fmService = $this->fileManagerService;

		// Relative path of the requested file
		if (!empty($folder)) {
			$filePath = $folder . '/' . $dirname;
		} else {
			$filePath = $dirname;
		}

		if ($this->fileManagerService->exists($filePath)) {
			$this->fileManagerService->remove($filePath);

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

	#[Route('/files/mass-delete/{folder}', name: 'app_file_manager_mass_delete_folder', defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	public function massDelete(Request $request, string $folder): Response
	{
		// $fmService = $this->fileManagerService;

		// Retrieves selected and added file names via JavaScript into JSON
		$filesToDelete = json_decode($request->get('filesToDelete'));

		if (!empty($filesToDelete)) {
			foreach ($filesToDelete as $file) {
				// Relative path of the requested file
				if (!empty($folder)) {
					$filePath = $folder . '/' . $file;
				} else {
					$filePath = $file;
				}

				if ($this->fileManagerService->exists($filePath)) {
					$this->fileManagerService->remove($filePath);
				}
			}

			$this->addFlash(
				'success',
				$this->translator->trans('file_manager.files_successfully_mass_deleted')
			);
		} else {
			$this->addFlash(
				'warning',
				$this->translator->trans('file_manager.no_files_selected')
			);
		}


		return $this->redirectToRoute('app_file_manager', [
			'folder' => $folder
		]);
	}
}
