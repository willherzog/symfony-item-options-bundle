<?php

namespace WHSymfony\WHItemOptionsBundle\Entity;

use WHSymfony\WHItemOptionsBundle\Config\ItemOptionDefinitionBag;

/**
 * Interface defining required methods for items with options.
 * OptionsIndexTrait, if used, providers all necessary non-static methods.
 *
 * @uses KeyValueInterface
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
	 * Return array of definitions for this item's options.
	 */
	static public function getOptionDefinitions(): ItemOptionDefinitionBag;

	public function addOption(ItemOption $option): void;

	public function removeOption(ItemOption $option): void;

	public function hasOption(string $key): bool;

	public function getOption(string $key): null|array|ItemOption;

	public function getOptionValue(string $key, mixed $default = null): mixed;
}
