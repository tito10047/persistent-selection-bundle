<?php

namespace Tito10047\PersistentSelectionBundle\Service;

interface SelectionStorageInterface {

	/**
	 * @param array<int|string> $ids
	 *
	 * @return $this
	 */
	public function setSelection(string $cacheKey, array $ids, int|\DateInterval|null $ttl = null): static;

	public function hasSelection(string $cacheKey): bool;

}