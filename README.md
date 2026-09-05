# FileManagerSystem (v1.0.58)

FileManagerSystem is a Symfony bundle that provides easy and intuitive management of files and directories: creation, deletion, moving, MIME type handling, image resizing, and more.

It is designed to simplify file management within any Symfony application.

## ⚠️ State Management

This bundle is **stateful**: it maintains a navigation context (e.g. current directory, browsing state) across requests.

The state is securely isolated per user session, ensuring that each user interacts with their own file system context without interference.

---

## 🚀 Installation

Install the bundle via Composer:

```sh
composer require anfallnorr/file-manager-system
```

---

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

> [!WARNING]
> If you want to use the built-in controller and assets provided by the bundle, create the following configuration files.

**Create** `config/packages/file_manager_system.yaml`:

```yaml
framework:
    asset_mapper:
        paths:
            - '%kernel.project_dir%/vendor/anfallnorr/file-manager-system/assets'
```

**Create** `config/routes/file_manager_system.yaml`:

```yaml
file_manager_system:
    resource: '../../vendor/anfallnorr/file-manager-system/src/Controller/'
    type: attribute
    prefix: /files-manager 
```

---

## 💡 Usage

### Service Injection

Inject the `FileManagerService` into your controller or service:

```php
public function __construct(
    private FileManagerService $fmService
) {
    $this->fmService->setDefaultDirectory(directory: '/var/uploads');
}
```

For convenience in examples below:

```php
$fmService = $this->fmService;
```

---

## 📂 1. Directory Management

### 📌 Get the Default Upload Directory

```php
$defaultDirectory = $fmService->getDefaultDirectory();
// Returns: /path/to/project/public/uploads
```

### 📌 Get the Relative Upload Directory

```php
$relativeDirectory = $fmService->getRelativeDirectory();
// Returns: /uploads
```

### 📌 Set a New Default Upload Directory

```php
$directory = $fmService
    ->setDefaultDirectory(directory: '/var/uploads')
    ->getDefaultDirectory();
// Returns: /path/to/project/var/uploads
```

---

### 📁 1.1. Listing Directories

**`getDirs(string $path = '/', string $excludeDir = '', string|array|null $depth = '== 0'): array`**  
Explore the file system with support for exclusions, depth control, and relative paths.

```php
$dirs = $fmService->getDirs(path: 'uploads', depth: '== 1');
```

**`getDirsTree(string $path = '/', string $excludeDir = ""): array`**  
Retrieve directories in a recursive tree structure, including sub-folders and files metadata.

```php
$tree = $fmService->getDirsTree(path: 'uploads');
```

**`hasDir(): bool`**  
Check if the default directory contains at least one sub-directory.

```php
if ($fmService->hasDir()) {
    // Contains sub-directories
}
```

---

### 📁 1.2. Creating and Cleaning Directories

**`createDir(string $directory, bool $returnDetails = false): array|bool`**  
Create a new directory within the default directory (slugified automatically). Supports nested (`folder/sub`) and multiple (`folder1+folder2`) creations.

```php
$fmService->createDir(directory: 'Hello World!'); // Creates: hello-world
```

**`cleanDir(?string $dir = null): void`**  
Recursively clean a directory by removing empty folders.

```php
$fmService->cleanDir(dir: 'uploads/temp');
```

---

## 📄 2. File Management

### 📄 2.1. Listing and Reading Files

**`getFiles(string $path = '/', string|array|null $depth = '== 0', ?string $folder = null, string|array|null $ext = null): array`**  
Offers complete control over file search: depth, extension, folder filtering.

```php
$files = $fmService->getFiles(path: 'uploads', ext: 'jpg');
```

**`getFileContent(string $relativeFile): string`**  
Read and return the entire content of a file.

```php
$content = $fmService->getFileContent(relativeFile: 'storage/data.json');
```

**`getRemoteFileContent(string $url): string`**  
Fetch content from a remote URL.

```php
$content = $fmService->getRemoteFileContent(url: 'https://example.com/data.json');
```

---

### 📄 2.2. Creating and Uploading Files

**`createFile(string $filename, string $content = '...'): void`**  
Create a new file with optional content.

```php
$fmService->createFile(filename: 'welcome.html', content: '<h1>Hello</h1>');
```

**`upload(UploadedFile|File|array $files, string $folder, string $newName = '', bool $returnDetails = false): array|bool`**  
Upload files, handle slugification, and generate useful metadata.

```php
$uploaded = $fmService->upload(files: $file, folder: '/var/www/uploads', newName: 'my-file', returnDetails: true);
```

---

## 🔄 3. File & Directory Operations

**`exists(?string $filePath = null): bool`**  
Check if a file or directory physically exists.

```php
if ($fmService->exists(filePath: 'images/photo.jpg')) {
    // File exists
}
```

**`copy(string $source, string $destination, bool $override = false): bool`**  
Duplicate a file or directory.

```php
$fmService->copy(source: 'uploads/file.txt', destination: 'backup/file.txt', override: true);
```

**`move(string $origine, string $target, bool $overwrite = false): bool`**  
Move a file or directory to a new location.

```php
$fmService->move(origine: '/uploads/temp/image.jpg', target: '/uploads/final/image.jpg', overwrite: true);
```

**`rename(string $source, string $destination, bool $override = false): bool`**  
Rename a file or directory (automatically slugifies the new name).

```php
$fmService->rename(source: 'Photo Vacances.jpg', destination: 'nouvelle photo');
// Result: nouvelle-photo.jpg
```

**`remove(string $relativePath = ''): bool`**  
Delete a file or a directory (and all its content).

```php
$fmService->remove(relativePath: 'uploads/documents/file.txt'); // Delete specific file
$fmService->remove(); // Delete entire default directory
```

---

## 🎨 4. Media & Utilities

### 🖼️ Image Handling

**`resizeImages(array $files, string $sourceDir, string $targetDir, int $width, int $quality = 100, ?string $suffix = null): array`**  
Resize images while keeping aspect ratio and saving them to a new destination.

```php
$fmService->resizeImages(files: ['img.jpg'], sourceDir: '/source', targetDir: '/target', width: 800, quality: 90, suffix: 'thumb');
```

**`getImageSize(string $filePath): ?array`**  
Get the dimensions of an image (`['width' => int, 'height' => int]`).

```php
$size = $fmService->getImageSize(filePath: 'uploads/photo.jpg');
```

### 📏 Size & MIME

**`getSize(string|array $files, int $totalFileSize = 0): int|float`**  
Calculate the total size in bytes of one or more files.

```php
$bytes = $fmService->getSize(files: $filesArray);
```

**`getSizeName(int|float $size): string`**  
Convert bytes into a human-readable format (o, Ko, Mo, Go).

```php
$readable = $fmService->getSizeName(size: 10485760); // 10.00 Mo
```

**`getMimeTypes(): array`**  
Get a complete list of supported MIME types.

```php
$mimeTypes = $fmService->getMimeTypes();
```

**`getMimeType(string $key): string|array|null`**  
Retrieve the MIME type for a given file extension.

```php
$mimeType = $fmService->getMimeType(key: 'pdf');
```

**`getMimeContent(string $filename): ?string`**  
Detect the real MIME type of a physical file based on its content.

```php
$mimeContent = $fmService->getMimeContent(filename: 'uploads/photo.jpg');
```

### ⬇️ Download & Miscellaneous

**`download(string $name, ?string $directory = null): BinaryFileResponse`**  
Force the download of a single file.

```php
return $fmService->download(name: 'document.pdf');
```

**`downloadBulk(array $names, ?string $directory = null): BinaryFileResponse`**  
Group multiple files into a ZIP archive and force its download.

```php
return $fmService->downloadBulk(names: ['doc1.pdf', 'img.png']);
```

**`createSlug(string $string): string`**  
Convert any string into a URL-safe slug.

```php
$slug = $fmService->createSlug(string: 'Hello World !'); // hello-world
```

---

## 🎨 5. Optional: Twig Integration

If you are using Twig and want Bootstrap-styled forms, add the following to your Twig configuration.

**Edit** `config/packages/twig.yaml`:

```yaml
twig:
    form_themes: ['bootstrap_5_layout.html.twig']
```

---

## 📚 Additional Resources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [AssetMapper Component](https://symfony.com/doc/current/frontend/asset_mapper.html)

---

## 📝 License

This bundle is open-source and available under the MIT License.
