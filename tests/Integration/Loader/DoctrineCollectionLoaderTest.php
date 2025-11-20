<?php

namespace Tito10047\BatchSelectionBundle\Tests\Integration\Loader;

use Doctrine\Common\Collections\ArrayCollection;
use Tito10047\BatchSelectionBundle\Loader\DoctrineCollectionLoader;
use Tito10047\BatchSelectionBundle\Service\IdentityResolverInterface;
use Tito10047\BatchSelectionBundle\Tests\App\AssetMapper\Src\Entity\RecordInteger;
use Tito10047\BatchSelectionBundle\Tests\App\AssetMapper\Src\Factory\RecordIntegerFactory;
use Tito10047\BatchSelectionBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class DoctrineCollectionLoaderTest extends AssetMapperKernelTestCase
{
    public function testBasic(): void
    {
        $records = RecordIntegerFactory::createMany(10);

        // jednoduchý resolver iba pre potreby testu
        $resolver = new class implements IdentityResolverInterface {
            public function normalize(mixed $item, string $identifierPath): string|int
            {
                if (is_object($item)) {
                    $method = 'get' . ucfirst($identifierPath);
                    if (method_exists($item, $method)) {
                        return $item->$method();
                    }

                    if (property_exists($item, $identifierPath)) {
                        return $item->$identifierPath;
                    }
                }

                if (is_array($item) && array_key_exists($identifierPath, $item)) {
                    return $item[$identifierPath];
                }

                throw new \RuntimeException('Unable to normalize identifier.');
            }
        };

        $collection = new ArrayCollection($records);
        $loader = new DoctrineCollectionLoader($resolver);

        $this->assertTrue($loader->supports($collection));
        $this->assertSame(10, $loader->getTotalCount($collection));

        $ids = array_map(fn(RecordInteger $record) => $record->getId(), $records);
        $foundIds = $loader->loadAllIdentifiers($collection);

        $this->assertEquals($ids, $foundIds);
    }
}
