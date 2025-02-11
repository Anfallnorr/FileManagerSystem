<?php

namespace Anfallnorr\FileManagerSystem\Controller;

use Anfallnorr\FileManagerSystem\Form\CreateFolderType;
// use Anfallnorr\FileManagerSystem\Form\MoveFileType;
// use Anfallnorr\FileManagerSystem\Form\RenameFileType;
use Anfallnorr\FileManagerSystem\Form\UploadFileType;
use Anfallnorr\FileManagerSystem\Service\FileManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
// use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\Translation\TranslatorInterface;

class FileManagerController extends AbstractController
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
		return $this->redirectToRoute('app_home');
	}

    #[Route('/home/{folder}', name: 'app_home', defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
    public function home(Request $request, string $folder): Response
    {
		$fmService = $this->fileManagerService;
		
		if (!empty($folder)) {
			$myFolder = $fmService->getDefaultDirectory() . DIRECTORY_SEPARATOR . $folder;
			$fmService->setDefaultDirectory('/var/uploads/' . $folder); // Example for personnal folder space: '/var/uploads/' . $his->getUser()->getId()
		} else {
			$myFolder = $fmService->getDefaultDirectory();
			$fmService->setDefaultDirectory('/var/uploads'); // Example for personnal folder space: '/var/uploads/' . $his->getUser()->getId()
		}

        // Vérifier si le chemin est un dossier valide
        if (!is_dir($myFolder)) {
            throw $this->createNotFoundException('Folder not found');
        }

		$uploadUrl = $this->generateUrl('app_home', [
			'folder' => $folder
		]);

		$folders = $fmService->getDirs();
		$files = $fmService->getFiles();
		$allFolders = $fmService->getDirs($path = '/', $excludeDir = "", $depth = null);
		$allFiles = $fmService->getFiles($path = '/', $depth = null);
		

		// Création de dossier
		$createFolderForm = $this->createForm(CreateFolderType::class);
		$createFolderForm->handleRequest($request);
		
		if ($createFolderForm->isSubmitted() && $createFolderForm->isValid()) {
			$folderName = $createFolderForm->get('folderName')->getData();

			if (!$fmService->exists($folderName)) {
				$fmService->createDir($folderName);

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

			return $this->redirectToRoute('app_home', [
				'folder' => $folder
			]);
		}


		// Upload de fichier
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
					$uploaded = $fmService->upload($files, $fmService->getDefaultDirectory(), false);

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

			return $this->redirectToRoute('app_home', [
				'folder' => $folder
			]);
		}


		return $this->render('@FileManagerSystem/index.html.twig', [
			'createFolderForm' => $createFolderForm->createView(),
			'uploadFileForm' => $uploadFileForm->createView(),
            'current_folder' => $folder,
			'folders' => $folders,
			'files' => $files,
			'allFolders' => $allFolders,
			'allFiles' => $allFiles
		]);
	}

	#[Route('/file/{filename}/{folder}', name: 'app_file', defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
	public function serveFile(string $filename, string $folder): BinaryFileResponse
	{
		// File directory
		$fmService = $this->fileManagerService;
		$fmService->setDefaultDirectory('/var/uploads');

		$baseDirectory = $fmService->getDefaultDirectory();


		// Full path of the requested file
		if (empty($folder)) {
			$filePath = $baseDirectory . '/' . $filename;
		} else {
			$filePath = rtrim($baseDirectory, '/') . '/' . trim($folder, '/') . '/' . ltrim($filename, '/');
		}

		if (!file_exists($filePath)) {
			throw $this->createNotFoundException('Fichier introuvable.');
		}


		// Retourne le fichier en tant que réponse
		return new BinaryFileResponse($filePath, 200, [
			'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE, // Online display (for images)
		]);
	}

	#[Route('/file/delete/{filename}/{folder}', name: 'app_delete_file', defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	public function deleteFile(string $filename, string $folder): Response
	{
		dd($filename);
		// File directory
		$fmService = $this->fileManagerService;
		
		// Chemin complet du fichier demandé
		if (!empty($folder)) {
			$filePath = $folder . '/' . $filename;
		} else {
			$filePath = $filename;
		}

		// dump($filename);
		// dump($folder);
		// dd($filePath);

		if ($fmService->exists($filePath)) {
			dd($filePath);
			$fmService->remove($filePath);

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


		return $this->redirectToRoute('app_home', [
			'folder' => $folder
		]);
	}

	#[Route('/folder/delete/{dirname}/{folder}', name: 'app_delete_folder', defaults: ['folder' => ''], methods: ['DELETE'], requirements: ['folder' => '.+'])]
	public function deleteFolder(string $folder, string $dirname): Response
	{
		// File directory
		$fmService = $this->fileManagerService;

		// Chemin complet du fichier demandé
		if (!empty($folder)) {
			$filePath = $folder . '/' . $dirname;
		} else {
			$filePath = $dirname;
		}
		
		if ($fmService->exists($filePath)) {
			$fmService->remove($filePath);

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


		return $this->redirectToRoute('app_home', [
			'folder' => $folder
		]);
	}
}
