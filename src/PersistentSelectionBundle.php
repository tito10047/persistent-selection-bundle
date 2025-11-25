<?php

namespace Tito10047\PersistentSelectionBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Tito10047\PersistentSelectionBundle\Converter\MetadataConverterInterface;
use Tito10047\PersistentSelectionBundle\Converter\ObjectVarsConverter;
use Tito10047\PersistentSelectionBundle\DependencyInjection\Compiler\AutoTagIdentifierNormalizersPass;
use Tito10047\PersistentSelectionBundle\DependencyInjection\Compiler\AutoTagIdentityLoadersPass;
use Tito10047\PersistentSelectionBundle\Service\SelectionManager;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\String\u;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html
 */
class PersistentSelectionBundle extends AbstractBundle
{
	protected string $extensionAlias = 'persistent_selection';
	public const STIMULUS_CONTROLLER='tito10047--persistent-selection-bundle--persistent-selection';
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }
    
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
      		$container->import('../config/services.php');
		$services = $container->services();
		// Default metadata converter service
		$services->set('persistent_selection.converter.object_vars', ObjectVarsConverter::class)
			->alias(MetadataConverterInterface::class, 'persistent_selection.converter.object_vars');
		foreach($config as $name=>$subConfig){
			$normalizer = service($subConfig['normalizer']??'persistent_selection.identity_loader');
			$storage = service($subConfig['storage']??'persistent_selection.storage.session');
			$identifierPath = $subConfig['identifier_path']??null;
			$ttl = $subConfig['ttl'] ?? null;
			$services
				->set('persistent_selection.manager.'.$name,SelectionManager::class)
				->public()
				->arg('$storage', $storage)
				->arg('$loaders', tagged_iterator('persistent_selection.identity_loader'))
				->arg('$normalizer', $normalizer)
				->arg('$identifierPath', $identifierPath)
				->arg('$ttl', $ttl)
				->arg('$metadataConverter', service('persistent_selection.converter.object_vars'))
				->tag('persistent_selection.manager', ['name' => $name])
				;
		}
	}

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AutoTagIdentifierNormalizersPass());
        $container->addCompilerPass(new AutoTagIdentityLoadersPass());
    }
}