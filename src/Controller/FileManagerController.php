<?php

namespace Anfallnorr\FileManagerSystem\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Anfallnorr\FileManagerSystem\Form\CreateFolderType;
use Anfallnorr\FileManagerSystem\Form\UploadFileType;
use Anfallnorr\FileManagerSystem\Form\RenameFileType;
use Anfallnorr\FileManagerSystem\Form\MoveFileType;

class FileManagerController extends AbstractController
{
    #[Route('/file-manager', name: 'file_manager')]
    public function index(Request $request, Filesystem $filesystem): Response
    {
        $defaultDirectory = $this->getParameter('file_manager_system.default_directory');

        // Création de dossier
        $createFolderForm = $this->createForm(CreateFolderType::class);
        $createFolderForm->handleRequest($request);
        if ($createFolderForm->isSubmitted() && $createFolderForm->isValid()) {
            $folderName = $createFolderForm->get('folderName')->getData();
            $folderPath = $defaultDirectory . '/' . $folderName;

            if (!$filesystem->exists($folderPath)) {
                $filesystem->mkdir($folderPath);
                $this->addFlash('success', 'Dossier créé avec succès.');
            }
        }

        // Upload de fichier
        $uploadFileForm = $this->createForm(UploadFileType::class);
        $uploadFileForm->handleRequest($request);
        if ($uploadFileForm->isSubmitted() && $uploadFileForm->isValid()) {
            $file = $uploadFileForm->get('file')->getData();
            if ($file) {
                $fileName = $file->getClientOriginalName();
                try {
                    $file->move($defaultDirectory, $fileName);
                    $this->addFlash('success', 'Fichier uploadé avec succès.');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l’upload.');
                }
            }
        }

        return $this->render('@FileManagerSystem/index.html.twig', [
            'createFolderForm' => $createFolderForm->createView(),
            'uploadFileForm' => $uploadFileForm->createView(),
        ]);
    }
}
