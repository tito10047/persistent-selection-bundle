<?php

namespace Tito10047\BatchSelectionBundle\Tests\Integration\Loader;

use Doctrine\ORM\EntityManagerInterface;
use Tito10047\BatchSelectionBundle\Loader\DoctrineQueryLoader;
use Tito10047\BatchSelectionBundle\Tests\App\AssetMapper\Src\Entity\RecordInteger;
use Tito10047\BatchSelectionBundle\Tests\App\AssetMapper\Src\Factory\RecordIntegerFactory;
use Tito10047\BatchSelectionBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class DoctrineQueryLoaderTest extends AssetMapperKernelTestCase {

	public function testBasic() {
		$records = RecordIntegerFactory::createMany(10);

		$queryLoader = new DoctrineQueryLoader();

		/** @var EntityManagerInterface $em */
		$em = self::getContainer()->get('doctrine')->getManager();
		$query = $em->createQueryBuilder()->select('i')->from(RecordInteger::class, 'i')->setMaxResults(5)->getQuery();


		$this->assertTrue($queryLoader->supports($query));
		$this->assertEquals(10, $queryLoader->getTotalCount($query));

		$ids = array_map(fn(RecordInteger $record) => $record->getId(), $records);
		$foundIds = $queryLoader->loadAllIdentifiers($query);

		$this->assertEquals($ids, $foundIds);


	}

}