<?php

namespace Tito10047\PersistentSelectionBundle\Service;

interface SelectionManagerInterface {

	public function registerSource(string $context, mixed $source): SelectionInterface;

	public function getSelection(string $context): SelectionInterface;

}