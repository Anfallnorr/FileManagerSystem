<?php

namespace Anfallnorr\FileManagerSystem;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FileManagerSystem extends Bundle
{
    // Vous pouvez personnaliser ici des comportements spécifiques du bundle.
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
