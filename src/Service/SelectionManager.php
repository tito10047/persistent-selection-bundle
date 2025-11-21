<?php

namespace Tito10047\BatchSelectionBundle\Service;

use Tito10047\BatchSelectionBundle\Exception\NormalizationFailedException;
use Tito10047\BatchSelectionBundle\Loader\IdentityLoaderInterface;
use Tito10047\BatchSelectionBundle\Normalizer\IdentifierNormalizerInterface;
use Tito10047\BatchSelectionBundle\Storage\StorageInterface;

final class SelectionManager implements SelectionManagerInterface {

	public function __construct(
		private readonly StorageInterface              $storage,
		private readonly IdentifierNormalizerInterface $normalizer,
		private readonly ?string                       $identifierPath,
		/** @var IdentityLoaderInterface[] */
		private readonly iterable                      $loaders,
	) {
	}

	public function registerSource(string $key, mixed $source): SelectionInterface {
		$loader = $this->findLoader($source);

		$selection = new Selection($key, $this->identifierPath, $this->storage, $this->normalizer);

		foreach ($source as $item) {
			if (!$this->normalizer->supports($item)) {
				throw new NormalizationFailedException('Item is not an object.');
			}
		}
		$selection->rememberAll($loader->loadAllIdentifiers($this->normalizer, $source, $this->identifierPath));

		return $selection;
	}

	public function getSelection(string $key): SelectionInterface {
		return new Selection($key, $this->identifierPath, $this->storage, $this->normalizer);
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