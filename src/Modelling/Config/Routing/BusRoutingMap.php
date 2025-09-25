<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use function array_keys;
use function class_exists;
use function interface_exists;

class BusRoutingMap
{
    public function __construct(
        /**
         * @var array<string, array<string, int|array<int>>> key is the class name, second key is the channel, value is the priority
         */
        protected array $objectRoutes = [],

        /**
         * @var array<string, int|array<int>> key is the channel, value is the priority
         */
        protected array $catchAllRoutes = [],
        /**
         * @var array<string, array<string, int|array<int>>> key is the route name, second key is the channel, value is the priority
         */
        protected array $namedRoutes = [],
        /**
         * @var array<string, array<string, int|array<int>>> key is the regex pattern, second key is the channel, value is the priority
         */
        protected array $regexRoutes = [],
        /**
         * @var array<string, string> key is the class name, value is the routing key
         */
        protected array $classToNameAliases = [],
        /**
         * @var array<string, string> key is the routing key, value is the class name
         */
        protected array $nameToClassAliases = [],

        /**
         * @var array<string, list<string>> key is the routing key, value is the full list of channels
         */
        protected array $optimizedRoutes = [],
    ) {
    }

    /**
     * @inheritDoc
     */
    public function get(string $routingKeyOrClass): array
    {
        if (isset($this->optimizedRoutes[$routingKeyOrClass])) {
            return $this->optimizedRoutes[$routingKeyOrClass];
        }

        return $this->resolveWithoutOptimization($routingKeyOrClass);
    }


    protected function resolveWithoutOptimization(string $routingKeyOrClass): array
    {
        $resultingChannels = [];
        $isObject = class_exists($routingKeyOrClass) || interface_exists($routingKeyOrClass);

        if ($isObject) {
            $className = $routingKeyOrClass;
            $routingKey = $this->classToNameAliases[$routingKeyOrClass] ?? null;
        } else {
            $routingKey = $routingKeyOrClass;
            $className = $this->nameToClassAliases[$routingKeyOrClass] ?? null;
        }

        if ($className) {
            $classRoutingKeys = $this->getClassInheritanceRoutes($className);
            foreach ($classRoutingKeys as $classRoutingKey) {
                $resultingChannels = array_merge($resultingChannels, $this->objectRoutes[$classRoutingKey]);
            }

            $resultingChannels = array_merge($resultingChannels, $this->catchAllRoutes);
        }

        if ($routingKey) {
            if (isset($this->namedRoutes[$routingKey])) {
                $resultingChannels = array_merge($resultingChannels, $this->namedRoutes[$routingKey]);
            }
            foreach ($this->regexRoutes as $pattern => $routes) {
                if (self::globMatch($pattern, $routingKey)) {
                    $resultingChannels = array_merge($resultingChannels, $routes);
                }
            }
        }

        uasort($resultingChannels, $this->sortByChannelPriority(...));

        return array_keys($resultingChannels);
    }

    public static function globMatch(string $pattern, string $route): bool
    {
        $pattern = str_replace('\\', '\\\\', $pattern);
        $pattern = str_replace('.', '\\.', $pattern);
        $pattern = str_replace('*', '.*', $pattern);

        return (bool) preg_match('#^' . $pattern . '$#i', $route);
    }

    /**
     * @param class-string $classString
     */
    private function getClassInheritanceRoutes(string $classString): array
    {
        $resultRoutingKeys = [];
        foreach ($this->objectRoutes as $routingKey => $routes) {
            if (is_a($classString, $routingKey, true)) {
                $resultRoutingKeys[] = $routingKey;
            }
        }
        return $resultRoutingKeys;
    }

    private function sortByChannelPriority(int|array $a, int|array $b): int
    {
        $a = is_array($a) ? $a : [$a];
        $b = is_array($b) ? $b : [$b];

        $maxLength = max(count($a), count($b));

        for ($i = 0; $i < $maxLength; $i++) {
            $aValue = $a[$i] ?? 0;
            $bValue = $b[$i] ?? 0;

            if ($aValue < $bValue) {
                return 1;
            } elseif ($aValue > $bValue) {
                return -1;
            }
        }

        return 0;
    }
}
