<?php

namespace Tito10047\BatchSelectionBundle\Service;

use Tito10047\BatchSelectionBundle\Normalizer\IdentifierNormalizerInterface;

interface SelectionManagerInterface {

	public function registerSource(string $context, mixed $source, ?IdentifierNormalizerInterface $normalizer = null): SelectionInterface;

	public function getSelection(string $context): SelectionInterface;

}