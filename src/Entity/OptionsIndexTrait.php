<?php

namespace WHSymfony\WHItemOptionsBundle\Entity;

use WHPHP\Util\ArrayUtil;

use WHSymfony\WHItemOptionsBundle\Config\ItemOptionDefinitionBag;

/**
 * An indexer for item options which allows retrieving the option values via key to minimize memory impact.
 * Intended to serve as the default implementation for the object instance methods required by ItemWithOptions.
 *
 * @author Will Herzog <willherzog@gmail.com>
 */
trait OptionsIndexTrait
{
	protected ?array $optionsIndex = null;

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
	protected function createOptionsIndex(): void
	{
		$this->optionsIndex = [];
		/** @var ItemOptionDefinitionBag */
		$optionDefinitions = (__CLASS__)::getOptionDefinitions();

		/** @var ItemOption $option */
		foreach( $this->{$this->getOptionsProperty()} as $i => $option ) {
			$key = $option->getKey();

			if( !isset($this->optionsIndex[$key]) ) {
				if( $optionDefinitions->get($key)?->persistWithMultipleRows() ) {
					$i = (array) $i;
				}

				$this->optionsIndex[$key] = $i;
			} elseif( is_array($this->optionsIndex[$key]) ) {
				$this->optionsIndex[$key][] = $i;
			} elseif( $optionDefinitions->has($key) && !$optionDefinitions->get($key)->persistWithMultipleRows() ) {
				throw new \UnexpectedValueException(sprintf('Found multiple instances of item option "%s" but its definition does not permit there to be more than one.', $key));
			} else {
				// For backwards compatibility, convert existing index to array (i.e. if options with this key do not have an associated definition)
				$this->optionsIndex[$key] = [$this->optionsIndex[$key], $i];
			}
		}
	}

	/**
	 * This method should be called whenever the contents of the options property are modified (e.g. when adding or removing options).
	 */
	public function resetOptionsIndex(): void
	{
		$this->optionsIndex = null;
	}

	/**
	 * Determine whether option(s) with the specified key exist.
	 */
	public function hasOption(string $key): bool
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
		return ArrayUtil::hasKeys($this->optionsIndex, $keys, $requireAll);
	}

	/**
	 * Get the option or options (if any) with the specified key (otherwise NULL).
	 *
	 * @return null|ItemOption|ItemOption[]
	 */
	public function getOption(string $key): null|array|ItemOption
	{
		if( $this->optionsIndex === null ) {
			$this->createOptionsIndex();
		}

		$optionsProperty = $this->getOptionsProperty();

		if( !isset($this->optionsIndex[$key]) ) {
			return null;
		} elseif( is_array($this->optionsIndex[$key]) ) {
			$optionsForKey = [];

			foreach( $this->optionsIndex[$key] as $i ) {
				$optionsForKey[] = $this->$optionsProperty->get($i);
			}

			return $optionsForKey;
		} else {
			$i = $this->optionsIndex[$key];

			return $this->$optionsProperty->get($i);
		}
	}

	/**
	 * Get option value(s).
	 *
	 * @param string $key The option key
	 * @param mixed $fallback Value returned if the key is not found; if NULL (the default), will use default value from option definition (when available)
	 */
	public function getOptionValue(string $key, mixed $fallback = null): mixed
	{
		if( $this->optionsIndex === null ) {
			$this->createOptionsIndex();
		}

		$optionsProperty = $this->getOptionsProperty();
		/** @var ItemOptionDefinitionBag */
		$optionDefinitions = (__CLASS__)::getOptionDefinitions();

		if( !isset($this->optionsIndex[$key]) ) {
			if( $fallback === null ) {
				$definition = $optionDefinitions->get($key);

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
