<?php

declare(strict_types=1);

namespace Nkamuo\AssetBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle configuration definition.
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nkamuo_asset');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('billing')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_currency')
                            ->defaultValue('USD')
                            ->info('Default currency for billing calculations')
                        ->end()
                        ->integerNode('calculation_precision')
                            ->defaultValue(4)
                            ->min(2)
                            ->max(8)
                            ->info('Decimal precision for billing calculations')
                        ->end()
                        ->booleanNode('auto_approve_billing')
                            ->defaultFalse()
                            ->info('Automatically approve calculated billing events')
                        ->end()
                        ->scalarNode('billing_frequency')
                            ->defaultValue('weekly')
                            ->validate()
                                ->ifNotInArray(['daily', 'weekly', 'monthly'])
                                ->thenInvalid('Invalid billing frequency %s')
                            ->end()
                            ->info('Default billing frequency')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('assets')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('auto_generate_asset_numbers')
                            ->defaultTrue()
                            ->info('Automatically generate asset numbers if not provided')
                        ->end()
                        ->scalarNode('asset_number_format')
                            ->defaultValue('AST-{type}-{sequence}')
                            ->info('Format for auto-generated asset numbers')
                        ->end()
                        ->booleanNode('require_acquisition_cost')
                            ->defaultTrue()
                            ->info('Require acquisition cost for all assets')
                        ->end()
                        ->arrayNode('default_statuses')
                            ->useAttributeAsKey('type')
                            ->scalarPrototype()->end()
                            ->defaultValue([
                                'vehicle' => 'available',
                                'driver' => 'available',
                                'equipment' => 'available',
                                'infrastructure' => 'available',
                                'technology' => 'available',
                                'container' => 'available',
                                'trailer' => 'available',
                            ])
                            ->info('Default status for new assets by type')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('provisions')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('require_end_date')
                            ->defaultFalse()
                            ->info('Require end date for all provisions')
                        ->end()
                        ->integerNode('default_lease_duration_months')
                            ->defaultValue(12)
                            ->min(1)
                            ->max(120)
                            ->info('Default lease duration in months')
                        ->end()
                        ->booleanNode('auto_activate_provisions')
                            ->defaultFalse()
                            ->info('Automatically activate provisions on creation')
                        ->end()
                        ->arrayNode('allowed_provision_types')
                            ->scalarPrototype()->end()
                            ->defaultValue(['owned', 'leased', 'rented', 'subcontracted', 'shared'])
                            ->info('Allowed provision types for the organization')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('usage_tracking')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enable_location_tracking')
                            ->defaultTrue()
                            ->info('Enable location tracking for usage events')
                        ->end()
                        ->booleanNode('require_usage_completion')
                            ->defaultTrue()
                            ->info('Require usage events to be marked as completed')
                        ->end()
                        ->integerNode('max_ongoing_usage_hours')
                            ->defaultValue(24)
                            ->min(1)
                            ->max(168)
                            ->info('Maximum hours for ongoing usage before auto-completion')
                        ->end()
                        ->arrayNode('billable_usage_types')
                            ->scalarPrototype()->end()
                            ->defaultValue(['driving', 'loading', 'operating', 'setup'])
                            ->info('Usage types that are billable by default')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('integrations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('ecotone')
                            ->canBeEnabled()
                            ->children()
                                ->booleanNode('enable_cqrs')
                                    ->defaultTrue()
                                    ->info('Enable CQRS pattern with Ecotone')
                                ->end()
                                ->booleanNode('enable_event_sourcing')
                                    ->defaultFalse()
                                    ->info('Enable event sourcing for asset entities')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('messenger')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('command_bus')
                                    ->defaultValue('messenger.bus.commands')
                                    ->info('Service ID for command bus')
                                ->end()
                                ->scalarNode('query_bus')
                                    ->defaultValue('messenger.bus.queries')
                                    ->info('Service ID for query bus')
                                ->end()
                                ->scalarNode('event_bus')
                                    ->defaultValue('messenger.bus.events')
                                    ->info('Service ID for event bus')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
