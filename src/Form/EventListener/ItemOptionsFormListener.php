<?php

namespace WHSymfony\WHItemOptionsBundle\Form\EventListener;

use InvalidArgumentException, LogicException;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\{FormEvent,FormEvents};
use Symfony\Component\OptionsResolver\OptionsResolver;

use WHPHP\Util\ArrayUtil;
use WHDoctrine\Entity\KeyValueInterface;

use WHSymfony\WHItemOptionsBundle\Config\ItemOptionDefinitionBag;
use WHSymfony\WHItemOptionsBundle\Entity\ItemWithOptions;

/**
 * An event subscriber for populating the data of item options within forms.
 *
 * @author Will Herzog <willherzog@gmail.com>
 */
class ItemOptionsFormListener implements EventSubscriberInterface
{
	protected ?string $optionClass;
	protected $optionHostItem;

	protected OptionsResolver $configResolver;
	protected array $optionsConfig = [];

	/**
	 * @inheritDoc
	 */
	static public function getSubscribedEvents(): array
	{
		return [
			FormEvents::POST_SET_DATA => ['onPostSetData', 999], // This should always be called FIRST
			FormEvents::POST_SUBMIT => ['onPostSubmit', -999] // This should always be called LAST
		];
	}

	/**
	 * Create the event subscriber: set option entity class and, if needed, override the method for retrieving the item host from the event object.
	 */
	public function __construct(?string $optionClass = null, callable $optionHostItem = null)
	{
		if( $optionClass !== null && !in_array(KeyValueInterface::class, class_implements($optionClass), true) ) {
			throw new InvalidArgumentException(sprintf('$optionClass must implement "%s".', KeyValueInterface::class));
		}

		$this->optionClass = $optionClass;

		if( $optionHostItem !== null ) {
			$this->optionHostItem = $optionHostItem;
		} else {
			$this->optionHostItem = fn(FormEvent $event) => $event->getData();
		}

		$resolver = new OptionsResolver();

		$resolver
			->setDefaults([
				'field' => null, // if different from option name
				'parent' => null, // if not main form
			])
			->setAllowedTypes('field', ['null','string'])
			->setAllowedTypes('parent', ['null','string'])
		;

		$this->configResolver = $resolver;
	}

	/**
	 * Configure a form field to be associated with an item option.
	 *
	 * @throws InvalidArgumentException If called with duplicate $optionName
	 */
	public function addOptionField(string $optionName, array $config = []): static
	{
		if( isset($this->optionsConfig[$optionName]) ) {
			throw new InvalidArgumentException(sprintf('Field configuration for option "%s" has already been added.', $optionName));
		}

		$this->optionsConfig[$optionName] = $this->configResolver->resolve($config);

		return $this;
	}

	/**
	 * @internal
	 */
	public function onPostSetData(FormEvent $event): void
	{
		$item = ($this->optionHostItem)($event);
		$form = $event->getForm();

		if( $item === null ) {
			return;
		}

		if( !($item instanceof ItemWithOptions) ) {
			throw new LogicException(sprintf('The option host item must be an instance of %s', ItemWithOptions::class));
		}

		$optionHostItemClass = get_class($item);
		/** @var ItemOptionDefinitionBag */
		$optionDefinitions = $optionHostItemClass::getOptionDefinitions();

		foreach( $this->optionsConfig as $optionName => $optionConfig ) {
			if( !$optionDefinitions->has($optionName) ) {
				throw new \RuntimeException(sprintf('Undefined option name "%s" for host item class "%s"', $optionName, $optionHostItemClass));
			}

			$optionDefinition = $optionDefinitions->get($optionName);

			if( !$optionDefinition->hostItemMeetsRequirements($item) ) {
				throw new \UnexpectedValueException(sprintf('Host item instance of class "%s" does not satisfy the requirements for option "%s"', $optionHostItemClass, $optionName));
			}

			$field = $this->getFieldFromForm($form, $optionName, $optionConfig);

			if( $field === null ) {
				continue;
			}

			$value = $item->getOptionValue($optionName);

			$field->setData($value);
		}
	}

	/**
	 * @internal
	 */
	public function onPostSubmit(FormEvent $event): void
	{
		$item = ($this->optionHostItem)($event);
		$form = $event->getForm();

		if( $item === null ) {
			return;
		}

		if( !($item instanceof ItemWithOptions) ) {
			throw new LogicException(sprintf('The option host item must be an instance of %s', ItemWithOptions::class));
		}

		$optionHostItemClass = get_class($item);
		/** @var ItemOptionDefinitionBag */
		$optionDefinitions = $optionHostItemClass::getOptionDefinitions();

		if( $this->optionClass === null ) {
			$this->optionClass = $optionHostItemClass::getOptionClass();
		}

		foreach( $this->optionsConfig as $optionName => $optionConfig ) {
			if( !$optionDefinitions->has($optionName) ) {
				throw new \RuntimeException(sprintf('Undefined option name "%s" for host item class "%s"', $optionName, $optionHostItemClass));
			}

			$field = $this->getFieldFromForm($form, $optionName, $optionConfig);

			if( $field === null ) {
				continue;
			}

			$definition = $optionDefinitions->get($optionName);

			if( $definition->persistWithMultipleRows() ) {
				$options = $item->getOption($optionName);
				$values = $field->getData();

				if( $options !== null && !is_array($options) ) {
					throw new LogicException(sprintf('Option "%s" is defined as being persisted with multiple rows but the previously persisted value is not an array.', $optionName));
				}

				if( $values !== null && !is_array($values) ) {
					throw new LogicException(sprintf('Option "%s" is defined as being persisted with multiple rows but the value for the asociated form field is not an array.', $optionName));
				}

				if( is_array($values) && count($values) > 0 ) {
					foreach( $options as $option ) {
						$optionValue = $option->getValue();

						if( in_array($optionValue, $values, true) ) {
							ArrayUtil::removeValue($values, $optionValue);
						} else {
							$item->removeOption($option);
						}
					}

					foreach( $values as $value ) {
						if( $definition->shouldPersistValue($value) ) {
							$this->createAndAddItemOption($item, $optionName, $value);
						}
					}
				} else {
					foreach( $options as $option ) {
						$item->removeOption($option);
					}
				}
			} else {
				$option = $item->getOption($optionName);
				$value = $field->getData();

				if( $definition->shouldPersistValue($value) ) {
					if( $option === null ) {
						$this->createAndAddItemOption($item, $optionName, $value);
					} else {
						$option->setValue($value);
					}
				} elseif( $option !== null ) {
					$item->removeOption($option);
				}
			}
		}
	}

	protected function getFieldFromForm(FormInterface $form, string $optionName, array $optionConfig): ?FormInterface
	{
		$fieldName = $optionConfig['field'] ?? $optionName;

		if( $optionConfig['parent'] ) {
			if( $form->has($optionConfig['parent']) ) {
				$parentForm = $form->get($optionConfig['parent']);
			}
		} else {
			$parentForm = $form;
		}

		if( isset($parentForm) && $parentForm->has($fieldName) ) {
			return $parentForm->get($fieldName);
		}

		return null;
	}

	protected function createAndAddItemOption(ItemWithOptions $item, string $name, mixed $value): void
	{
		$option = new $this->optionClass();

		$option
			->setKey($name)
			->setValue($value)
		;

		$item->addOption($option);
	}
}
