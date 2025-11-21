<?php

namespace Tito10047\BatchSelectionBundle\Twig;

use Tito10047\BatchSelectionBundle\Service\SelectionInterface;
use Tito10047\BatchSelectionBundle\Service\SelectionManagerInterface;
use Twig\Extension\RuntimeExtensionInterface;

class SelectionRuntime implements RuntimeExtensionInterface {

	/**
	 * @param iterable<SelectionManagerInterface> $selectionManagers
	 */
	public function __construct(
		private readonly iterable $selectionManagers,
		private readonly UrlGeneratorInterface $router
	) { }

	public function isSelected(string $key, int|string $id, string $manager='default'):bool {
		$manager = $this->getRowsSelector($manager);
		$selection = $manager->getSelection($key);
		return $selection->isSelected($id);
	}

	public function rowSelector(string $key, int|string $id, string $manager='default'): string {
		$selected = "";
		if ($this->isSelected($key, $id, $manager)) {
			$selected = 'checked="checked" ';
		}
		$url = $this->rowsSelector->getUrlsSelect($key, $id, "__SELECTED__");

		return "<input type='checkbox' {$selected} name='row-selector[]' class='row-selector' data-url='{$url}' data-key='{$key}' value='{$id}'>";
	}

	public function rowSelectorAll(string $key, string $manager='default'): string {
		$selected = "";
		$all      = 0;
		if ($this->rowsSelector->isSelectedAll($key)) {
			$selected = 'checked="checked" ';
			$all      = 1;
		}
		$urlRange = $this->rowsSelector->getUrlsSelectRange($key, "__SELECTED__");
		$urlAll   = $this->rowsSelector->getUrlsSelectAll($key, "__SELECTED__");

		return "<input type='checkbox' {$selected} name='row-selector-all' class='row-selector' data-url-range='{$urlRange}' data-url-all='{$urlAll}' data-key='{$key}' data-all='{$all}' value='all'>";
	}

	public function rowSelectorItemsCount(string $key, string $manager='default'): int {
		return count($this->rowsSelector->getSelectedIds($key));
	}

	private function getRowsSelector(string $manager): SelectionManagerInterface {
		foreach ($this->selectionManagers as $id=>$selectionManager) {
			if ($id === $manager) {
				return $selectionManager;
			}
		}
		throw new \InvalidArgumentException(sprintf('No selection manager found for manager "%s".', $manager));
	}
}