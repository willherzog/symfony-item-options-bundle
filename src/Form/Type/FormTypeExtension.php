<?php

namespace WHSymfony\WHItemOptionsBundle\Form\Type;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use WHSymfony\WHItemOptionsBundle\Form\EventListener\ItemOptionsFormListener;

/**
 * @author Will Herzog <willherzog@gmail.com>
 */
class FormTypeExtension extends AbstractTypeExtension
{
	/**
	 * @inheritDoc
	 */
	public static function getExtendedTypes(): iterable
	{
		return [FormType::class];
	}

	/**
	 * @inheritDoc
	 */
	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver
			->define('item_option')
			->allowedTypes('string', 'null')
			->default(null)
			->info('Specify item option to use for persisting this form field\'s value. There must be an ancestor form with an instance of ItemWithOptions as its underlying data and that instance must have this item option defined.')
		;
	}

	/**
	 * @inheritDoc
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		if( $options['item_option'] !== null ) {
			$builder->addEventSubscriber(new ItemOptionsFormListener());
		}
	}
}
