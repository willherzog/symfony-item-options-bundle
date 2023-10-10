<?php

namespace WHSymfony\WHItemOptionsBundle\Entity;

use WHPHP\Util\ArrayUtil;

use WHSymfony\WHItemOptionsBundle\Exception\InvalidMultipleOptionInstancesException;

/**
 * An indexer for item options which allows retrieving the option values via key to minimize memory impact.
 * Intended to serve as the default implementation for the object instance methods required by ItemWithOptions.
 *
 * @author Will Herzog <willherzog@gmail.com>
 */
trait OptionsIndexTrait
{
	private ?array $optionsIndex = null;

	/**
	 * This is to allow subclasses to use a different property name without needing to redefine other methods.
	 */
	protected function getOptionsProperty(): string
	{
		return 'options';
	}

	/**
	 * @internal Indexes the options currently present in $this->options.
	 */
	final protected function createOptionsIndex(): void
	{
		$this->optionsIndex = [];

		/** @var ItemOption $option */
		foreach( $this->{$this->getOptionsProperty()} as $i => $option ) {
			$key = $option->getKey();

			if( !isset($this->optionsIndex[$key]) ) {
				if( self::getOptionDefinitions()->get($key)?->persistWithMultipleRows() ) {
					$i = (array) $i;
				}

				$this->optionsIndex[$key] = $i;
			} elseif( is_array($this->optionsIndex[$key]) ) {
				$this->optionsIndex[$key][] = $i;
			} elseif( self::getOptionDefinitions()->has($key) && !self::getOptionDefinitions()->get($key)->persistWithMultipleRows() ) {
				throw new InvalidMultipleOptionInstancesException($key);
			} else {
				// For backwards compatibility, convert existing index to array (i.e. if options with this key do not have an associated definition)
				$this->optionsIndex[$key] = [$this->optionsIndex[$key], $i];
			}
		}
	}

	/**
	 * This method should be called whenever the contents of the options property are modified (e.g. when adding or removing options).
	 */
	final public function resetOptionsIndex(): void
	{
		$this->optionsIndex = null;
	}

	/**
	 * @inheritDoc
	 */
	final public function hasOption(string $key): bool
	{
		if( $this->optionsIndex === null ) {
			$this->createOptionsIndex();
		}

		return isset($this->optionsIndex[$key]);
	}

	/**
	 * @uses ArrayUtil::hasKeys()
	 *
	 * @param string[] $keys
	 */
	public function hasOptions(array $keys, $requireAll = false): bool
	{
		if( $this->optionsIndex === null ) {
			$this->createOptionsIndex();
		}

		return ArrayUtil::hasKeys($this->optionsIndex, $keys, $requireAll);
	}

	/**
	 * @inheritDoc
	 */
	final public function getOption(string $key, bool $createIfNotFound = false): null|array|ItemOption
	{
		if( $this->optionsIndex === null ) {
			$this->createOptionsIndex();
		}

		$optionsProperty = $this->getOptionsProperty();

		if( isset($this->optionsIndex[$key]) ) {
			if( is_array($this->optionsIndex[$key]) ) {
				$optionsForKey = [];

				foreach( $this->optionsIndex[$key] as $i ) {
					$optionsForKey[] = $this->$optionsProperty->get($i);
				}

				return $optionsForKey;
			} else {
				$i = $this->optionsIndex[$key];

				return $this->$optionsProperty->get($i);
			}
		} elseif( $createIfNotFound ) {
			$optionClass = static::getOptionClass();
			$option = new $optionClass();

			$option->setKey($key);
			$this->addOption($option);

			$this->optionsIndex = null;

			return $option;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	final public function getOptionValue(string $key, mixed $fallback = null): mixed
	{
		if( $this->optionsIndex === null ) {
			$this->createOptionsIndex();
		}

		$optionsProperty = $this->getOptionsProperty();

		if( !isset($this->optionsIndex[$key]) ) {
			if( $fallback === null ) {
				$definition = self::getOptionDefinitions()->get($key);

				return $definition !== null ? $definition->getDefaultValue() : $fallback;
			} else {
				return $fallback;
			}
		} elseif( is_array($this->optionsIndex[$key]) ) {
			$valuesForKey = [];

			foreach( $this->optionsIndex[$key] as $i ) {
				$valuesForKey[] = $this->$optionsProperty->get($i)->getValue();
			}

			return $valuesForKey;
		} else {
			$i = $this->optionsIndex[$key];

			return $this->$optionsProperty->get($i)->getValue();
		}
	}
}
