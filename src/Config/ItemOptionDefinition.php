<?php

namespace WHSymfony\WHItemOptionsBundle\Config;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

use WHSymfony\WHItemOptionsBundle\Entity\ItemWithOptions;

/**
 * A definition for an item option.
 * All config options are optional.
 *
 * @author Will Herzog <willherzog@gmail.com>
 */
final class ItemOptionDefinition
{
	private readonly array $config;

	public function __construct(array $unresolvedConfig)
	{
		$resolver = new OptionsResolver();

		$resolver->define('multiple')
			->allowedTypes('bool')
			->default(false)
			->info('Controls whether array values are stored as multiple database rows (arrays are always serialized otherwise).')
		;

		$resolver->define('enum_type')
			->allowedTypes('string', 'null')
			->default(null)
			->info('FQCN for a backed enum (i.e. PHP enumerator with a scalar equivalent for each of its cases) to use as the type for this option. With this set, the values do not need to be serialized in the database. Note: If used in conjunction with a Symfony EnumType form field, its "class" option should be the same as this.')
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

		$resolvedConfig = $resolver->resolve($unresolvedConfig);

		if( $resolvedConfig['enum_type'] !== null ) {
			$enumType = $resolvedConfig['enum_type'];

			if( !enum_exists($enumType) || !is_subclass_of($enumType, \BackedEnum::class) ) {
				throw new InvalidOptionsException(sprintf('The option "enum_type" with value %s is expected to be the FQCN for a backed enum, but either no such enum exists or it is not a backed enum.', $resolvedConfig['enum_type']));
			}

			$defaultValue = $resolvedConfig['default'];

			if( $defaultValue !== null && (!($defaultValue instanceof \BackedEnum) || $enumType::tryFrom($defaultValue->value) === null) ) {
				throw new InvalidOptionsException(sprintf('When the option "enum_type" is not NULL, the option "default" with value %s must either be NULL or the scalar equivalent for a case of the PHP enumerator referenced by the "enum_type" option.', $resolvedConfig['default']));
			}
		}

		$this->config = $resolvedConfig;
	}

	/**
	 * @internal
	 */
	public function hostItemMeetsRequirements(ItemWithOptions $item): bool
	{
		if( is_callable($this->config['requirement_callback']) ) {
			return (bool) $this->config['requirement_callback']($item);
		}

		return true;
	}

	/**
	 * @internal
	 */
	public function persistWithMultipleRows(): bool
	{
		return $this->config['multiple'];
	}

	/**
	 * @internal
	 */
	public function getDefaultValue(): mixed
	{
		return $this->config['multiple'] ? [] : $this->config['default'];
	}

	/**
	 * @internal
	 */
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

	/**
	 * @internal Prepare value to be persisted.
	 */
	public function normalizeValue(mixed &$value): void
	{
		if( $this->config['enum_type'] !== null && is_a($value, $this->config['enum_type']) ) {
			$value = $value->value;
		}
	}

	/**
	 * @internal Restore value from its persisted form.
	 */
	public function deNormalizeValue(mixed &$value): void
	{
		if( $this->config['enum_type'] !== null && (is_string($value) || is_int($value)) ) {
			$enumType = $this->config['enum_type'];

			$value = $enumType::tryFrom($value) ?? $value;
		}
	}
}
