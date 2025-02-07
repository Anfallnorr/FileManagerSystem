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

```bash
use Anfallnorr\FileManagerSystem\Service\FileManagerService;
$fms = new FileManagerService();
