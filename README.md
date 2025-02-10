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
