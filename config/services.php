<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tito10047\BatchSelectionBundle\DependencyInjection\Compiler\AutoTagIdentifierNormalizersPass;
use Tito10047\BatchSelectionBundle\DependencyInjection\Compiler\AutoTagIdentityLoadersPass;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use Tito10047\BatchSelectionBundle\Support\TaggedServiceCollection;

use Tito10047\BatchSelectionBundle\Loader\IdentityLoaderInterface;
use Tito10047\BatchSelectionBundle\Normalizer\IdentifierNormalizerInterface;
use Tito10047\BatchSelectionBundle\Normalizer\ObjectNormalizer;
use Tito10047\BatchSelectionBundle\Normalizer\ScalarNormalizer;
use Tito10047\BatchSelectionBundle\Loader\ArrayLoader;
use Tito10047\BatchSelectionBundle\Loader\DoctrineCollectionLoader;
use Tito10047\BatchSelectionBundle\Loader\DoctrineQueryLoader;
use Tito10047\BatchSelectionBundle\Service\SelectionManager;
use Tito10047\BatchSelectionBundle\Service\SelectionManagerInterface;
use Tito10047\BatchSelectionBundle\Storage\SessionStorage;
use Tito10047\BatchSelectionBundle\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Konfigurácia služieb pre BatchSelectionBundle – bez autowire/autoconfigure.
 * Všetko je definované manuálne.
 */
return static function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();

    $services = $container->services();

    // --- Normalizéry ---
    $services
        ->set(ScalarNormalizer::class)
            ->tag(AutoTagIdentifierNormalizersPass::TAG)
    ;

    $services
        ->set(ObjectNormalizer::class)
            ->tag(AutoTagIdentifierNormalizersPass::TAG)
    ;

    // --- Loadery ---
    $services
        ->set(ArrayLoader::class)
            ->tag(AutoTagIdentityLoadersPass::TAG)
    ;

    $services
        ->set(DoctrineCollectionLoader::class)
		->tag(AutoTagIdentityLoadersPass::TAG)
    ;

    $services
        ->set(DoctrineQueryLoader::class)
		->tag(AutoTagIdentityLoadersPass::TAG)
    ;

    // --- Storage ---
    $services
        ->set(SessionStorage::class)
            ->arg('$requestStack', service(RequestStack::class))
    ;
    $services->alias(StorageInterface::class, SessionStorage::class);

    // --- SelectionManager ---
    $services
        ->set(SelectionManager::class)
		->public()
            ->arg('$storage', service(StorageInterface::class))
            ->arg('$loaders', tagged_iterator('batch_selection.identity_loader'))
            ->arg('$normalizers', tagged_iterator('batch_selection.identifier_normalizer'))
    ;
    $services->alias(SelectionManagerInterface::class, SelectionManager::class);

};
