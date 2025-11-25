<?php

namespace Tito10047\BatchSelectionBundle\Service;

use Tito10047\BatchSelectionBundle\Converter\MetadataConverterInterface;
use Tito10047\BatchSelectionBundle\Enum\SelectionMode;
use Tito10047\BatchSelectionBundle\Normalizer\IdentifierNormalizerInterface;
use Tito10047\BatchSelectionBundle\Storage\StorageInterface;

final class Selection implements SelectionInterface, RememberAllInterface, HasModeInterface {

	public function __construct(
		private readonly string                        $key,
		private readonly ?string                       $identifierPath,
		private readonly StorageInterface              $storage,
		private readonly IdentifierNormalizerInterface $normalizer,
		private readonly MetadataConverterInterface    $metadataConverter,
	) {
	}

	public function isSelected(mixed $item): bool {
		$has = $this->storage->hasIdentifier($this->key, $item);
		return $this->storage->getMode($this->key) === SelectionMode::INCLUDE ? $has : !$has;
	}

	public function select(mixed $item, null|array|object $metadata = null): static {
		$id        = is_scalar($item) ? $item : $this->normalizer->normalize($item, $this->identifierPath);
		$mode      = $this->storage->getMode($this->key);
		$metaArray = null;
		if ($metadata !== null) {
			$metaArray = is_object($metadata)
				? $this->metadataConverter->convertToStorable($metadata)
				: $metadata;
		}
		if ($mode === SelectionMode::INCLUDE) {
			$this->storage->add($this->key, [$id], $metaArray);
		} else {
			// In EXCLUDE mode, selecting means removing the id from the exclusion list
			$this->storage->remove($this->key, [$id]);
		}
		return $this;
	}

	public function unselect(mixed $item): static {
		$id = is_scalar($item) ? $item : $this->normalizer->normalize($item, $this->identifierPath);
		if ($this->storage->getMode($this->key) === SelectionMode::INCLUDE) {
			$this->storage->remove($this->key, [$id]);
		} else {
			$this->storage->add($this->key, [$id], null);
		}
		return $this;
	}

	public function selectMultiple(array $items, null|array $metadata = null): static {
		$mode = $this->storage->getMode($this->key);
		// If metadata is provided as a map per-id, we need to process per item.
		// When metadata is null, we can batch add in INCLUDE mode.
		if ($mode === SelectionMode::EXCLUDE) {
			throw new \LogicException('Cannot select multiple items in EXCLUDE mode.');
		}
		if ($metadata === null) {
			$ids = [];
			foreach ($items as $item) {
				$ids[] = is_scalar($item) ? $item : $this->normalizer->normalize($item, $this->identifierPath);
			}
			$this->storage->add($this->key, $ids, null);
			return $this;
		}
		// metadata provided: support map [id => array|object]
		// Additionally, support pattern: (per-id map) + (associative defaults), e.g. [1=>meta1, 2=>meta2] + ['x'=>0]
		foreach ($items as $item) {
			$id = is_scalar($item) ? $item : $this->normalizer->normalize($item, $this->identifierPath);

			$metaForId = null;
			if (array_key_exists($id, $metadata) || array_key_exists((string) $id, $metadata)) {
				$metaForId = $metadata[$id] ?? $metadata[(string) $id];
			} else {
				throw new \LogicException("No metadata found for id $id");
			}

			$this->storage->add($this->key, [$id], $metaForId);
		}
		return $this;
	}

	public function unselectMultiple(array $items): static {
		$ids = [];
		foreach ($items as $item) {
			$ids[] = is_scalar($item) ? $item : $this->normalizer->normalize($item, $this->identifierPath);
		}
		$this->storage->remove($this->key, $ids);
		return $this;
	}

	public function selectAll(): static {
		$this->storage->clear($this->key);
		$this->storage->setMode($this->key, SelectionMode::EXCLUDE);
		return $this;
	}

	public function unselectAll(): static {
		$this->storage->clear($this->key);
		$this->storage->setMode($this->key, SelectionMode::INCLUDE);
		return $this;
	}

	public function getSelectedIdentifiers(): array {
		if ($this->storage->getMode($this->key) === SelectionMode::INCLUDE) {
			return $this->storage->getStored($this->key);
		} else {
			$excluded = $this->storage->getStored($this->key);
			$all      = $this->storage->getStored($this->getAllContext());
			return array_diff($all, $excluded);
		}
	}

	public function update(mixed $item, object|array|null $metadata = null): static {
		$id = is_scalar($item) ? $item : $this->normalizer->normalize($item, $this->identifierPath);
		if ($metadata === null) {
			return $this; // nothing to update
		}
		$metaArray = is_object($metadata)
			? $this->metadataConverter->convertToStorable($metadata)
			: $metadata;

		$mode = $this->storage->getMode($this->key);
		if ($mode === SelectionMode::INCLUDE) {
			// Ensure metadata is persisted for this id (and id is included)
			$this->storage->add($this->key, [$id], $metaArray);
			return $this;
		}
		// In EXCLUDE mode, metadata can only be stored for explicitly excluded ids
		if ($this->storage->hasIdentifier($this->key, $id)) {
			$this->storage->add($this->key, [$id], $metaArray);
		}
		return $this;
	}

	public function getSelected(?string $metadataClass = null): array {
		$mode = $this->storage->getMode($this->key);
		if ($mode === SelectionMode::INCLUDE) {
			$map = $this->storage->getStoredWithMetadata($this->key);
			if ($metadataClass === null) {
				return $map;
			}
			$hydrated = [];
			foreach ($map as $id => $meta) {
				$hydrated[$id] = $this->metadataConverter->convertFromStorable($meta, $metadataClass);
			}
			return $hydrated;
		}
		// EXCLUDE mode
		$excluded = $this->storage->getStored($this->key);
		$all      = $this->storage->getStored($this->getAllContext());
		$selected = array_values(array_diff($all, $excluded));
		$result   = [];
		foreach ($selected as $id) {
			$meta = $this->storage->getMetadata($this->key, $id);
			if ($metadataClass !== null) {
				$result[$id] = $this->metadataConverter->convertFromStorable($meta, $metadataClass);
			} else {
				$result[$id] = $meta;
			}
		}
		return $result;
	}

	public function getMetadata(mixed $item, ?string $metadataClass = null): null|array|object {
		$id   = is_scalar($item) ? $item : $this->normalizer->normalize($item, $this->identifierPath);
		$meta = $this->storage->getMetadata($this->key, $id);
		if ($meta === [] || $meta === null) {
			return null;
		}
		if ($metadataClass !== null) {
			return $this->metadataConverter->convertFromStorable($meta, $metadataClass);
		}
		return $meta;
	}

	public function rememberAll(array $ids): static {
		$this->storage->add($this->getAllContext(), $ids, null);
		return $this;
	}

	public function setMode(SelectionMode $mode): void {
		$this->storage->setMode($this->key, $mode);
	}

	public function getMode(): SelectionMode {
		return $this->storage->getMode($this->key);
	}

	private function getAllContext(): string {
		return $this->key . '__ALL__';
	}

	public function destroy(): static {
		$this->storage->clear($this->key);
		$this->storage->clear($this->getAllContext());
		return $this;
	}

	public function isSelectedAll(): bool {
		return $this->getMode() == SelectionMode::EXCLUDE && count($this->getSelectedIdentifiers()) == 0;
	}

	public function getTotal(): int {
		return count($this->storage->getStored($this->getAllContext()));
	}

	public function normalize(mixed $item): int|string {
		return $this->normalizer->normalize($item, $this->identifierPath);
	}

}