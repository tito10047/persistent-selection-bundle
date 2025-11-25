<?php

namespace Tito10047\PersistentSelectionBundle\Service;

use Tito10047\PersistentSelectionBundle\Enum\SelectionMode;

interface HasModeInterface {
	public function setMode(SelectionMode $mode): void;
	public function getMode(): SelectionMode;
}