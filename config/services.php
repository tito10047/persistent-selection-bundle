<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tito10047\PersistentSelectionBundle\DependencyInjection\Compiler\AutoTagIdentifierNormalizersPass;
use Tito10047\PersistentSelectionBundle\DependencyInjection\Compiler\AutoTagIdentityLoadersPass;
use Tito10047\PersistentSelectionBundle\Normalizer\ArrayNormalizer;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use Tito10047\PersistentSelectionBundle\Support\TaggedServiceCollection;

use Tito10047\PersistentSelectionBundle\Loader\IdentityLoaderInterface;
use Tito10047\PersistentSelectionBundle\Normalizer\IdentifierNormalizerInterface;
use Tito10047\PersistentSelectionBundle\Normalizer\ObjectNormalizer;
use Tito10047\PersistentSelectionBundle\Normalizer\ScalarNormalizer;
use Tito10047\PersistentSelectionBundle\Loader\ArrayLoader;
use Tito10047\PersistentSelectionBundle\Loader\DoctrineCollectionLoader;
use Tito10047\PersistentSelectionBundle\Loader\DoctrineQueryLoader;
use Tito10047\PersistentSelectionBundle\Loader\DoctrineQueryBuilderLoader;
use Tito10047\PersistentSelectionBundle\Service\SelectionManager;
use Tito10047\PersistentSelectionBundle\Service\SelectionManagerInterface;
use Tito10047\PersistentSelectionBundle\Storage\SessionStorage;
use Tito10047\PersistentSelectionBundle\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Tito10047\PersistentSelectionBundle\Twig\SelectionExtension;
use Tito10047\PersistentSelectionBundle\Twig\SelectionRuntime;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tito10047\PersistentSelectionBundle\Controller\SelectController;

/**
 * Konfigurácia služieb pre PersistentSelectionBundle – bez autowire/autoconfigure.
 * Všetko je definované manuálne.
 */
return static function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();

    $services = $container->services();

    // --- Normalizéry ---
    $services
        ->set('persistent_selection.normalizer.scalar',ScalarNormalizer::class)
			->public()
            ->tag(AutoTagIdentifierNormalizersPass::TAG)
    ;

    $services
        ->set('persistent_selection.normalizer.object',ObjectNormalizer::class)
			->public()
            ->tag(AutoTagIdentifierNormalizersPass::TAG)
    ;

    $services
        ->set('persistent_selection.normalizer.array', ArrayNormalizer::class)
			->public()
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

    $services
        ->set(DoctrineQueryBuilderLoader::class)
			->arg('$arrayNormalizer', service("persistent_selection.normalizer.array"))
		->tag(AutoTagIdentityLoadersPass::TAG)
    ;

    // --- Storage ---
    $services
        ->set('persistent_selection.storage.session',SessionStorage::class)
            ->arg('$requestStack', service(RequestStack::class))
    ;
    $services->alias(StorageInterface::class, SessionStorage::class);

    // --- SelectionManager ---
    $services
        ->set('persistent_selection.manager.default',SelectionManager::class)
		->public()
            ->arg('$storage', service('persistent_selection.storage.session'))
            ->arg('$loaders', tagged_iterator('persistent_selection.identity_loader'))
            ->arg('$normalizer', service('persistent_selection.normalizer.object'))
            ->arg('$identifierPath', 'id')
            ->arg('$ttl', null)
            ->tag('persistent_selection.manager', ['name' => 'default'])
		->alias(SelectionManagerInterface::class, 'persistent_selection.manager.default')
    ;

    // --- Twig integration ---
    $services
        ->set(SelectionExtension::class)
            ->tag('twig.extension')
    ;

    $services
        ->set(SelectionRuntime::class)
            ->arg('$selectionManagers', tagged_iterator('persistent_selection.manager', 'name'))
            ->arg('$router', service(UrlGeneratorInterface::class))
            ->tag('twig.runtime')
    ;

    // --- Controllers ---
    $services
        ->set(SelectController::class)
            ->public()
            ->arg('$selectionManagers', tagged_iterator('persistent_selection.manager', 'name'))
    ;

};
