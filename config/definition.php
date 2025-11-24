<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html#configuration
 */
return static function (DefinitionConfigurator $definition): void {
    // Konfigurácia bundle:
    // batch_selection:
    //     <selection_name>:
    //         normalizer: '@service_id' | typ položiek (napr. 'scalar'|'object')
    //         identifier_path: 'id' | 'user.id' | atď. (voliteľné)
    //         storage: '@service_id' (voliteľné)
    $definition
        ->rootNode()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    // ID normalizéra alebo typ, ktorý má podporiť registrovaný normalizér
                    ->scalarNode('normalizer')->isRequired()->cannotBeEmpty()->end()

                    // Cesta k identifikátoru (napr. "id" alebo "user.id").
                    // Ak nie je zadaná, môže ju definovať spotrebiteľ alebo použije defaulty loaderov
                    ->scalarNode('identifier_path')->defaultNull()->end()

                    // ID storage služby; ak nie je zadané, použije sa defaultná storage aliasovaná na StorageInterface
                    ->scalarNode('storage')->defaultNull()->end()

                    // TTL (v sekundách) pre cache ALL výberu. Ak je null, neexpiruje.
                    ->integerNode('ttl')->defaultNull()->min(0)->end()
                ->end()
            ->end()
        ->end()
    ;
};
