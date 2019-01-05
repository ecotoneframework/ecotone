<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Enricher;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class PropertyReaderAccessor
 * @package SimplyCodedSoftware\Messaging\Handler\Enricher
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @param PropertyPath $propertyPath
     * @param mixed $fromData
     *
     * @return bool
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function hasPropertyValue(PropertyPath $propertyPath, $fromData) : bool
    {
        try {
            $this->getPropertyValue($propertyPath, $fromData);
        }catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $fromData
     * @param string $currentAccessProperty
     * @return mixed
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function getValueForCurrentState($fromData, string $currentAccessProperty)
    {
        if (is_array($fromData)) {
            if (!array_key_exists($currentAccessProperty, $fromData)) {
                throw InvalidArgumentException::create("Can't access property at `{$currentAccessProperty}`");
            }

            return $fromData[$currentAccessProperty];
        }elseif (is_object($fromData)) {
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