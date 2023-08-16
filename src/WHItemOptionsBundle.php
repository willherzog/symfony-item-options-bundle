<?php

namespace WHSymfony\WHItemOptionsBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author Will Herzog <willherzog@gmail.com>
 */
class WHItemOptionsBundle extends AbstractBundle
{
	public function configure(DefinitionConfigurator $definition): void
	{
		$definition->rootNode()
			->children()
			->end()
		;
	}

	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		/*
		$container->services()
			->set('whform.type_extension.button', ButtonTypeExtension::class)
				->tag('form.type_extension', ['priority' => 99])
			->set('whform.type_extension.base', BaseTypeExtension::class)
				->args([$config['form']['id_attributes_use_dashes']])
				->tag('form.type_extension', ['priority' => 97])
		;
		*/
	}
}
