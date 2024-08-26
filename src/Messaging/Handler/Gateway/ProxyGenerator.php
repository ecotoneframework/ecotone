<?php

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\Container\GatewayProxyReference;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Reflection\ClassReflection;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * licence Apache-2.0
 */
class ProxyGenerator
{
    public function __construct(private string $namespace)
    {
    }

    /**
     * @param class-string $implementedInterface
     * @return string
     * @throws ReflectionException
     */
    public function generateProxyFor(string $className, string $implementedInterface): string
    {
        $classGenerator = new ClassGenerator($className, $this->namespace);
        $classGenerator->setImplementedInterfaces([$implementedInterface]);

        $messagingSystemParameter = new ParameterGenerator('messagingSystem', ConfiguredMessagingSystem::class);
        $messagingSystemProperty = new PropertyGenerator('messagingSystem', null, PropertyGenerator::FLAG_PRIVATE);
        $messagingSystemProperty->omitDefaultValue(true);
        $classGenerator->addPropertyFromGenerator($messagingSystemProperty);

        $gatewayProxyReferenceParameter = new ParameterGenerator('gatewayProxyReference', GatewayProxyReference::class);
        $gatewayProxyReferenceProperty = new PropertyGenerator('gatewayProxyReference', null, PropertyGenerator::FLAG_PRIVATE);
        $gatewayProxyReferenceProperty->omitDefaultValue(true);
        $classGenerator->addPropertyFromGenerator($gatewayProxyReferenceProperty);

        $classGenerator->addMethod('__construct', [
            $messagingSystemParameter, $gatewayProxyReferenceParameter,
        ], MethodGenerator::FLAG_PUBLIC, '$this->messagingSystem = $messagingSystem; $this->gatewayProxyReference = $gatewayProxyReference;');

        $reflectionClass = new ClassReflection($implementedInterface);
        foreach ($reflectionClass->getMethods() as $method) {
            $methodGenerator = MethodGenerator::fromReflection($method);
            $methodGenerator->setInterface(false);
            if ($method->getReturnType() instanceof ReflectionNamedType && $method->getReturnType()->getName() === 'void') {
                // Do not return if method expects void return type
                $return = '';
            } else {
                $return = 'return ';
            }
            $parameterNames = array_map(function (ReflectionParameter $parameter) {
                return '$' . $parameter->getName();
            }, $method->getParameters());
            $executeCallParameters = implode(', ', $parameterNames);
            $methodGenerator->setBody("$return\$this->messagingSystem->getNonProxyGatewayByName(\$this->gatewayProxyReference->gatewayReferenceForMethod('{$method->getName()}'))->execute([$executeCallParameters]);");
            $classGenerator->addMethodFromGenerator($methodGenerator);
        }

        return $classGenerator->generate();
    }
}
