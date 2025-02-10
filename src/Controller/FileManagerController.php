<?php

namespace Anfallnorr\FileManagerSystem\Controller;

use Anfallnorr\FileManagerSystem\Form\CreateFolderType;
use Anfallnorr\FileManagerSystem\Form\MoveFileType;
use Anfallnorr\FileManagerSystem\Form\RenameFileType;
use Anfallnorr\FileManagerSystem\Form\UploadFileType;
use Anfallnorr\FileManagerSystem\Service\FileManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class FileManagerController extends AbstractController
{
	public function __construct(
		private FileManagerService $fileManagerService,
		private TranslatorInterface $translator
	) {}

    #[Route('/', name: 'app_index')]
    public function index()
    {
		return $this->redirectToRoute('app_home');
	}

    #[Route('/home/{folder}', name: 'app_home', defaults: ['folder' => ''], requirements: ['folder' => '.+'])]
    public function home(Request $request, string $folder): Response
    {
		$fmService = $this->fileManagerService;
		$fmService->setDefaultDirectory('/var/uploads'); // Example for personnal folder space: '/var/uploads/' . $his->getUser()->getId()
		// $directories = $fmService->getDirs();
		// $files = $fmService->getFiles();
		

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

			return $this->redirectToRoute('app_home');
		}


		// Upload de fichier
		$uploadFileForm = $this->createForm(UploadFileType::class);
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

			return $this->redirectToRoute('app_home');
		}


		return $this->render('@FileManagerSystem/index.html.twig', [
			'createFolderForm' => $createFolderForm->createView(),
			'uploadFileForm' => $uploadFileForm->createView(),
		]);
	}
}
