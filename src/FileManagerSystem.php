<?php

namespace Anfallnorr\FileManagerSystem;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
// use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
// use Symfony\Component\String\Slugger\AsciiSlugger;

final class FileManagerSystem extends AbstractBundle
{
	public function configure(DefinitionConfigurator $definition): void
	{
		$definition->rootNode()
			->children()
				->scalarNode('relative_directory')->defaultValue('/public/uploads')->end()
				->scalarNode('default_directory')->defaultValue('/public/uploads')->end()
			->end()
		;
	}

	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		// Get the project path
		$projectDir = $builder->getParameter('kernel.project_dir');

		// load an XML, PHP or YAML file
		$container->import('../config/services.yaml');

		// you can also add or replace parameters and services
		$container->parameters()
			->set('fms.kernel_directory', $projectDir)
			->set('fms.default_directory', $projectDir . $config['default_directory'])
			->set('fms.relative_directory', $config['relative_directory'])
		;

		// Add Filesystem and AsciiSlugger services
		// $builder->register(Filesystem::class)->setAutowired(true)->setPublic(true);
		// $builder->register(AsciiSlugger::class)->setAutowired(true)->setPublic(true);
	}

	public function getPath(): string
	{
		return \dirname(__DIR__);
	}
}
