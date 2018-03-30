<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

/**
 * Class PayloadPropertySetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DataSetter
{
    private function __construct()
    {
    }

    /**
     * @return DataSetter
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @param PropertyPath $propertyNamePath
     * @param mixed $dataToEnrich
     * @param mixed $value
     *
     * @return mixed enriched data
     * @throws EnrichException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function enrichDataWith(PropertyPath $propertyNamePath, $dataToEnrich, $value)
    {
        $propertyName = $propertyNamePath->getPath();
        /** [0][data][worker] */
        preg_match("#^\[([a-zA-Z0-9]*)\]#", $propertyNamePath->getPath(), $startingWithPath);
        if ($this->hasAnyMatches($startingWithPath)) {
            $propertyName = $startingWithPath[1];
            $accessPropertyName = $startingWithPath[0];
            if ($accessPropertyName !== $propertyNamePath->getPath()) {
                $value = $this->enrichDataWith($this->cutOutCurrentAccessPropertyName($propertyNamePath, $accessPropertyName), $dataToEnrich[$propertyName], $value);
            }
        }else {
            /** worker[name] */
            preg_match('#\b([^\[\]]*)\[[a-zA-Z0-9]*\]#', $propertyNamePath->getPath(), $startingWithPropertyName);

            if ($this->hasAnyMatches($startingWithPropertyName)) {
                $propertyName = $startingWithPropertyName[1];

                if ($propertyName !== $propertyNamePath->getPath()) {
                    $value = $this->enrichDataWith($this->cutOutCurrentAccessPropertyName($propertyNamePath, $propertyName), $dataToEnrich[$propertyName], $value);
                }
            }
        }

        if (is_array($dataToEnrich)) {
            $newPayload = $dataToEnrich;
            $newPayload[$propertyName] = $value;

            return $newPayload;
        }

        if (is_object($dataToEnrich)) {
            $setterMethod = "set" . ucfirst($propertyName);

            if (method_exists($dataToEnrich, $setterMethod)) {
                $dataToEnrich->{$setterMethod}($value);

                return $dataToEnrich;
            }

            $objectReflection = new \ReflectionClass($dataToEnrich);

            if (!$objectReflection->hasProperty($propertyName)) {
                throw EnrichException::create("Object for enriching has no property named {$propertyName}");
            }

            $classProperty = $objectReflection->getProperty($propertyName);

            $classProperty->setAccessible(true);
            $classProperty->setValue($dataToEnrich, $value);

            return $dataToEnrich;
        }
    }

    /**
     * @param $matches
     *
     * @return bool
     */
    private function hasAnyMatches($matches): bool
    {
        return !empty($matches);
    }

    /**
     * @param PropertyPath $propertyName
     * @param string $accessPropertyName
     *
     * @return PropertyPath
     */
    private function cutOutCurrentAccessPropertyName(PropertyPath $propertyName, string $accessPropertyName) : PropertyPath
    {
        return PropertyPath::createWith(substr($propertyName->getPath(), strlen($accessPropertyName), strlen($propertyName->getPath())));
    }
}