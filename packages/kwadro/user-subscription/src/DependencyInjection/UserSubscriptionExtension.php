<?php

declare(strict_types=1);

namespace Kwadro\UserSubscription\DependencyInjection;

use Kwadro\UserSubscription\Controller\Api\PaymentWebhookController;
use Kwadro\UserSubscription\Payment\LiqPayGateway;
use Kwadro\UserSubscription\Payment\MonobankClient;
use Kwadro\UserSubscription\Payment\MonobankGateway;
use Kwadro\UserSubscription\Payment\MonobankSignatureVerifier;
use Kwadro\UserSubscription\Payment\NullPaymentGateway;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class UserSubscriptionExtension extends Extension
{
    public function getAlias(): string
    {
        return 'kwadro_user_subscription';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('kwadro_user_subscription.user_class', $config['user_class']);
        $container->setParameter('kwadro_user_subscription.default_plan_code', $config['default_plan_code']);
        $container->setParameter('kwadro_user_subscription.auto_activate', $config['auto_activate']);
        $container->setParameter('kwadro_user_subscription.default_plans', $config['default_plans']);
        $container->setParameter('kwadro_user_subscription.payment', $config['payment']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container->register(NullPaymentGateway::class)
            ->addTag('kwadro.payment_gateway');

        $privat = $config['payment']['providers']['privat'];
        if ($privat['enabled']) {
            $container->register(LiqPayGateway::class)
                ->setArguments([
                    $privat['public_key'],
                    $privat['private_key'],
                    $privat['result_url'],
                    $privat['server_url'],
                    $privat['sandbox'],
                    $privat['api_version'],
                ])
                ->addTag('kwadro.payment_gateway');
        }

        $monobank = $config['payment']['providers']['monobank'];
        if ($monobank['enabled']) {
            $container->register(MonobankClient::class)
                ->setArguments([
                    new Reference('http_client'),
                    $monobank['token'],
                ]);

            $container->register(MonobankGateway::class)
                ->setArguments([
                    new Reference(MonobankClient::class),
                    $monobank['redirect_url'],
                    $monobank['webhook_url'],
                    $monobank['invoice_validity'],
                ])
                ->addTag('kwadro.payment_gateway');

            $container->register(MonobankSignatureVerifier::class)
                ->setArguments([
                    new Reference(MonobankClient::class),
                    new Reference('cache.app'),
                ]);
        }

        $container->getDefinition(PaymentWebhookController::class)
            ->setArgument('$verifyMonobankSignature', $config['payment']['verify_monobank_signature']);

        $container->getDefinition(\Kwadro\UserSubscription\Service\SubscriptionPaymentHandler::class)
            ->setArgument('$liqPayPrivateKey', $privat['enabled'] ? $privat['private_key'] : null)
            ->setArgument(
                '$monobankSignatureVerifier',
                $monobank['enabled']
                    ? new Reference(MonobankSignatureVerifier::class)
                    : null,
            );
    }
}
