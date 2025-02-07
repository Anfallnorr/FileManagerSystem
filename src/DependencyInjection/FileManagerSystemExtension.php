<?php

namespace Anfallnorr\FileManagerSystem\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class FileManagerSystemExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // dd('Extension chargée !'); // ⚠️ TEST pour vérifier si l'extension est bien exécutée
        
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

		// Enregistre la valeur de `default_directory` en tant que paramètre de service
        $container->setParameter('file_manager_system.default_directory', $config['default_directory']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
    }
}
