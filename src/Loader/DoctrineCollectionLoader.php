<?php

namespace Tito10047\BatchSelectionBundle\Loader;

use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use Tito10047\BatchSelectionBundle\Normalizer\IdentifierNormalizerInterface;

/**
 * Loader responsible for extracting identifiers from Doctrine Collection objects.
 */
final class DoctrineCollectionLoader implements IdentityLoaderInterface
{
	private const DEFAULT_IDENTIFIER_PATH = 'id';

	/**
	 * @inheritDoc
	 */
	public function supports(mixed $source): bool
	{
		return $source instanceof Collection;
	}

	/**
	 * @inheritDoc
	 */
	public function getTotalCount(mixed $source): int
	{
		if (!$this->supports($source)) {
			throw new InvalidArgumentException('Source must be a Doctrine Collection.');
		}

		/** @var Collection $source */
		return $source->count();
	}

	/**
	 * @param string $identifierPath *
	 *
	 * @inheritDoc
	 */
	public function loadAllIdentifiers(?IdentifierNormalizerInterface $resolver, mixed $source, ?string $identifierPath): array
	{
		if (!$this->supports($source)) {
			throw new InvalidArgumentException('Source must be a Doctrine Collection.');
		}

		/** @var Collection $source */
		$identifiers = [];

		foreach ($source as $item) {
			$identifiers[] = $resolver->normalize($item, $identifierPath);
		}

		return $identifiers;
	}
}