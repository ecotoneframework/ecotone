<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use function array_unique;
use function class_exists;
use function usort;

class BusRoutingMap
{
    public function __construct(
        /**
         * @var array<string, int|array<int>> key is the channel name, value is the priority
         */
        protected array $channelsPriority = [],
        /**
         * @var array<string, list<string>> key is the class name, value is the list of channels
         */
        protected array $objectRoutes = [],

        /**
         * @var list<string> list of channels
         */
        protected array $catchAllRoutes = [],
        /**
         * @var array<string, list<string>> key is the route name, value is the list of channels
         */
        protected array $namedRoutes = [],
        /**
         * @var array<string, list<string>> key is the regex pattern, value is the list of channels
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
        $result = [];
        $isObject = class_exists($routingKeyOrClass);

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
                $result = array_merge($result, $this->objectRoutes[$classRoutingKey]);
            }

            $result = array_merge($result, $this->catchAllRoutes);
        }

        if ($routingKey) {
            if (isset($this->namedRoutes[$routingKey])) {
                $result = array_merge($result, $this->namedRoutes[$routingKey]);
            }
            foreach ($this->regexRoutes as $pattern => $routes) {
                if (self::globMatch($pattern, $routingKey)) {
                    $result = array_merge($result, $routes);
                }
            }
        }

        $result = array_unique($result);

        usort($result, $this->sortByChannelPriority(...));

        return $result;
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

    private function sortByChannelPriority(string $aChannel, string $bChannel): int
    {
        $a = $this->channelsPriority[$aChannel];
        $b = $this->channelsPriority[$bChannel];

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
