<?php

namespace Ecotone\SymfonyBundle\DepedencyInjection;

use Ecotone\SymfonyBundle\DepedencyInjection\Compiler\EcotoneCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class EcotoneExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(EcotoneCompilerPass::WORKING_NAMESPACES_CONFIG, $config['namespaces']);
        $container->setParameter(EcotoneCompilerPass::SERVICE_NAME, $config['serviceName']);
        $container->setParameter(EcotoneCompilerPass::FAIL_FAST_CONFIG, $config['failFast']);
        $container->setParameter(EcotoneCompilerPass::LOAD_SRC, $config['loadSrcNamespaces']);
        $container->setParameter(EcotoneCompilerPass::DEFAULT_SERIALIZATION_MEDIA_TYPE, $config['defaultSerializationMediaType']);
        $container->setParameter(EcotoneCompilerPass::ERROR_CHANNEL, $config['defaultErrorChannel']);
        $container->setParameter(EcotoneCompilerPass::DEFAULT_MEMORY_LIMIT, $config['defaultMemoryLimit']);
        $container->setParameter(EcotoneCompilerPass::DEFAULT_CONNECTION_EXCEPTION_RETRY, $config['defaultConnectionExceptionRetry'] ?? null);
    }
}
