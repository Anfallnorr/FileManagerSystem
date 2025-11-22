<?php

// anfallnorr/file-manager-system/src/FileManagerSystem.php
namespace Anfallnorr\FileManagerSystem;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
// use Symfony\Component\DependencyInjection\Extension\Extension;
// use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
// use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class FileManagerSystem extends AbstractBundle
{
	/**
	 * Configure Twig to recognize bundle templates
	 * This method is called BEFORE other bundles are configured
	 */
	public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		// Register template paths for this bundle
		// Templates will be accessible via @FileManagerSystem/ namespace
		$builder->prependExtensionConfig(name: 'twig', config: [
			'paths' => [
				$this->getPath() . '/templates' => 'FileManagerSystem'
			]
		]);
	}

	public function configure(DefinitionConfigurator $definition): void
	{
		// Configure les valeurs par défaut
		$definition->rootNode()
			->children()
				// ->scalarNode('kernel_directory')->defaultValue('/')->end()
				->scalarNode(name: 'relative_directory')->defaultValue(value: '/public/uploads')->end()
				->scalarNode(name: 'default_directory')->defaultValue(value: '/public/uploads')->end()
			->end()
		;
	}

	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		// Obtenir le chemin du projet
		$projectDir = $builder->getParameter(name: 'kernel.project_dir');

		// charger un fichier XML, PHP ou YAML
		$container->import(resource: $this->getPath() . '/config/services.yaml');

		// vous pouvez également ajouter ou remplacer des paramètres et des services
		$container->parameters()
			->set(name: 'fms.kernel_directory', value: $projectDir)
			->set(name: 'fms.default_directory', value: $projectDir . $config['default_directory'])
			->set(name: 'fms.relative_directory', value: $config['relative_directory'])
		;

		/* $container->services()
			->load('Anfallnorr\\FileManagerSystem\\', $this->getPath() . '/src/*')
			->exclude($this->getPath() . '/src/{DependencyInjection,Resources,Tests}')
		; */

		/* $container->services()
			->load('Anfallnorr\\FileManagerSystem\\', $this->getPath() . '/src/*')
			->exclude($this->getPath() . '/src/{DependencyInjection,Resources,Tests}');

		$container->parameters()
			->set('fms.kernel_directory', $projectDir)
			->set('fms.default_directory', $projectDir . $config['default_directory'])
			->set('fms.relative_directory', $config['relative_directory']); */

		// Ajouter les services Filesystem et AsciiSlugger
		// $builder->register(Filesystem::class)->setAutowired(true)->setPublic(true);
		// $builder->register(AsciiSlugger::class)->setAutowired(true)->setPublic(true);
	}

	/* public function configureRoutes(RoutingConfigurator $routes, array $config): void
	{
		// Charger les routes depuis les controllers avec attributs
		$routes->import(resource: $this->getPath() . '/src/Controller/', type: 'attribute')
			->prefix(prefix: '/files-manager');
	} */

	/* public function getContainerExtension(): ?ExtensionInterface
	{
		if (null === $this->extension) {
			$this->extension = new class extends Extension {
				public function load(array $configs, ContainerBuilder $container): void
				{
					// Configuration si nécessaire
				}

				public function getAlias(): string
				{
					return 'file_manager_system';
				}
			};
		}
		return $this->extension;
	} */

	public function getPath(): string
	{
		return \dirname(path: __DIR__);
	}
}
