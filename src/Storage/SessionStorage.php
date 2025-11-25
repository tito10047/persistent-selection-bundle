<?php

namespace Tito10047\PersistentSelectionBundle\Storage;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tito10047\PersistentSelectionBundle\Enum\SelectionMode;

/**
 * Default storage implementation using Symfony Session.
 *
 * Data structure in session:
 * [
 * 		'_persistent_selection_{context}' => [
 * 			'mode'=> 'include', // value of SelectionMode enum
 * 			'ids'=> [1, 2, 5]
 * 		]
 * ]
 */
final class SessionStorage implements StorageInterface
{
    private const SESSION_PREFIX = '_persistent_selection_';

    /**
     * Fallback session used when there is no active HTTP session available (e.g. CLI/tests).
     */
    private ?SessionInterface $fallbackSession = null;

	public function __construct(
		private readonly RequestStack $requestStack
	) {}

	public function add(string $context, array $ids): void
	{
		$data = $this->loadData($context);

		// Merge new IDs with existing ones
		$merged = array_merge($data['ids'], $ids);

		// Remove duplicates and re-index the array to ensure it's a list, not a map
		$data['ids'] = array_values(array_unique($merged));

		$this->saveData($context, $data);
	}

	public function remove(string $context, array $ids): void
	{
		$data = $this->loadData($context);

		// Remove specified IDs from the stored list
		$diff = array_diff($data['ids'], $ids);

		// Re-index array after removal
		$data['ids'] = array_values($diff);

		$this->saveData($context, $data);
	}

	public function clear(string $context): void
	{
		$this->getSession()->remove($this->getKey($context));
	}

	public function getStoredIdentifiers(string $context): array
	{
		return $this->loadData($context)['ids'];
	}

	public function hasIdentifier(string $context, int|string $id): bool
	{
		// Strict check might fail if mixing int/string types,
		// but usually, we rely on PHP's loose comparison for in_array here
		// or we should enforce type consistency in the add() method.
		return in_array($id, $this->loadData($context)['ids']);
	}

	public function setMode(string $context, SelectionMode $mode): void
	{
		$data = $this->loadData($context);
		$data['mode'] = $mode->value;

		$this->saveData($context, $data);
	}

	public function getMode(string $context): SelectionMode
	{
		$value = $this->loadData($context)['mode'];

		return SelectionMode::tryFrom($value) ?? SelectionMode::INCLUDE;
	}

 /**
  * Helper to retrieve the session service.
  * Using RequestStack allows usage in services where the session might not be started yet.
  */
 private function getSession(): SessionInterface
 {
     try {
         return $this->requestStack->getSession();
     } catch (SessionNotFoundException $e) {
         // No HTTP session available (likely CLI/tests). Use in-memory fallback session.
         if ($this->fallbackSession === null) {
             $this->fallbackSession = new Session(new MockArraySessionStorage());
         }

         return $this->fallbackSession;
     }
 }

	/**
	 * Generates a namespaced key for the session.
	 */
	private function getKey(string $context): string
	{
		return self::SESSION_PREFIX . $context;
	}

	/**
	 * Loads raw data from session or returns default structure.
	 *
	 * @return array{mode: string, ids: array<int|string>}
	 */
	private function loadData(string $context): array
	{
		return $this->getSession()->get($this->getKey($context), [
			'mode' => SelectionMode::INCLUDE->value,
			'ids' => []
		]);
	}

	/**
	 * Persists the data structure back to the session.
	 * * @param array{mode: string, ids: array<int|string>} $data
	 */
	private function saveData(string $context, array $data): void
	{
		$this->getSession()->set($this->getKey($context), $data);
	}
}