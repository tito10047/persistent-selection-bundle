<?php

namespace Tito10047\PersistentSelectionBundle\Service;

use Tito10047\PersistentSelectionBundle\Normalizer\IdentifierNormalizerInterface;

interface SelectionManagerInterface {

	public function registerSource(string $context, mixed $source): SelectionInterface;

	public function getSelection(string $context): SelectionInterface;

}