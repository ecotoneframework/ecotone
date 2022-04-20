<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;


use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotatedMethod;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\ConsoleParameterOption;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Attribute\Scheduled;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use Ecotone\Messaging\Config\ConsoleCommandParameter;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;
use Ecotone\Messaging\Endpoint\ConsumerLifecycleBuilder;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ramsey\Uuid\Uuid;

#[ModuleAnnotation]
final class ConsoleCommandModule extends NoExternalConfigurationModule implements AnnotationModule
{
    const ECOTONE_COMMAND_PARAMETER_PREFIX = "ecotone.oneTimeCommand.";

    /**
     * @var ServiceActivatorBuilder[]
     */
    private array $oneTimeCommandHandlers;
    /**
     * @var ConsoleCommandConfiguration[]
     */
    private array $oneTimeCommandConfigurations;

    private function __construct(array $oneTimeCommands, array $oneTimeCommandConfigurations)
    {
        $this->oneTimeCommandHandlers = $oneTimeCommands;
        $this->oneTimeCommandConfigurations = $oneTimeCommandConfigurations;
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $messageHandlerBuilders    = [];
        $oneTimeConfigurations = [];

        foreach ($annotationRegistrationService->findAnnotatedMethods(ConsoleCommand::class) as $annotationRegistration) {
            /** @var ConsoleCommand $annotation */
            $annotation               = $annotationRegistration->getAnnotationForMethod();
            $commandName                     = $annotation->getName();
            $className    = $annotationRegistration->getClassName();
            $methodName               = $annotationRegistration->getMethodName();

            list($messageHandlerBuilder, $oneTimeCommandConfiguration) = self::prepareConsoleCommand($interfaceToCallRegistry, $annotationRegistration, $className, $methodName, $commandName);

            $messageHandlerBuilders[] = $messageHandlerBuilder;
            $oneTimeConfigurations[]     = $oneTimeCommandConfiguration;
        }

        return new static($messageHandlerBuilders, $oneTimeConfigurations);
    }

    public static function prepareConsoleCommand(InterfaceToCallRegistry $interfaceToCallRegistry, AnnotatedMethod $annotatedMethod, string $className, string $methodName, string $commandName): array
    {
        $parameterConverters = [];
        $parameters          = [];

        list($parameterConverters, $parameters) = self::prepareParameter($interfaceToCallRegistry, $className, $methodName, $parameterConverters, $parameters);

        $inputChannel                = "ecotone.channel." . $commandName;

        $messageHandlerBuilder       = ServiceActivatorBuilder::create(AnnotatedDefinitionReference::getReferenceFor($annotatedMethod), $methodName)
            ->withEndpointId("ecotone.endpoint." . $commandName)
            ->withEndpointAnnotations([$annotatedMethod->getAnnotationForMethod()])
            ->withInputChannelName($inputChannel)
            ->withMethodParameterConverters($parameterConverters);
        $oneTimeCommandConfiguration = ConsoleCommandConfiguration::create($inputChannel, $commandName, $parameters);

        return array($messageHandlerBuilder, $oneTimeCommandConfiguration);
    }

    public static function prepareConsoleCommandForDirectObject(object $directObject, string $methodName, string $commandName, bool $discoverableByConsoleCommandAttribute, InterfaceToCallRegistry $interfaceToCallRegistry)
    {
        $className = get_class($directObject);
        $parameterConverters = [];
        $parameters          = [];

        list($parameterConverters, $parameters) = self::prepareParameter($interfaceToCallRegistry, $className, $methodName, $parameterConverters, $parameters);

        $inputChannel                = "ecotone.channel." . $commandName;
        $messageHandlerBuilder       = ServiceActivatorBuilder::createWithDirectReference($directObject, $methodName)
            ->withEndpointId("ecotone.endpoint." . $commandName)
            ->withEndpointAnnotations($discoverableByConsoleCommandAttribute ? [new ConsoleCommand($commandName)] : [])
            ->withInputChannelName($inputChannel)
            ->withMethodParameterConverters($parameterConverters);
        $oneTimeCommandConfiguration = ConsoleCommandConfiguration::create($inputChannel, $commandName, $parameters);

        return array($messageHandlerBuilder, $oneTimeCommandConfiguration);
    }

    private static function prepareParameter(InterfaceToCallRegistry $interfaceToCallRegistry, bool|string $className, string $methodName, array $parameterConverters, array $parameters): array
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor($className, $methodName);

        if ($interfaceToCall->canReturnValue() && !$interfaceToCall->getReturnType()->equals(TypeDescriptor::create(ConsoleCommandResultSet::class))) {
            throw InvalidArgumentException::create("One Time Command {$interfaceToCall} must have void or " . ConsoleCommandResultSet::class . " return type");
        }

        foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
            if ($interfaceParameter->getTypeDescriptor()->isClassOrInterface()) {
                $parameterConverters[] = ReferenceBuilder::create($interfaceParameter->getName(), $interfaceParameter->getTypeDescriptor()->toString());
            } else {
                $headerName = self::ECOTONE_COMMAND_PARAMETER_PREFIX . $interfaceParameter->getName();
                $parameterConverters[] = HeaderBuilder::create($interfaceParameter->getName(), $headerName);
                $parameters[]          = $interfaceParameter->hasDefaultValue()
                    ? ConsoleCommandParameter::createWithDefaultValue($interfaceParameter->getName(), $headerName, $interfaceParameter->hasAnnotation(ConsoleParameterOption::class), $interfaceParameter->getDefaultValue())
                    : ConsoleCommandParameter::create($interfaceParameter->getName(), $headerName, $interfaceParameter->getTypeDescriptor()->isBoolean());
            }
        }

        return array($parameterConverters, $parameters);
    }

    public function prepare(Configuration $configuration,array $extensionObjects,ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry) : void
    {
        foreach ($this->oneTimeCommandHandlers as $oneTimeCommand) {
            $configuration->registerMessageHandler($oneTimeCommand);
        }
        foreach ($this->oneTimeCommandConfigurations as $oneTimeCommandConfiguration) {
            $configuration->registerConsoleCommand($oneTimeCommandConfiguration);
        }
    }

    public function canHandle($extensionObject) : bool
    {
        return false;
    }
}