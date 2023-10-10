<?php

namespace WHSymfony\WHItemOptionsBundle\Entity;

use Doctrine\Common\Collections\Collection;

use WHSymfony\WHItemOptionsBundle\Config\ItemOptionDefinitionBag;

/**
 * Interface defining required methods for items with options.
 * OptionsIndexTrait, if used, provides some of the necessary non-static methods.
 *
 * @author Will Herzog <willherzog@gmail.com>
 */
interface ItemWithOptions
{
	/**
	 * Return name of entity class implementing KeyValueInterface.
	 */
	static public function getOptionClass(): string;

	/**
	 * Return instance of class extending from ItemOptionDefinitionBag.
	 */
	static public function getOptionDefinitions(): ItemOptionDefinitionBag;

	public function addOption(ItemOption $option): void;

	public function removeOption(ItemOption $option): void;

	/**
	 * Retrieve all options for this item.
	 *
	 * @return Collection|ItemOption[]
	 */
	public function getOptions(): Collection;

	/**
	 * Determine whether option(s) with the specified key exist.
	 */
	public function hasOption(string $key): bool;

	/**
	 * Get the option or options (if any) with the specified key (otherwise NULL).
	 *
	 * @param string $key The option key
	 * @param bool $createIfNotFound Whether to automatically create (and return) an option instance with the specified key when none yet exists
	 */
	public function getOption(string $key, bool $createIfNotFound = false): null|array|ItemOption;

	/**
	 * Get option value(s).
	 *
	 * @param string $key The option key
	 * @param mixed $fallback Value returned if the key is not found; if NULL (the default), will use default value from option definition (when available)
	 */
	public function getOptionValue(string $key, mixed $default = null): mixed;
}
