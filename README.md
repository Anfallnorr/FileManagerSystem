# FileManagerSystem
[//]: # (FileManagerSystem est un bundle Symfony permettant de gérer facilement les fichiers et répertoires : création, suppression, déplacement, redimensionnement d'images, gestion des MIME types, etc.)
FileManagerSystem is a Symfony bundle that provides easy and intuitive management of files and directories: creation, deletion, moving, MIME type handling, image resizing, and more.

It is designed to simplify file management within any Symfony application.

[//]: # (**Demo:** https://symfotest.js-info.fr/home)

## 🚀 Installation

Install the bundle via Composer:

```sh
composer require anfallnorr/file-manager-system
```

## ⚙️ Configuration

### 1. Register the Bundle

Add the bundle to your `config/bundles.php` file:

```php
return [
    // ...
    Anfallnorr\FileManagerSystem\FileManagerSystem::class => ['all' => true],
];
```

### 2. AssetMapper Configuration (Optional)

[//]: # (**If you want to use the built-in controller and assets provided by the bundle, create the following configuration files.**)
> [!WARNING]
> If you want to use the built-in controller and assets provided by the bundle, create the following configuration files.

Create `config/packages/file_manager_system.yaml`
```yaml
framework:
    asset_mapper:
        paths:
            - '%kernel.project_dir%/vendor/anfallnorr/file-manager-system/assets'
```

Create `config/routes/file_manager_system.yaml`
```yaml
file_manager_system:
    resource: '../../vendor/anfallnorr/file-manager-system/src/Controller/'
    type: attribute
    prefix: /files-manager 
```

## 💡 Usage

### Injecting the Service

```php
public function __construct(
    private FileManagerService $fmService
) {
    $this->fmService
        ->setDefaultDirectory('/var/uploads')
        ->setRelativeDirectory('/var/uploads');
}
```

```php
$fmService = $this->fmService;
```

### 📚 Examples

#### Get the default upload directory
```php
$defaultDirectory = $fmService->getDefaultDirectory();
// e.g. /path/to/project/public/uploads
```

#### Change the default upload directory
```php
$directory = $fmService
	->setDefaultDirectory(directory: '/var/www/uploads')
	->getDefaultDirectory();
// e.g. /path/to/project/var/www/uploads
```

#### Retrieve all available MIME types
```php
$mimeTypes = $fmService->getMimeTypes();
// returns an array
```

#### Get the MIME type for a specific extension
```php
$mimeType = $fmService->getMimeType(key: 'pdf');
// application/pdf
```

#### Create a URL-friendly slug
```php
$slug = $fmService->createSlug(string: 'Hello World !');
// hello-world
```

#### Create a directory
```php
$fmService->createDir(directory: 'Hello World !', return: false);
```
If return is set to true, the method returns:
```php
[
    'absolute' => "/var/www/absolute/path/hello-world",
    'relative' => "/path/hello-world",
    'ltrimmed_relative' => "path/hello-world",
    'foldername' => "hello-world"
]
```

#### Create a file with optional content
```php
$fmService->createFile(
	filename: 'Hello World.html',
	content: 'Hello World! I\'m Js Info'
);
```

### 🧩 Optional (Twig Integration)

If you are using Twig and want Bootstrap-styled forms, add this in:

`config/packages/twig.yaml`
```yaml
twig:
    form_themes: ['bootstrap_5_layout.html.twig']
```
