<?php

namespace WHSymfony\WHItemOptionsBundle\Config;

use WHPHP\Bag\GenericBag;
use WHPHP\Exception\InvalidArgumentTypeException;
use WHPHP\Util\ArrayUtil;

/**
 * A container for item option definitions (i.e. instances of ItemOptionDefinition).
 *
 * @author Will Herzog <willherzog@gmail.com>
 */
class ItemOptionDefinitionBag implements GenericBag
{
	protected array $definitions = [];

	public function __construct(array $definitions)
	{
		if( !ArrayUtil::isAssociative($definitions) ) {
			throw new \InvalidArgumentException('The $definitions argument must be an associated array.');
		}

		foreach( $definitions as $name => $definition ) {
			if( !($definition instanceof ItemOptionDefinition) ) {
				if( !is_array($definition) ) {
					throw new \InvalidArgumentException(sprintf('All elements of the $definitions argument must either be instances of %s or arrays themselves.', ItemOptionDefinition::class));
				}

				$definition = new ItemOptionDefinition($definition);
			}

			$this->definitions[$name] = $definition;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function add(string $name, mixed $definition): bool
	{
		if( !is_object($definition) || !($definition instanceof ItemOptionDefinition) ) {
			throw new InvalidArgumentTypeException($definition, ItemOptionDefinition::class);
		}

		if( !isset($this->definitions[$name]) ) {
			$this->definitions[$name] = $definition;

			return true;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function remove(string $name): bool
	{
		if( isset($this->definitions[$name]) ) {
			unset($this->definitions[$name]);

			return true;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function has(string $name): bool
	{
		return isset($this->definitions[$name]);
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $name): ?ItemOptionDefinition
	{
		if( isset($this->definitions[$name]) ) {
			return $this->definitions[$name];
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function all(): array
	{
		return $this->definitions;
	}

	/**
	 * @inheritDoc
	 */
	public function isEmpty(): bool
	{
		return count($this->definitions) === 0;
	}

	public function rewind(): void
	{
		reset($this->definitions);
	}

	public function current(): mixed
	{
		return current($this->definitions);
	}

	public function key(): mixed
	{
		return key($this->definitions);
	}

	public function next(): void
	{
		next($this->definitions);
	}

	public function valid(): bool
	{
		return key($this->definitions) !== null;
	}
}
