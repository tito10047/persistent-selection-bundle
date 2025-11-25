<?php

namespace Tito10047\PersistentSelectionBundle\Service;

interface RegisterSelectionInterface {

	public function registerSource(string $cacheKey, mixed $source): static;

	public function hasSource(string $cacheKey): bool;
}