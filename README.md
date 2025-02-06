# FileManagerSystem
A Symfony bundle for file management (move, copy, delete, resize, etc.).

```sh
composer require anfallnorr/file-manager-system
```

## Usage

```bash
use Anfallnorr\FileManagerSystem\Service\FileManagerService;
$fms = new FileManagerService($this->getParameter('kernel.project_dir'));
