<?php

namespace WHSymfony\WHItemOptionsBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use WHSymfony\WHItemOptionsBundle\Form\Type\FormTypeExtension;

/**
 * @author Will Herzog <willherzog@gmail.com>
 */
class WHItemOptionsBundle extends AbstractBundle
{
	public function configure(DefinitionConfigurator $definition): void
	{
	}

	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		$container->services()
			->set('wh_item_options.type_extension.form', FormTypeExtension::class)
				->tag('form.type_extension', ['priority' => 50])
		;
	}
}
