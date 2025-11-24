<?php

namespace Tito10047\BatchSelectionBundle\Tests\App\AssetMapper\Src\Loader;

use Tito10047\BatchSelectionBundle\Loader\IdentityLoaderInterface;
use Tito10047\BatchSelectionBundle\Normalizer\IdentifierNormalizerInterface;
use Tito10047\BatchSelectionBundle\Tests\App\AssetMapper\Src\Support\TestList;

/**
 * Jednoduchý testovací loader pre TestList wrapper.
 */
class TestListLoader implements IdentityLoaderInterface
{
    public function supports(mixed $source): bool
    {
        return $source instanceof TestList;
    }

    public function loadAllIdentifiers(?IdentifierNormalizerInterface $resolver, mixed $source, ?string $identifierPath): array
    {
        if (!$this->supports($source)) {
            throw new \InvalidArgumentException('Source musí byť inštancia TestList.');
        }

        if (!$resolver) {
            throw new \InvalidArgumentException('Resolver (IdentifierNormalizerInterface) musí byť poskytnutý.');
        }

        $ids = [];
        foreach ($source->all() as $item) {
            $ids[] = $resolver->normalize($item, $identifierPath);
        }

        return $ids;
    }

    public function getTotalCount(mixed $source): int
    {
        if (!$this->supports($source)) {
            throw new \InvalidArgumentException('Source musí byť inštancia TestList.');
        }

        return count($source->all());
    }

	public function getCacheKey(mixed $source): string {
		if (!$this->supports($source)) {
			throw new \InvalidArgumentException('Source musí byť inštancia TestList.');
		}
		// Deterministický kľúč zo zoznamu položiek
		return 'test_list:' . md5(serialize($source->all()));
	}
}
