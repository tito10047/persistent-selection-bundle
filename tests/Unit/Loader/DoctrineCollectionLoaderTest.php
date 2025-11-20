<?php

namespace Tito10047\BatchSelectionBundle\Tests\Unit\Loader;

use Doctrine\Common\Collections\ArrayCollection;
use Tito10047\BatchSelectionBundle\Loader\DoctrineCollectionLoader;
use Tito10047\BatchSelectionBundle\Normalizer\ObjectNormalizer;
use Tito10047\BatchSelectionBundle\Normalizer\ScalarNormalizer;
use Tito10047\BatchSelectionBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class DoctrineCollectionLoaderTest extends AssetMapperKernelTestCase
{
    public function testBasic(): void
    {
		$records = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        // jednoduchý resolver iba pre potreby testu
        $resolver = new ScalarNormalizer();
        $collection = new ArrayCollection($records);
        $loader = new DoctrineCollectionLoader($resolver);

        $this->assertTrue($loader->supports($collection));
        $this->assertSame(10, $loader->getTotalCount($collection));

        $foundIds = $loader->loadAllIdentifiers($collection, null);

        $this->assertEquals($records, $foundIds);
    }
}
