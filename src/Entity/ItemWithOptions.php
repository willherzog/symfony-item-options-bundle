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
	 * @return Collection|ItemOption[]
	 */
	public function getOptions(): Collection;

	public function hasOption(string $key): bool;

	public function getOption(string $key): null|array|ItemOption;

	public function getOptionValue(string $key, mixed $default = null): mixed;
}
