<?php

namespace WHSymfony\WHItemOptionsBundle\Exception;

class InvalidMultipleOptionInstancesException extends \RuntimeException
{
	public function __construct(string $optionKey, int $code = 0)
	{
		parent::__construct(sprintf('Found multiple instances of item option "%s" but its definition does not permit there to be more than one.', $optionKey), $code);
	}
}
