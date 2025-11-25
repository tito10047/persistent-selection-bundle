<?php

namespace Tito10047\PersistentSelectionBundle\Tests\Integration\Loader;

use Tito10047\PersistentSelectionBundle\IdentityResilver\IdentityResolverInterface;
use Tito10047\PersistentSelectionBundle\Loader\ArrayLoader;
use Tito10047\PersistentSelectionBundle\Normalizer\ObjectNormalizer;
use Tito10047\PersistentSelectionBundle\Tests\App\AssetMapper\Src\Entity\RecordInteger;
use Tito10047\PersistentSelectionBundle\Tests\App\AssetMapper\Src\Factory\RecordIntegerFactory;
use Tito10047\PersistentSelectionBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class ArrayLoaderTest extends AssetMapperKernelTestCase
{
    public function testBasic(): void
    {
        $records = RecordIntegerFactory::createMany(10);

        $resolver = new ObjectNormalizer();

        $loader = new ArrayLoader($resolver);

        $this->assertTrue($loader->supports($records));
        $this->assertSame(10, $loader->getTotalCount($records));

        $ids = array_map(fn(RecordInteger $record) => $record->getId(), $records);
        $foundIds = $loader->loadAllIdentifiers($resolver, $records, "id");

        $this->assertEquals($ids, $foundIds);
    }
}
