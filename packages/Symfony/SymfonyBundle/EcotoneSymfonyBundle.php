<?php

namespace Ecotone\SymfonyBundle;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\SymfonyBundle\DepedencyInjection\Compiler\ConfiguredMessagingSystemWrapper;
use Ecotone\SymfonyBundle\DepedencyInjection\Compiler\EcotoneCompilerPass;
use Ecotone\SymfonyBundle\DepedencyInjection\Compiler\SymfonyConfigurationVariableService;
use Ecotone\SymfonyBundle\DepedencyInjection\Compiler\SymfonyReferenceTypeResolver;
use Ecotone\SymfonyBundle\DepedencyInjection\EcotoneExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class IntegrationMessagingBundle
 * @package Ecotone\SymfonyBundle
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EcotoneSymfonyBundle extends Bundle
{
    public const CONFIGURED_MESSAGING_SYSTEM                 = 'configured_messaging_system';
    public const CONFIGURED_MESSAGING_SYSTEM_WRAPPER = ConfiguredMessagingSystem::class;
    public const APPLICATION_CONFIGURATION_CONTEXT   = 'messaging_system_application_context';

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EcotoneCompilerPass());

        $this->setUpExpressionLanguage($container);

        $definition = new Definition();
        $definition->setClass(ConfiguredMessagingSystemWrapper::class);
        $definition->addArgument(new Reference('service_container'));
        $container->setDefinition(self::CONFIGURED_MESSAGING_SYSTEM_WRAPPER, $definition);

        $definition = new Definition();
        $definition->setClass(ConfiguredMessagingSystem::class);
        $definition->setSynthetic(true);
        $definition->setPublic(true);
        $container->setDefinition(self::CONFIGURED_MESSAGING_SYSTEM, $definition);
    }

    /**
     * @param ContainerBuilder $container
     * @return void
     */
    private function setUpExpressionLanguage(ContainerBuilder $container): void
    {
        $expressionLanguageAdapter = ExpressionEvaluationService::REFERENCE . '_adapter';
        $definition = new Definition();
        $definition->setClass(ExpressionLanguage::class);

        $container->setDefinition($expressionLanguageAdapter, $definition);

        $definition = new Definition();
        $definition->setClass(SymfonyExpressionEvaluationAdapter::class);
        $definition->setFactory([SymfonyExpressionEvaluationAdapter::class, 'createWithExternalExpressionLanguage']);
        $definition->addArgument(new Reference($expressionLanguageAdapter));
        $definition->setPublic(true);
        $container->setDefinition(ExpressionEvaluationService::REFERENCE, $definition);
    }

    public function boot()
    {
        $configuration = MessagingSystemConfiguration::prepare(
            EcotoneCompilerPass::getRootProjectPath($this->container),
            new SymfonyReferenceTypeResolver($this->container),
            new SymfonyConfigurationVariableService($this->container),
            unserialize($this->container->getParameter(self::APPLICATION_CONFIGURATION_CONTEXT)),
            true
        );
        $messagingSystem = $configuration->buildMessagingSystemFromConfiguration($this->container->get('symfonyReferenceSearchService'));

        $this->container->set(self::CONFIGURED_MESSAGING_SYSTEM, $messagingSystem);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new EcotoneExtension();
    }
}
