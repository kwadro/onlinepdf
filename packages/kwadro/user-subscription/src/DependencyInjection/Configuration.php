<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\DependencyInjection;

use Kwadro\UserSubscription\Payment\LiqPayGateway;
use Kwadro\UserSubscription\Payment\MonobankClient;
use Kwadro\UserSubscription\Payment\MonobankGateway;
use Kwadro\UserSubscription\Payment\MonobankSignatureVerifier;
use Kwadro\UserSubscription\Payment\NullPaymentGateway;
use Kwadro\UserSubscription\Payment\PaymentGatewayRegistry;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('kwadro_user_subscription');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('user_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('default_plan_code')
                    ->defaultValue('free')
                ->end()
                ->booleanNode('auto_activate')
                    ->defaultFalse()
                ->end()
                ->scalarNode('payment_gateway')
                    ->defaultNull()
                    ->info('Deprecated. Use payment.providers instead.')
                ->end()
                ->arrayNode('payment')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_provider')
                            ->defaultValue('privat')
                        ->end()
                        ->booleanNode('verify_monobank_signature')
                            ->defaultTrue()
                        ->end()
                        ->arrayNode('providers')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('privat')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->booleanNode('enabled')->defaultFalse()->end()
                                        ->scalarNode('public_key')->defaultValue('')->end()
                                        ->scalarNode('private_key')->defaultValue('')->end()
                                        ->booleanNode('sandbox')->defaultFalse()->end()
                                        ->integerNode('api_version')->defaultValue(7)->end()
                                        ->scalarNode('result_url')->defaultValue('/subscription/payment/success')->end()
                                        ->scalarNode('server_url')->defaultValue('/webhook/payment/privat')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('monobank')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->booleanNode('enabled')->defaultFalse()->end()
                                        ->scalarNode('token')->defaultValue('')->end()
                                        ->scalarNode('redirect_url')->defaultValue('/subscription/payment/success')->end()
                                        ->scalarNode('webhook_url')->defaultValue('/webhook/payment/monobank')->end()
                                        ->integerNode('invoice_validity')->defaultValue(86400)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('default_plans')
                    ->useAttributeAsKey('code')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->integerNode('price')->defaultValue(0)->end()
                            ->scalarNode('currency')->defaultValue('UAH')->end()
                            ->scalarNode('interval')->defaultValue('monthly')->end()
                            ->booleanNode('active')->defaultTrue()->end()
                            ->arrayNode('features')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue([
                        'free' => [
                            'name' => 'Free',
                            'price' => 0,
                            'currency' => 'UAH',
                            'interval' => 'monthly',
                            'active' => true,
                            'features' => ['basic_access'],
                        ],
                        'pro' => [
                            'name' => 'Pro',
                            'price' => 9900,
                            'currency' => 'UAH',
                            'interval' => 'monthly',
                            'active' => true,
                            'features' => ['basic_access', 'premium_recipes', 'no_ads'],
                        ],
                    ])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
