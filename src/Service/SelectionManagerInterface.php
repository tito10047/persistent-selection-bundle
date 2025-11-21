<?php

namespace Tito10047\BatchSelectionBundle\Service;

interface SelectionManagerInterface {

	public function registerSource(string $key, mixed $source): SelectionInterface;

	public function getSelection(string $key): SelectionInterface;

}