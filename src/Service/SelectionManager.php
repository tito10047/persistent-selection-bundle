<?php

namespace Tito10047\PersistentSelectionBundle\Service;

use Tito10047\PersistentSelectionBundle\Exception\NormalizationFailedException;
use Tito10047\PersistentSelectionBundle\Loader\IdentityLoaderInterface;
use Tito10047\PersistentSelectionBundle\Normalizer\IdentifierNormalizerInterface;
use Tito10047\PersistentSelectionBundle\Storage\StorageInterface;

final class SelectionManager implements SelectionManagerInterface {

 public function __construct(
        private readonly StorageInterface              $storage,
        private readonly IdentifierNormalizerInterface $normalizer,
        private readonly ?string                       $identifierPath,
        /** @var IdentityLoaderInterface[] */
        private readonly iterable                      $loaders,
        private readonly int|\DateInterval|null        $ttl = null,
    ) {
    }

	public function registerSource(string $key, mixed $source, ?IdentifierNormalizerInterface $normalizer = null): SelectionInterface {
		$loader = $this->findLoader($source);

		$selection = new Selection($key, $this->identifierPath, $this->storage, $this->normalizer);

		foreach ($source as $item) {
			if (!$this->normalizer->supports($item)) {
				throw new NormalizationFailedException('Item is not an object.');
			}
		}
		$cacheKey = $loader->getCacheKey($source);
  if (!$selection->hasSelection($cacheKey)) {
            $selection->setSelection(
                $cacheKey,
                $loader->loadAllIdentifiers($normalizer ?? $this->normalizer, $source, $this->identifierPath),
                $this->ttl
            );
        }

		return $selection;
	}

	public function getSelection(string $key): SelectionInterface {
		return new Selection($key, $this->identifierPath, $this->storage, $this->normalizer);
	}


	private function findLoader(mixed $source): IdentityLoaderInterface {
		$loader = null;
		foreach ($this->loaders as $_loader) {
			if ($_loader->supports($source)) {
				$loader = $_loader;
				break;
			}
		}
		if ($loader === null) {
			throw new \InvalidArgumentException('No suitable loader found for the given source.');
		}
		return $loader;
	}
}