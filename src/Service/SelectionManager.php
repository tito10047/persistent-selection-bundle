<?php

namespace Tito10047\BatchSelectionBundle\Service;

use Tito10047\BatchSelectionBundle\Loader\IdentityLoaderInterface;
use Tito10047\BatchSelectionBundle\Normalizer\IdentifierNormalizerInterface;
use Tito10047\BatchSelectionBundle\Storage\StorageInterface;

final class SelectionManager implements SelectionManagerInterface{

	public function __construct(
		private readonly StorageInterface $storage,
		/** @var IdentityLoaderInterface[] */
		private readonly iterable         $loaders,
		/** @var IdentifierNormalizerInterface[] */
		private readonly iterable $normalizers
	) { }

	public function registerSource(string $key, mixed $source, string $type, ?string $identifierPath = null): SelectionInterface {
		$normalizer = $this->findNormalizer($type);
		$loader    = $this->findLoader($source);

		$selection = new Selection($key, $identifierPath, $this->storage, $normalizer);
		$selection->rememberAll($loader->loadAllIdentifiers($normalizer, $source, $identifierPath));

		return $selection;
	}

	public function getSelection(string $key, string $type, ?string $identifierPath = null): SelectionInterface {
		$normalizer = $this->findNormalizer($type);
		return new Selection($key, $identifierPath, $this->storage, $normalizer);
	}

	private function findNormalizer(string $type): IdentifierNormalizerInterface {
		$normalizer = null;
		foreach ($this->normalizers as $_normalizer) {
			if ($_normalizer->supports($type)) {
				$normalizer = $_normalizer;
				break;
			}
		}
		if ($normalizer === null) {
			throw new \InvalidArgumentException('No suitable normalizer found for the given source.');
		}
		return $normalizer;
	}

	private function findLoader(mixed $source): mixed {
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