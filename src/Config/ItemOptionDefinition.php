<?php

namespace WHSymfony\WHItemOptionsBundle\Config;

use Symfony\Component\OptionsResolver\OptionsResolver;

use WHSymfony\WHItemOptionsBundle\Entity\ItemWithOptions;

/**
 * A definition for an item option.
 *
 * @author Will Herzog <willherzog@gmail.com>
 */
class ItemOptionDefinition
{
	protected readonly array $config;

	public function __construct(array $unresolvedConfig)
	{
		$resolver = new OptionsResolver();

		$resolver->define('multiple')
			->allowedTypes('bool')
			->default(false)
			->info('Controls whether array values are stored as multiple database rows (arrays are always serialized otherwise).')
		;

		$resolver->define('default')
			->default(null)
			->info('Fallback value for when this option has not been persisted (this will always be an empty array if "multiple" is TRUE).')
		;

		$resolver->define('persist_default')
			->allowedTypes('bool')
			->default(false)
			->info('Whether to persist this option when the value matches the default/fallback value. Not applicable if the value is already covered by one of the allow_* options.')
		;

		$resolver->define('allow_empty_array')
			->allowedTypes('bool')
			->default(false)
			->info('Whether to persist this option when the value is an empty array.')
		;

		$resolver->define('allow_empty_string')
			->allowedTypes('bool')
			->default(false)
			->info('Whether to persist this option when the value is an empty string.')
		;

		$resolver->define('allow_zero')
			->allowedTypes('bool')
			->default(false)
			->info('Whether to persist this option when the value is the number zero (either an integer or a float).')
		;

		$resolver->define('allow_false')
			->allowedTypes('bool')
			->default(false)
			->info('Whether to persist this option when the value is FALSE.')
		;

		$resolver->define('allow_null')
			->allowedTypes('bool')
			->default(false)
			->info('Whether to persist this option when the value is NULL.')
		;

		$resolver->define('requirement_callback')
			->allowedTypes('callable', 'null')
			->default(null)
			->info('Boolean-returning function to determine whether given host item instance fulfills any non-static requirements for having this option.')
		;

		$this->config = $resolver->resolve($unresolvedConfig);
	}

	public function hostItemMeetsRequirements(ItemWithOptions $item): bool
	{
		if( is_callable($this->config['requirement_callback']) ) {
			return (bool) $this->config['requirement_callback']($item);
		}

		return true;
	}

	public function persistWithMultipleRows(): bool
	{
		return $this->config['multiple'];
	}

	public function getDefaultValue(): mixed
	{
		return $this->config['multiple'] ? [] : $this->config['default'];
	}

	public function shouldPersistValue(mixed $value): bool
	{
		$checkDefault = function ($value): bool {
			$defaultValue = $this->getDefaultValue();

			if( !empty($defaultValue) && $value === $defaultValue ) {
				return $this->config['persist_default'];
			}

			return true;
		};

		return match($value) {
			[] => $this->config['allow_empty_array'],
			'' => $this->config['allow_empty_string'],
			0, 0.0 => $this->config['allow_zero'],
			false => $this->config['allow_false'],
			null => $this->config['allow_null'],
			default => $checkDefault($value)
		};
	}
}
