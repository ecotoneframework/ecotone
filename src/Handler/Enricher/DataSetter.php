<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class PayloadPropertySetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter
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
     * @param string  $propertyName
     * @param mixed $dataToEnrich
     * @param mixed   $value
     *
     * @return mixed enriched data
     * @throws EnrichException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function enrichDataWith(string $propertyName, $dataToEnrich, $value)
    {
        preg_match("#\[([a-zA-Z0-9]*)\]#", $propertyName, $matches);
        if ($this->hasAnyMatches($matches)) {
            $propertyName = $matches[1];
            $accessPropertyName = $matches[0];
            if ($accessPropertyName !== $propertyName) {
                $dataToEnrich = $this->enrichDataWith($this->cutOutCurrentAccessPropertyName($propertyName, $accessPropertyName), $dataToEnrich[$propertyName], $value);
            }
        }

        if (is_array($dataToEnrich)) {
            $newPayload                = $dataToEnrich;
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
     * @param string $propertyName
     * @param        $accessPropertyName
     *
     * @return bool|string
     */
    private function cutOutCurrentAccessPropertyName(string $propertyName, $accessPropertyName)
    {
        return substr($propertyName, count($accessPropertyName) - 1, count($propertyName));
    }
}