<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;


use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Annotation\OneTimeCommand;
use Ecotone\Messaging\Annotation\Scheduled;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\OneTimeCommandConfiguration;
use Ecotone\Messaging\Config\OneTimeCommandParameter;
use Ecotone\Messaging\Config\OneTimeCommandResultSet;
use Ecotone\Messaging\Endpoint\ConsumerLifecycleBuilder;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * @ModuleAnnotation()
 */
class OneTimeCommandModule extends NoExternalConfigurationModule implements AnnotationModule
{
    const ECOTONE_COMMAND_PARAMETER_PREFIX = "ecotone.oneTimeCommand.";

    /**
     * @var ServiceActivatorBuilder[]
     */
    private array $oneTimeCommandHandlers;
    /**
     * @var OneTimeCommandConfiguration[]
     */
    private array $oneTimeCommandConfigurations;

    private function __construct(array $oneTimeCommands, array $oneTimeCommandConfigurations)
    {
        $this->oneTimeCommandHandlers = $oneTimeCommands;
        $this->oneTimeCommandConfigurations = $oneTimeCommandConfigurations;
    }

    public static function create(AnnotationFinder $annotationRegistrationService): AnnotationModule
    {
        $messageHandlerBuilders    = [];
        $oneTimeConfigurations = [];

        foreach ($annotationRegistrationService->findAnnotatedMethods(OneTimeCommand::class) as $annotationRegistration) {
            /** @var OneTimeCommand $annotation */
            $annotation               = $annotationRegistration->getAnnotationForMethod();
            $commandName                     = $annotation->name;
            $className    = $annotationRegistration->getClassName();
            $methodName               = $annotationRegistration->getMethodName();

            list($messageHandlerBuilder, $oneTimeCommandConfiguration) = self::prepareOneTimeCommand($className, $methodName, $commandName);

            $messageHandlerBuilders[] = $messageHandlerBuilder;
            $oneTimeConfigurations[]     = $oneTimeCommandConfiguration;
        }

        return new static($messageHandlerBuilders, $oneTimeConfigurations);
    }

    public static function prepareOneTimeCommand(string $className, string $methodName, string $commandName): array
    {
        $parameterConverters = [];
        $parameters          = [];
        $classReflection     = new \ReflectionClass($className);

        $interfaceToCall = InterfaceToCall::create($className, $methodName);
        if ($classReflection->getConstructor() && $classReflection->getConstructor()->getParameters()) {
            throw InvalidArgumentException::create("One Time Command {$interfaceToCall} must not have constructor parameters");
        }

        if ($interfaceToCall->canReturnValue() && !$interfaceToCall->getReturnType()->equals(TypeDescriptor::create(OneTimeCommandResultSet::class))) {
            throw InvalidArgumentException::create("One Time Command {$interfaceToCall} must have void or " . OneTimeCommandResultSet::class . " return type");
        }

        foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
            if ($interfaceParameter->getTypeDescriptor()->isClassOrInterface()) {
                $parameterConverters[] = ReferenceBuilder::create($interfaceParameter->getName(), $interfaceParameter->getTypeDescriptor()->toString());
            } else {
                $parameterConverters[] = HeaderBuilder::create($interfaceParameter->getName(), self::ECOTONE_COMMAND_PARAMETER_PREFIX . $interfaceParameter->getName());
                $parameters[]          = $interfaceParameter->hasDefaultValue()
                    ? OneTimeCommandParameter::createWithDefaultValue($interfaceParameter->getName(), $interfaceParameter->getDefaultValue())
                    : OneTimeCommandParameter::create($interfaceParameter->getName());
            }
        }

        $inputChannel                = "ecotone.channel." . $commandName;
        $messageHandlerBuilder    = ServiceActivatorBuilder::createWithDirectReference(new $className(), $methodName)
            ->withEndpointId("ecotone.endpoint." . $commandName)
            ->withInputChannelName($inputChannel)
            ->withMethodParameterConverters($parameterConverters);
        $oneTimeCommandConfiguration = OneTimeCommandConfiguration::create($inputChannel, $commandName, $parameters);

        return array($messageHandlerBuilder, $oneTimeCommandConfiguration);
    }

    public function prepare(Configuration $configuration,array $extensionObjects,ModuleReferenceSearchService $moduleReferenceSearchService) : void
    {
        foreach ($this->oneTimeCommandHandlers as $oneTimeCommand) {
            $configuration->registerMessageHandler($oneTimeCommand);
        }
        foreach ($this->oneTimeCommandConfigurations as $oneTimeCommandConfiguration) {
            $configuration->registerOneTimeCommand($oneTimeCommandConfiguration);
        }
    }

    public function canHandle($extensionObject) : bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "oneTimeCommandModule";
    }
}