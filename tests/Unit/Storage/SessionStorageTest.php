<?php

namespace Tito10047\PersistentSelectionBundle\Tests\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tito10047\PersistentSelectionBundle\Enum\SelectionMode;
use Tito10047\PersistentSelectionBundle\Storage\SessionStorage;
use Tito10047\PersistentSelectionBundle\Tests\Trait\SessionInterfaceTrait;

class SessionStorageTest extends TestCase {

	use SessionInterfaceTrait;

	private SessionStorage $storage;


	protected function setUp(): void {

		// Mock RequestStack to return our fake session
		$requestStack = $this->createMock(RequestStack::class);
		$requestStack->method('getSession')->willReturn($this->mockSessionInterface());

		$this->storage = new SessionStorage($requestStack);
	}

	public function testAddMergesAndDeduplicates(): void {
		$ctx = 'ctx_add';

		$this->storage->add($ctx, [1, 2, 3], null);
		$this->storage->add($ctx, [2, 3, 4, '5'], null);

		$this->assertSame([1, 2, 3, 4, '5'], $this->storage->getStored($ctx));
	}

	public function testRemoveRemovesAndReindexes(): void {
		$ctx = 'ctx_remove';

		$this->storage->add($ctx, [1, 2, 3, 4], null);
		$this->storage->remove($ctx, [2, 4]);

		$this->assertSame([1, 3], $this->storage->getStored($ctx));
	}

	public function testClearResetsContext(): void {
		$ctx = 'ctx_clear';

		$this->storage->add($ctx, [7], null);
		$this->storage->setMode($ctx, SelectionMode::EXCLUDE);

		$this->storage->clear($ctx);

		$this->assertSame([], $this->storage->getStored($ctx));
		$this->assertSame(SelectionMode::INCLUDE, $this->storage->getMode($ctx));
	}

	public function testGetStoredIdentifiersReturnsCurrentIds(): void {
		$ctx = 'ctx_ids';
		$this->storage->add($ctx, [9, 10], null);

		$this->assertSame([9, 10], $this->storage->getStored($ctx));
	}

	public function testHasIdentifierIsStrictComparison(): void {
		$ctx = 'ctx_has';
		$this->storage->add($ctx, [5], null);

		// striktne typové porovnanie: '5' !== 5
		$this->assertFalse($this->storage->hasIdentifier($ctx, '5'));
		$this->assertTrue($this->storage->hasIdentifier($ctx, 5));
		$this->assertFalse($this->storage->hasIdentifier($ctx, '6'));

		// metadata not set returns empty array
		$this->assertSame([], $this->storage->getMetadata($ctx, 5));
	}

	public function testTypeInequalityNotSameAndHasIdentifierStrict(): void {
		$ctx = 'ctx_not_same_types';

		$this->storage->add($ctx, [1], null);

		// '1' != 1 striktne
		$this->assertFalse($this->storage->hasIdentifier($ctx, '1'));
		$this->assertTrue($this->storage->hasIdentifier($ctx, 1));

		// pole identifikátorov sa typovo nerovná stringovej variante
		$this->assertNotSame(['1'], $this->storage->getStored($ctx));
	}

	public function testStrictRemoveAndMetadataAreTypeAware(): void {
		$ctx     = 'ctx_strict_remove_meta';
		$metaInt = ['t' => 'int'];
		$metaStr = ['t' => 'str'];

		// pridaj 5 (int) aj '5' (string) s rôznymi metadátami
		// Pozn.: v PHP by pole [5 => ..., '5' => ...] zhluklo kľúče, preto pridávame v dvoch krokoch
		$this->storage->add($ctx, [5], [5 => $metaInt]);
		$this->storage->add($ctx, ['5'], ['5' => $metaStr]);

		// oba existujú, ale s typovým rozlíšením
		$this->assertTrue($this->storage->hasIdentifier($ctx, 5));
		$this->assertTrue($this->storage->hasIdentifier($ctx, '5'));

		// over typovo oddelené metadáta
		$this->assertSame($metaInt, $this->storage->getMetadata($ctx, 5));
		$this->assertSame($metaStr, $this->storage->getMetadata($ctx, '5'));

		// odstráň iba int 5
		$this->storage->remove($ctx, [5]);

		// mal by zostať iba '5' so svojimi metadátami
		$this->assertFalse($this->storage->hasIdentifier($ctx, 5));
		$this->assertTrue($this->storage->hasIdentifier($ctx, '5'));
		$this->assertSame([], $this->storage->getMetadata($ctx, 5));
		$this->assertSame(['5' => $metaStr], $this->storage->getStoredWithMetadata($ctx));
		$this->assertSame(['5'], $this->storage->getStored($ctx));
	}

	public function testDefaultModeIsInclude(): void {
		$ctx = 'ctx_default_mode';
		$this->assertSame(SelectionMode::INCLUDE, $this->storage->getMode($ctx));
	}

	public function testSetAndGetModePersistsValue(): void {
		$ctx = 'ctx_mode';
		$this->storage->setMode($ctx, SelectionMode::EXCLUDE);
		$this->assertSame(SelectionMode::EXCLUDE, $this->storage->getMode($ctx));
	}

	public function testAddWithMetadataAndGetStored(): void {
		$ctx = 'ctx_meta';

		$meta = ['foo' => 'bar', 'n' => 1];
		// New API: third parameter is an associative map id => metadata
		$this->storage->add($ctx, [1, 2], [
			1 => $meta,
			2 => $meta,
		]);

		// Non-overwritten metadata persists per id
		$this->assertSame($meta, $this->storage->getMetadata($ctx, 1));
		$this->assertSame($meta, $this->storage->getMetadata($ctx, 2));

		// getStored returns id=>metadata map for stored ids
		$this->assertSame([
			1 => $meta,
			2 => $meta,
		], $this->storage->getStoredWithMetadata($ctx));

		// Add another id without metadata, should not override others
		$this->storage->add($ctx, [3], null);
		$this->assertSame([], $this->storage->getMetadata($ctx, 3));

		$this->assertSame([
			1 => $meta,
			2 => $meta,
			3 => [],
		], $this->storage->getStoredWithMetadata($ctx));
	}

	public function testRemoveAlsoRemovesMetadata(): void {
		$ctx  = 'ctx_remove_meta';
		$meta = ['x' => 10];
		// New API: provide map for both ids
		$this->storage->add($ctx, [10, 11], [
			10 => $meta,
			11 => $meta,
		]);
		$this->storage->remove($ctx, [10]);

		$this->assertSame([11], $this->storage->getStored($ctx));
		$this->assertSame([], $this->storage->getMetadata($ctx, 10));
		$this->assertSame([11 => $meta], $this->storage->getStoredWithMetadata($ctx));
	}
}
