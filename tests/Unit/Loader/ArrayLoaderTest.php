<?php

namespace Tito10047\BatchSelectionBundle\Tests\Unit\Loader;

use PHPUnit\Framework\TestCase;
use Tito10047\BatchSelectionBundle\Loader\ArrayLoader;
use Tito10047\BatchSelectionBundle\Normalizer\ScalarNormalizer;
use Tito10047\BatchSelectionBundle\Tests\App\AssetMapper\Src\Entity\RecordInteger;

class ArrayLoaderTest extends TestCase
{
    public function testBasic(): void
    {
        $records = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $resolver = new ScalarNormalizer();

        $loader = new ArrayLoader($resolver);

        $this->assertTrue($loader->supports($records));
        $this->assertSame(10, $loader->getTotalCount($records));

        $foundIds = $loader->loadAllIdentifiers($records, "id");

        $this->assertEquals($records, $foundIds);
    }
}
