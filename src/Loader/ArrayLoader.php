<?php

namespace Tito10047\BatchSelectionBundle\Loader;


use InvalidArgumentException;
use Tito10047\BatchSelectionBundle\Normalizer\IdentifierNormalizerInterface;

final class ArrayLoader implements IdentityLoaderInterface
{

	public function supports(mixed $source): bool
	{
		return is_array($source);
	}

	public function loadAllIdentifiers(?IdentifierNormalizerInterface $resolver, mixed $source, ?string $identifierPath): array
	{
		if (!is_array($source)) {
			throw new InvalidArgumentException('Source must be an array.');
		}

		$identifiers = [];

		foreach ($source as $item) {
			$identifiers[] = $resolver->normalize($item, $identifierPath);
		}

		return $identifiers;
	}


	public function getTotalCount(mixed $source): int {
		return count($source);
	}
}