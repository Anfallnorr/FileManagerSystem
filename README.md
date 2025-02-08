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
dd($fmService->setDefaultDirectory('/var/www/uploads')->getDefaultDirectory());
dd($fmService->getMimeTypes());
dd($fmService->getMimeType('docx'));
dd($fmService->createSlug('Hello World !'));

$fmService->createDir('toto');
$fmService->createFile('index.html', 'Hello World! I\'m Js info');
```
