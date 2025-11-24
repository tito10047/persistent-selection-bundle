<?php

namespace Tito10047\BatchSelectionBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tito10047\BatchSelectionBundle\Enum\SelectionMode;
use Tito10047\BatchSelectionBundle\Normalizer\IdentifierNormalizerInterface;
use Tito10047\BatchSelectionBundle\Normalizer\ScalarNormalizer;
use Tito10047\BatchSelectionBundle\Service\Selection;
use Tito10047\BatchSelectionBundle\Service\SelectionInterface;
use Tito10047\BatchSelectionBundle\Storage\SessionStorage;
use Tito10047\BatchSelectionBundle\Tests\Trait\SessionInterfaceTrait;

class SelectionInterfaceTest  extends TestCase{

	use SessionInterfaceTrait;
	private IdentifierNormalizerInterface $normalizer;
	private SessionStorage $storage;

	protected function setUp(): void
	{
		$requestStack = $this->createMock(RequestStack::class);
		$requestStack->method('getSession')->willReturn($this->mockSessionInterface());

		$this->storage = new SessionStorage($requestStack);

		$this->normalizer = new ScalarNormalizer();
	}

	public function testGetSelectedIdentifiersWithExcludeModeRemembersAll():void {
  $selection = new Selection('test', null, $this->storage, $this->normalizer);
  $selection->setSelection('ck_test_123', [1, 2, 3]);
		$selection->setMode(SelectionMode::EXCLUDE);

		/** @var SelectionInterface $selection */
		$ids = $selection->getSelectedIdentifiers();

		$this->assertSame([1, 2, 3], $ids);
	}

	public function testSelectAndIsSelected(): void
	{
		$selection = new Selection('ctx_select', null, $this->storage, $this->normalizer);

		// selection methods should be fluent
		$chain = $selection->select(5)->select(6);
		$this->assertSame($selection, $chain);

		$this->assertTrue($selection->isSelected(5));
		$this->assertTrue($selection->isSelected(6));
		$this->assertFalse($selection->isSelected(7));
	}

	public function testUnselect(): void
	{
		$selection = new Selection('ctx_unselect', null, $this->storage, $this->normalizer);
		$selection->select(10)->select(11);

		$this->assertTrue($selection->isSelected(10));
		$this->assertTrue($selection->isSelected(11));

		$chain = $selection->unselect(10);
		$this->assertSame($selection, $chain);

		$this->assertFalse($selection->isSelected(10));
		$this->assertTrue($selection->isSelected(11));
	}

	public function testSelectMultiple(): void
	{
		$selection = new Selection('ctx_multi', null, $this->storage, $this->normalizer);
		// intentionally pass mixed types, storage supports loose comparisons
		$selection->selectMultiple([1, '2', 3]);

		/** @var SelectionInterface $selection */
		$this->assertSame([1, '2', 3], $selection->getSelectedIdentifiers());
	}

	public function testClearSelected(): void
	{
		$selection = new Selection('ctx_clear', null, $this->storage, $this->normalizer);
		$selection->select(1)->select(2);
		$this->assertSame([1, 2], $selection->getSelectedIdentifiers());

		$chain = $selection->clearSelected();
		$this->assertSame($selection, $chain);
		$this->assertSame([], $selection->getSelectedIdentifiers());
		$this->assertFalse($selection->isSelected(1));
	}

	public function testDestroyClearsAllContexts(): void
	{
  $selection = new Selection('ctx_destroy', null, $this->storage, $this->normalizer);
  // Setup some state in both primary and __ALL__ contexts (using helper methods only for setup)
  $selection->setSelection('ck_destroy', [100, 200, 300]);
		$selection->select(200)->select(400);

		// Sanity before destroy
		$this->assertNotSame([], $selection->getSelectedIdentifiers());

		$chain = $selection->destroy();
		$this->assertSame($selection, $chain);

		// After destroy, include-mode default with no identifiers
		$this->assertSame([], $selection->getSelectedIdentifiers());
		$this->assertFalse($selection->isSelected(200));
	}

    public function testGetSelectedIdentifiersInIncludeMode(): void
    {
        $selection = new Selection('ctx_ids', null, $this->storage, $this->normalizer);
        $selection->select(1)->select(2)->select(2); // duplicate should be deduped by storage

        /** @var SelectionInterface $selection */
        $this->assertSame([1, 2], $selection->getSelectedIdentifiers());
    }

    public function testHasSelectionWithCacheKeyAndTtl(): void
    {
        $selection = new Selection('ctx_meta', null, $this->storage, $this->normalizer);

        // Initially no selection cached
        $this->assertFalse($selection->hasSelection('abc'));

        // Set with cache key and ttl 1 second
        $selection->setSelection('abc', [10, 20], 1);
        $this->assertTrue($selection->hasSelection('abc'));
        $this->assertFalse($selection->hasSelection('other'));

        // Simulate expiration by setting ttl=0 immediate expire
        $selection->setSelection('exp', [1], 0);
        $this->assertFalse($selection->hasSelection('exp'));
    }
}