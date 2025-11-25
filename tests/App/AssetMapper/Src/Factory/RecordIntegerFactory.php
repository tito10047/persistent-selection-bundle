<?php

namespace Tito10047\PersistentSelectionBundle\Tests\App\AssetMapper\Src\Factory;

use Doctrine\ORM\EntityRepository;
use Tito10047\PersistentSelectionBundle\Tests\App\AssetMapper\Src\Entity\RecordInteger;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<RecordInteger>
 */
final class RecordIntegerFactory extends PersistentProxyObjectFactory{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return RecordInteger::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
			"name"=>self::faker()->boolean(50) ? 'keep' : 'drop',
			"category"=>TestCategoryFactory::randomOrCreate([
				"name"=>self::faker()->words(1,true)
			])
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(RecordInteger $recordInteger): void {})
        ;
    }
}
