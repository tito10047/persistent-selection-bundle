<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Tito10047\PersistentSelectionBundle\Controller\SelectController;

/**
 * Definícia rout pre PersistentSelectionBundle.
 *
 * @link https://symfony.com/doc/current/bundles/best_practices.html#routing
 */
return static function (RoutingConfigurator $routes): void {
    // Toggle jedného riadku (prepnutie select/unselect)
    $routes
        ->add('persistent_selection_toggle', '/_persistent-selection/toggle')
            ->controller([SelectController::class, 'rowSelectorToggle'])
            ->methods(['GET'])
    ;

    // Označiť/odznačiť všetky riadky podľa kľúča
    $routes
        ->add('persistent_selection_select_all', '/_persistent-selection/select-all')
            ->controller([SelectController::class, 'rowSelectorSelectAll'])
            ->methods(['GET'])
    ;

    // Označiť/odznačiť viac ID naraz (POST body: id[]=1&id[]=2...)
    $routes
        ->add('persistent_selection_select_range', '/_persistent-selection/select-range')
            ->controller([SelectController::class, 'rowSelectorSelectRange'])
            ->methods(['POST'])
    ;
};
