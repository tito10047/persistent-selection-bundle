<?php

namespace Tito10047\BatchSelectionBundle\Normalizer;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ArrayNormalizer implements IdentifierNormalizerInterface
{
	public function supports(mixed $item): bool
	{
		return is_array($item);
	}

	public function normalize(mixed $item, ?string $identifierPath): string|int
	{

		if (is_array($item) && array_key_exists($identifierPath, $item)) {
			return $item[$identifierPath];
		}

		throw new \RuntimeException('Extracted value is not a scalar.');
	}
}