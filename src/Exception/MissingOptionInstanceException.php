<?php

namespace WHSymfony\WHItemOptionsBundle\Exception;

class MissingOptionInstanceException extends \RuntimeException
{
	public function __construct(string $optionKey, int $collectionIndex, int $code = 0)
	{
		parent::__construct(sprintf('Expected an instance of item option "%s" at collection index %d but the collection has no entry at this index (indicating a discrepancy between the options index and the actual Doctrine collection).', $optionKey, $collectionIndex), $code);
	}
}
