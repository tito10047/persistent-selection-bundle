<?php

namespace Tito10047\BatchSelectionBundle\Tests\Integration\Service;

use PHPUnit\Framework\Attributes\TestWith;
use stdClass;
use Tito10047\BatchSelectionBundle\Exception\NormalizationFailedException;
use Tito10047\BatchSelectionBundle\Service\SelectionInterface;
use Tito10047\BatchSelectionBundle\Service\SelectionManagerInterface;
use Tito10047\BatchSelectionBundle\Tests\App\AssetMapper\Src\ServiceHelper;
use Tito10047\BatchSelectionBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;
use Tito10047\BatchSelectionBundle\Tests\App\AssetMapper\Src\Support\TestList;
use Tito10047\BatchSelectionBundle\Enum\SelectionMode;
use function Zenstruck\Foundry\object;

class SelectionManagerTest extends AssetMapperKernelTestCase
{
    public function testGetSelectionAndSelectFlow(): void
    {
        $container = self::getContainer();

        /** @var SelectionManagerInterface $manager */
        $manager = $container->get('batch_selection.manager.scalar');
        $this->assertInstanceOf(SelectionManagerInterface::class, $manager);

        // Use the test normalizer that supports type "array" and requires identifierPath
        $selection = $manager->getSelection('test_key');
        $this->assertInstanceOf(SelectionInterface::class, $selection);

        // Initially nothing selected
        $this->assertFalse($selection->isSelected( 1));

        // Select single item and verify
        $selection->select( 1);
        $this->assertTrue($selection->isSelected(1));

        // Select multiple
        $selection->selectMultiple([
            2,
            3,
        ]);

        $this->assertTrue($selection->isSelected( 2));
        $this->assertTrue($selection->isSelected( 3));

        $ids = $selection->getSelectedIdentifiers();
        sort($ids);
        $this->assertSame([1, 2, 3], $ids);

        // Unselect one and verify
        $selection->unselect(2);
        $this->assertFalse($selection->isSelected( 2));

        $ids = $selection->getSelectedIdentifiers();
        sort($ids);
        $this->assertSame([1, 3], $ids);
    }

    public function testRegisterSourceThrowsWhenNoLoader(): void
    {
        $container = self::getContainer();

        /** @var SelectionManagerInterface $manager */
        $manager = $container->get('batch_selection.manager.default');
        $this->assertInstanceOf(SelectionManagerInterface::class, $manager);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No suitable loader found');

        // stdClass is not supported by any IdentityLoader in tests/app
        $manager->registerSource('no_loader_key', new \stdClass());
    }

	public function testAutoBindManager():void {

		$container = self::getContainer();

		/** @var ServiceHelper $helper */
		$helper = $container->get(ServiceHelper::class);
		$this->assertInstanceOf(ServiceHelper::class, $helper);

		$manager = $helper->arraySelectionManager;
		$this->assertInstanceOf(SelectionManagerInterface::class, $manager);


		$data = [
			['id' => 1, 'name' => 'A'],
			['id' => 2, 'name' => 'B'],
			['id' => 3, 'name' => 'C'],
		];

		$manager->registerSource("array_key",$data);
	}

	#[TestWith(['default',[['id' => 1, 'name' => 'A']]])]
	#[TestWith(['scalar',[['id' => 1, 'name' => 'A']]])]
	#[TestWith(['array',[new stdClass()]])]
	public function testThrowExceptionOnBadNormalizer($service,$data):void {

		$container = self::getContainer();

		/** @var SelectionManagerInterface $manager */
		$manager = $container->get('batch_selection.manager.'.$service);

		$this->expectException(NormalizationFailedException::class);
		$manager->registerSource("array_key_2",$data);
	}

    public function testRegisterSourceLoadsAllInExcludeMode(): void
    {
        $container = self::getContainer();

        /** @var SelectionManagerInterface $manager */
        $manager = $container->get('batch_selection.manager.array');
        $this->assertInstanceOf(SelectionManagerInterface::class, $manager);

        $data = [
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
            ['id' => 3, 'name' => 'C'],
        ];
        $list = new TestList($data);

        $selection = $manager->registerSource('reg_key', $list);
        $this->assertInstanceOf(SelectionInterface::class, $selection);

        // After registerSource -> rememberAll() should store all ids in ALL context.
        // Switching to EXCLUDE mode means: all are selected unless explicitly excluded.
        $selection->setMode(SelectionMode::EXCLUDE);
        $ids = $selection->getSelectedIdentifiers();
        sort($ids);
        $this->assertSame([1, 2, 3], $ids);
    }
}
