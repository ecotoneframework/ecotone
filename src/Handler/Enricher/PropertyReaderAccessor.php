<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class PropertyReaderAccessor
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PropertyReaderAccessor
{
    /**
     * @param PropertyPath $propertyPath
     * @param mixed $fromData
     *
     * @return mixed
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function getPropertyValue(PropertyPath $propertyPath, $fromData)
    {
        $cutCurrent = $propertyPath->cutCurrentAccessProperty();
        $currentAccessProperty = $propertyPath->getCurrentAccessProperty();

        if ($cutCurrent) {
            return $this->getPropertyValue($cutCurrent, $this->getValueForCurrentState($fromData, $currentAccessProperty));
        }

        return $this->getValueForCurrentState($fromData, $currentAccessProperty);
    }

    /**
     * @param mixed $fromData
     * @param string $currentAccessProperty
     * @return mixed
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function getValueForCurrentState($fromData, string $currentAccessProperty)
    {
        if (is_array($fromData)) {
            return $fromData[$currentAccessProperty];
        }else {
            $getterMethod = "get" . ucfirst($currentAccessProperty);

            if (method_exists($fromData, $getterMethod)) {
                return call_user_func([$fromData, $getterMethod]);
            }else {
                $objectReflection = new \ReflectionClass($fromData);
                $classProperty = $objectReflection->getProperty($currentAccessProperty);
                $classProperty->setAccessible(true);

                return $classProperty->getValue($fromData);
            }
        }

        throw InvalidArgumentException::create("Can't access property at `{$currentAccessProperty}`");
    }
}