# FileManagerSystem
A Symfony bundle for file management (move, copy, delete, resize, etc.).

```sh
composer require anfallnorr/file-manager-system
```

## Usage

### Add to /config/bundles.php

```bash
Anfallnorr\FileManagerSystem\FileManagerSystem::class => ['all' => true],
```

### In controller

#### Init

```php
use Anfallnorr\FileManagerSystem\Service\FileManagerService;
```
```php
public function __construct(
    private FileManagerService $fileManagerService
) {}
```
```php
$fmService = $this->fileManagerService;

dd($fmService);

dd($fmService->getDefaultDirectory());
dd($fmService->setDefaultDirectory('/var/www/uploads')->getDefaultDirectory()); // /path/to/folder/var/www/uploads
dd($fmService->getMimeTypes());
dd($fmService->getMimeType('docx'));
dd($fmService->createSlug('Hello World !')); // hello-world

$fmService->createDir('Hello World !'); // create hello-world directory in default directory path
$fmService->createFile('Hello World.html', 'Hello World! I\'m Js info'); // create hello-world.html file in default directory path
```

#### Optionnal

```yaml
# app/config/packages/twig.yaml
twig:
    form_themes: ['bootstrap_5_layout.html.twig']
```

#### Your controller

```php
use Anfallnorr\FileManagerSystem\Form\CreateFolderType;
use Anfallnorr\FileManagerSystem\Form\UploadFileType;
use Anfallnorr\FileManagerSystem\Service\FileManagerService;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
```
```php
public function __construct(
    private FileManagerService $fileManagerService
) {}
```
```php
#[Route('/', name: 'app_home')]
public function index(Request $request): Response
{
    $fmService = $this->fileManagerService;
    
    // Création de dossier
    $createFolderForm = $this->createForm(CreateFolderType::class);
    $createFolderForm->handleRequest($request);
    
    if ($createFolderForm->isSubmitted() && $createFolderForm->isValid()) {
        $fmService->setDefaultDirectory('/var/uploads'); // Example for personnal folder space: '/var/uploads/' . $his->getUser()->getId()
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
                // $file->move($defaultDirectory, $fileName);
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
    
    
    return $this->render('home/index.html.twig', [
        'folder_form' => $createFolderForm,
        'file_form' => $uploadFileForm
    ]);
}
```
