<?php

namespace Tito10047\BatchSelectionBundle\Service;

use Tito10047\BatchSelectionBundle\Exception\NormalizationFailedException;
use Tito10047\BatchSelectionBundle\Converter\MetadataConverterInterface;
use Tito10047\BatchSelectionBundle\Loader\IdentityLoaderInterface;
use Tito10047\BatchSelectionBundle\Normalizer\IdentifierNormalizerInterface;
use Tito10047\BatchSelectionBundle\Storage\StorageInterface;

final class SelectionManager implements SelectionManagerInterface {

	public function __construct(
		private readonly StorageInterface              $storage,
		private readonly IdentifierNormalizerInterface $normalizer,
		private readonly ?string                       $identifierPath,
		private readonly MetadataConverterInterface    $metadataConverter,
		/** @var IdentityLoaderInterface[] */
		private readonly iterable                      $loaders,
	) {
	}

	public function registerSource(string $context, mixed $source, ?IdentifierNormalizerInterface $normalizer = null): SelectionInterface {
		$loader = $this->findLoader($source);

		$selection = new Selection($context, $this->identifierPath, $this->storage, $this->normalizer, $this->metadataConverter);

		foreach ($source as $item) {
			if (!$this->normalizer->supports($item)) {
				throw new NormalizationFailedException('Item is not an object.');
			}
		}
		$selection->rememberAll($loader->loadAllIdentifiers($normalizer??$this->normalizer, $source, $this->identifierPath));

		return $selection;
	}

	public function getSelection(string $context): SelectionInterface {
		return new Selection($context, $this->identifierPath, $this->storage, $this->normalizer, $this->metadataConverter);
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