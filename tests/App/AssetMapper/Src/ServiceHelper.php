<?php

namespace Tito10047\PersistentSelectionBundle\Tests\App\AssetMapper\Src;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Tito10047\PersistentSelectionBundle\Normalizer\IdentifierNormalizerInterface;
use Tito10047\PersistentSelectionBundle\Service\SelectionManagerInterface;
use Twig\Loader\LoaderInterface;

class ServiceHelper {

	/**
	 * @param IdentifierNormalizerInterface[] $normalizers
	 * @param LoaderInterface[] $loaders
	 */
	public function __construct(
		#[Autowire(service: 'persistent_selection.manager.array')]
		public readonly SelectionManagerInterface $arraySelectionManager,
		public readonly iterable $normalizers,
		public readonly iterable $loaders
	) { }
}