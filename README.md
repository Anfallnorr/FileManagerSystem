# FileManagerSystem

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
    $this->fmService->setDefaultDirectory('/var/uploads');
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

### 📌 Set a New Default Upload Directory

```php
$directory = $fmService
    ->setDefaultDirectory('/var/uploads')
    ->getDefaultDirectory();
// Returns: /path/to/project/var/uploads
```

---

### 📁 1.1. Listing Directories

The `getDirs()` method allows you to explore the file system with support for exclusions, depth control, and relative paths.

**Method Signature:**

```php
getDirs(
    string $path = '/', 
    string $excludeDir = '', 
    string|array|null $depth = '== 0'
): array
```

**Parameters:**
- `$path` — Base directory path to search within
- `$excludeDir` — Directory name pattern to exclude from results
- `$depth` — Depth filter using comparison operators (`==`, `>`, `<`)

**Return Value:**
- `array` — List of directories with absolute and relative paths

#### Examples

**Basic usage:**

```php
$dirs = $fmService->getDirs();
// Returns directories found at depth 0 in the default directory
```

**List directories inside a specific subfolder:**

```php
$dirs = $fmService->getDirs(path: 'uploads');
// Returns all directories inside /uploads at depth 0
```

**Control search depth:**

```php
$dirs = $fmService->getDirs(path: 'uploads', depth: '== 1');
// Returns only directories exactly 1 level below /uploads
```

**Exclude specific directories:**

```php
$dirs = $fmService->getDirs(path: 'uploads', excludeDir: 'temp');
// Returns all directories except those containing "temp" in their path
```

**Combine all parameters:**

```php
$dirs = $fmService->getDirs(path: 'uploads', excludeDir: 'temp', depth: '== 1');
// Returns directories at depth 1 under "uploads", excluding folders containing "temp"
```

---

### 📁 1.2. Creating Directories

Create a new directory within the default directory.

**Method Signature:**

```php
createDir(
    string $directory, 
    bool $returnDetails = false
): array
```

**Parameters:**
- `$directory` — Directory name (will be slugified automatically)
- `$returnDetails` — If `true`, returns detailed path information

**Return Value:**
- `array` — Directory details (if `$returnDetails` is `true`)

#### Examples

**Simple directory creation:**

```php
$fmService->createDir(directory: 'Hello World!');
// Creates directory: /path/to/project/public/uploads/hello-world
```

**Get detailed information:**

```php
$details = $fmService->createDir(directory: 'Hello World!', returnDetails: true);
// Returns:
// [
//     'absolute' => '/var/www/absolute/path/hello-world',
//     'relative' => '/path/hello-world',
//     'ltrimmed_relative' => 'path/hello-world',
//     'foldername' => 'hello-world'
// ]
```

---

## 📄 2. File Management

### 📄 2.1. Listing Files

The `getFiles()` method offers complete control over file search: depth, extension, folder filtering, and more.

**Method Signature:**

```php
getFiles(
    string $path = '/', 
    string|array|null $depth = '== 0', 
    ?string $folder = null, 
    ?string $ext = null
): array|bool
```

**Parameters:**
- `$path` — Base directory path to search within
- `$depth` — Depth filter using comparison operators (`==`, `>`, `<`)
- `$folder` — Filter files by folder name (partial match)
- `$ext` — Filter by file extension (without dot)

**Return Value:**
- `array` — List of files with paths and metadata
- `false` — If no files are found

#### Examples

**Get files from default directory:**

```php
$files = $fmService->getFiles();
// Returns files at depth 0 from the default directory, or false if none found
```

**Get files from a subfolder:**

```php
$files = $fmService->getFiles(path: 'uploads');
// Returns files from /uploads at depth 0
```

**Limit search by depth:**

```php
$files = $fmService->getFiles(path: 'uploads', depth: '== 1');
// Returns files located exactly 1 level below /uploads
```

**Filter by folder name:**

```php
$files = $fmService->getFiles(path: 'uploads', folder: 'images');
// Returns only files within folders containing "images"
```

**Filter by file extension:**

```php
$files = $fmService->getFiles(path: 'uploads', ext: 'jpg');
// Returns only .jpg files
```

**Combine all filters:**

```php
$files = $fmService->getFiles(
    path: 'uploads', 
    depth: '== 2', 
    folder: 'products', 
    ext: 'png'
);
// Returns .png files inside folders containing "products", at depth 2 under "uploads"
```

---

### 📄 2.2. Creating Files

Create a new file with optional content.

**Method Signature:**

```php
createFile(
    string $filename, 
    string $content = '<!DOCTYPE html><html lang="en"><body style="background: #ffffff;"></body></html>'
): void
```

**Parameters:**
- `$filename` — File name (will be slugified automatically)
- `$content` — File content (defaults to basic HTML template)

**Return Value:**
- `void`

#### Examples

**Create an HTML file with custom content:**

```php
$fmService->createFile(
    filename: 'Hello World.html',
    content: 'Hello World! I\'m Js Info'
);
// Creates: /path/to/project/public/uploads/hello-world.html
```

**Create a file with default HTML template:**

```php
$fmService->createFile(filename: 'welcome.html');
// Creates file with default HTML content
```

---

## 🔧 3. Utilities

### 🧩 Retrieve All Available MIME Types

Get a complete list of supported MIME types.

```php
$mimeTypes = $fmService->getMimeTypes();
// Returns: ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', ...]
```

### 🧩 Get MIME Type for Specific Extension

Retrieve the MIME type for a given file extension.

```php
$mimeType = $fmService->getMimeType(key: 'pdf');
// Returns: 'application/pdf'
```

### 🧩 Create URL-Friendly Slugs

Convert any string into a URL-safe slug.

```php
$slug = $fmService->createSlug('Hello World !');
// Returns: 'hello-world'
```

---

## 🎨 4. Optional: Twig Integration

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
