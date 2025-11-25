<?php

namespace Tito10047\BatchSelectionBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Tito10047\BatchSelectionBundle\DependencyInjection\Compiler\AutoTagIdentifierNormalizersPass;
use Tito10047\BatchSelectionBundle\DependencyInjection\Compiler\AutoTagIdentityLoadersPass;
use Tito10047\BatchSelectionBundle\Service\SelectionManager;
use Tito10047\BatchSelectionBundle\Service\SelectionManagerInterface;
use Tito10047\BatchSelectionBundle\Storage\StorageInterface;
use Tito10047\BatchSelectionBundle\Converter\ObjectVarsConverter;
use Tito10047\BatchSelectionBundle\Converter\MetadataConverterInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\String\u;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html
 */
class BatchSelectionBundle extends AbstractBundle
{
	protected string $extensionAlias = 'batch_selection';
	public const STIMULUS_CONTROLLER='tito10047--batch-selection-bundle--batch-selection';
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }
    
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
      		$container->import('../config/services.php');
		$services = $container->services();
		// Default metadata converter service
		$services->set('batch_selection.converter.object_vars', ObjectVarsConverter::class)
			->alias(MetadataConverterInterface::class, 'batch_selection.converter.object_vars');
		foreach($config as $name=>$subConfig){
			$normalizer = service($subConfig['normalizer']??'batch_selection.identity_loader');
			$storage = service($subConfig['storage']??'batch_selection.storage.session');
			$identifierPath = $subConfig['identifier_path']??null;
			$services
				->set('batch_selection.manager.'.$name,SelectionManager::class)
				->public()
				->arg('$storage', $storage)
				->arg('$loaders', tagged_iterator('batch_selection.identity_loader'))
				->arg('$normalizer', $normalizer)
				->arg('$identifierPath', $identifierPath)
				->arg('$metadataConverter', service('batch_selection.converter.object_vars'))
				->tag('batch_selection.manager', ['name' => $name])
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