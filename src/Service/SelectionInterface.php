<?php

namespace Tito10047\BatchSelectionBundle\Service;

interface SelectionInterface {

	public function clearSelected(): static;

	public function destroy(): static;

	public function isSelected(mixed $item): bool;

	public function select(mixed $item): static;

	public function unselect(mixed $item): static;
	public function selectMultiple(array $items):static;
	public function unselectMultiple(array $items):static;

	public function selectAll():static;

	public function unselectAll():static;

	public function getSelectedIdentifiers(): array;
}