<?php

namespace WHSymfony\WHItemOptionsBundle\Form\EventListener;

use InvalidArgumentException,LogicException;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\{FormEvent,FormEvents};

use WHPHP\Util\ArrayUtil;

use WHSymfony\WHItemOptionsBundle\Entity\ItemWithOptions;

/**
 * An event subscriber for populating the data of item options within forms.
 * Do not add this directly to a form: use the "item_option" form option on individual fields.
 *
 * @author Will Herzog <willherzog@gmail.com>
 */
class ItemOptionsFormListener implements EventSubscriberInterface
{
	/**
	 * @inheritDoc
	 */
	static public function getSubscribedEvents(): array
	{
		return [
			FormEvents::PRE_SET_DATA => ['onPreSetData', 999], // This should always be called FIRST
			FormEvents::POST_SUBMIT => ['onPostSubmit', -999] // This should always be called LAST
		];
	}

	protected function getItemFromFormData(FormInterface $form): ItemWithOptions
	{
		if( $form->isRoot() ) {
			throw new LogicException('The "item_option" form option is not supported on a root form.');
		}

		while( ($form = $form->getParent()) !== null ) {
			$dataClassValue = $form->getConfig()->getDataClass();

			if( $dataClassValue !== null && is_subclass_of($dataClassValue, ItemWithOptions::class) ) {
				$dataObject = $form->getData();
			}
		}

		if( !isset($dataObject) ) {
			throw new LogicException('A field with the "item_option" form option must have an ancestor with an instance of ItemWithOptions as its underlying data.');
		} elseif( !($dataObject instanceof ItemWithOptions) ) { // in *theory* this should never happen, but just in case...
			throw new LogicException('The underlying form data is not an instance of ItemWithOptions even though the form\'s data class requires it to be.');
		}

		return $dataObject;
	}

	public function onPreSetData(FormEvent $event): void
	{
		$formField = $event->getForm();
		$hostItem = $this->getItemFromFormData($formField);
		$optionName = $formField->getConfig()->getOption('item_option');

		if( !$hostItem::getOptionDefinitions()->has($optionName) ) {
			throw new InvalidArgumentException(sprintf('Undefined option name "%s" for host item class "%s"', $optionName, get_class($hostItem)));
		}

		$optionDefinition = $hostItem::getOptionDefinitions()->get($optionName);

		if( !$optionDefinition->hostItemMeetsRequirements($hostItem) ) {
			throw new InvalidArgumentException(sprintf('Host item instance of class "%s" does not satisfy the requirements for option "%s"', get_class($hostItem), $optionName));
		}

		$event->setData($hostItem->getOptionValue($optionName));
	}

	public function onPostSubmit(FormEvent $event): void
	{
		$formField = $event->getForm();
		$hostItem = $this->getItemFromFormData($formField);
		$optionName = $formField->getConfig()->getOption('item_option');

		if( !$hostItem::getOptionDefinitions()->has($optionName) ) {
			throw new InvalidArgumentException(sprintf('Undefined option name "%s" for host item class "%s"', $optionName, get_class($hostItem)));
		}

		$optionDefinition = $hostItem::getOptionDefinitions()->get($optionName);

		if( $optionDefinition->persistWithMultipleRows() ) {
			$options = $hostItem->getOption($optionName);
			$values = $formField->getData();

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
						$hostItem->removeOption($option);
					}
				}

				foreach( $values as $value ) {
					if( $optionDefinition->shouldPersistValue($value) ) {
						$this->createAndAddItemOption($hostItem, $optionName, $value);
					}
				}
			} else {
				foreach( $options as $option ) {
					$hostItem->removeOption($option);
				}
			}
		} else {
			$option = $hostItem->getOption($optionName);
			$value = $formField->getData();

			if( $optionDefinition->shouldPersistValue($value) ) {
				if( $option === null ) {
					$this->createAndAddItemOption($hostItem, $optionName, $value);
				} else {
					$option->setValue($value);
				}
			} elseif( $option !== null ) {
				$hostItem->removeOption($option);
			}
		}
	}

	protected function createAndAddItemOption(ItemWithOptions $item, string $key, mixed $value): void
	{
		$optionClass = $item::getOptionClass();
		$option = new $optionClass();

		$option
			->setKey($key)
			->setValue($value)
		;

		$item->addOption($option);
	}
}
