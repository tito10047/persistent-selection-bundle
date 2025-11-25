<?php

namespace Tito10047\PersistentSelectionBundle\Tests\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tito10047\PersistentSelectionBundle\Enum\SelectionMode;
use Tito10047\PersistentSelectionBundle\Storage\SessionStorage;
use Tito10047\PersistentSelectionBundle\Tests\Trait\SessionInterfaceTrait;

class SessionStorageTest extends TestCase
{
	use SessionInterfaceTrait;

    private SessionStorage $storage;


    protected function setUp(): void
    {

        // Mock RequestStack to return our fake session
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($this->mockSessionInterface());

        $this->storage = new SessionStorage($requestStack);
    }

    public function testAddMergesAndDeduplicates(): void
    {
        $ctx = 'ctx_add';

        $this->storage->add($ctx, [1, 2, 3]);
        $this->storage->add($ctx, [2, 3, 4, '5']);

        $this->assertSame([1, 2, 3, 4, '5'], $this->storage->getStoredIdentifiers($ctx));
    }

    public function testRemoveRemovesAndReindexes(): void
    {
        $ctx = 'ctx_remove';

        $this->storage->add($ctx, [1, 2, 3, 4]);
        $this->storage->remove($ctx, [2, 4]);

        $this->assertSame([1, 3], $this->storage->getStoredIdentifiers($ctx));
    }

    public function testClearResetsContext(): void
    {
        $ctx = 'ctx_clear';

        $this->storage->add($ctx, [7]);
        $this->storage->setMode($ctx, SelectionMode::EXCLUDE);

        $this->storage->clear($ctx);

        $this->assertSame([], $this->storage->getStoredIdentifiers($ctx));
        $this->assertSame(SelectionMode::INCLUDE, $this->storage->getMode($ctx));
    }

    public function testGetStoredIdentifiersReturnsCurrentIds(): void
    {
        $ctx = 'ctx_ids';
        $this->storage->add($ctx, [9, 10]);

        $this->assertSame([9, 10], $this->storage->getStoredIdentifiers($ctx));
    }

    public function testHasIdentifierUsesLooseComparison(): void
    {
        $ctx = 'ctx_has';
        $this->storage->add($ctx, [5]);

        // uses in_array with loose comparison in the implementation
        $this->assertTrue($this->storage->hasIdentifier($ctx, '5'));
        $this->assertTrue($this->storage->hasIdentifier($ctx, 5));
        $this->assertFalse($this->storage->hasIdentifier($ctx, '6'));
    }

    public function testDefaultModeIsInclude(): void
    {
        $ctx = 'ctx_default_mode';
        $this->assertSame(SelectionMode::INCLUDE, $this->storage->getMode($ctx));
    }

    public function testSetAndGetModePersistsValue(): void
    {
        $ctx = 'ctx_mode';
        $this->storage->setMode($ctx, SelectionMode::EXCLUDE);
        $this->assertSame(SelectionMode::EXCLUDE, $this->storage->getMode($ctx));
    }
}
