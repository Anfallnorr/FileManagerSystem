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

### AssetMapper

Create config/packages/file_manager_system.yaml

```yaml
# config/packages/file_manager_system.yaml
framework:
    asset_mapper:
        paths:
            - '%kernel.project_dir%/vendor/anfallnorr/file-manager-system/assets'
```

## Usage

### Initialize in a Controller

```php
public function __construct(
    private FileManagerService $fmService
) {
    $fmService
        ->setDefaultDirectory('/var/uploads')
        ->setRelativeDirectory('/var/uploads');
}
```
```php
$fmService = $this->fmService;
```

### Examples

```php
// Get the default upload directory path
$defaultDirectory = $fmService->getDefaultDirectory(); // /path/to/folder/public/uploads

// Change the default upload directory
$directory = $fmService->setDefaultDirectory($directory = '/var/www/uploads')->getDefaultDirectory(); // /path/to/folder/var/www/uploads

// Retrieve available MIME types
$mimeTypes = $fmService->getMimeTypes(); // array

// Get the MIME type of a specific extension
$mimeType = $fmService->getMimeType($key = 'pdf'); // application/pdf

// Create a URL-friendly slug from a string
$string = $fmService->createSlug($string = 'Hello World !'); // hello-world

// Create a directory named "hello-world" inside the default directory
$fmService->createDir($directory = 'Hello World !', $return = false);
// if $return is `true`, then an array will be returned:
[
    'absolute' => $this->getDefaultDirectory() . '/hello-world', // Absolute path
    'relative' => $relative, // Relative path of the folder
    'ltrimed_relative' => ltrim($relative, '/'), // Relative path of the folder minus a slash at the beginning of the string
    'foldername' => $dir // The name of the folder created
]

// Create a file named "hello-world.html" inside the default directory with content
$fmService->createFile($filename = 'Hello World.html', $content = 'Hello World! I\'m Js info'); // $content is optional
```

### Optional Configuration

If you are using Twig, add Bootstrap form themes in config/packages/twig.yaml

```yaml
# app/config/packages/twig.yaml
twig:
    form_themes: ['bootstrap_5_layout.html.twig']
```
