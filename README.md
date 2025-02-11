# FileManagerSystem
[//]: # (FileManagerSystem est un bundle Symfony permettant de gérer facilement les fichiers et répertoires : création, suppression, déplacement, redimensionnement d'images, gestion des MIME types, etc.)
FileManagerSystem is a Symfony bundle to easily manage files and directories: creation, deletion, moving, resizing images, managing MIME types, etc.

[//]: # (**Demo:** https://symfotest.js-info.fr/home)

## Installation

Install FileManagerSystem via Composer

```sh
composer require anfallnorr/file-manager-system
```

## Configuration

### Add to /config/bundles.php

Register the bundle in config/bundles.php

```php
# config/bundles.php
return [
    ...
    Anfallnorr\FileManagerSystem\FileManagerSystem::class => ['all' => true],
];
```

## Usage

### Initialize in a Controller

```php
public function __construct(
    private FileManagerService $fileManagerService
) {}
```
```php
$fmService = $this->fileManagerService;
```

### Examples

```php
// Get the default upload directory path
$defaultDirectory = $fmService->getDefaultDirectory(); // /path/to/folder/public/uploads

// Change the default upload directory
$directory = $fmService->setDefaultDirectory('/var/www/uploads')->getDefaultDirectory(); // /path/to/folder/var/www/uploads

// Retrieve available MIME types
$mimeTypes = $fmService->getMimeTypes(); // array

// Get the MIME type of a specific extension
$mimeType = $fmService->getMimeType('pdf'); // application/pdf

// Create a URL-friendly slug from a string
$string = $fmService->createSlug('Hello World !'); // hello-world

// Create a directory named "hello-world" inside the default directory
$fmService->createDir('Hello World !');

// Create a file named "hello-world.html" inside the default directory with content
$fmService->createFile('Hello World.html', 'Hello World! I\'m Js info');
```

### Optional Configuration

If you are using Twig, add Bootstrap form themes in config/packages/twig.yaml

```yaml
# app/config/packages/twig.yaml
twig:
    form_themes: ['bootstrap_5_layout.html.twig']
```
