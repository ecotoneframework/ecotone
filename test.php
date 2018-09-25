<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

class Some
{
    /**
     * @param int|null|array $test
     */
    public function test(?int $test) : void
    {

    }
}

/**
 * @author David Grudl <david@grudl.com>
 * @copyright David Grudl (https://davidgrudl.com)
 * @see https://github.com/nette/di/blob/ed1b90255688b08b87ae641f2bf1dfcba586ae5b/src/DI/PhpReflection.php
 */
class UseStatements
{
    /** @var array */
    private static $cache = [];
    /**
     * Expands class name into full name.
     *
     * @param  string
     * @return string  full name
     */
    public static function expandClassName($name, \ReflectionClass $rc)
    {
        $lower = strtolower($name);
        if (empty($name)) {
            throw new \InvalidArgumentException('Class name must not be empty.');
        } elseif (self::isBuiltinType($lower)) {
            return $lower;
        } elseif ($lower === 'self' || $lower === 'static' || $lower === '$this') {
            return $rc->getName();
        } elseif ($name[0] === '\\') { // fully qualified name
            return ltrim($name, '\\');
        }
        $uses = self::getUseStatements($rc);
        $parts = explode('\\', $name, 2);
        if (isset($uses[$parts[0]])) {
            $parts[0] = $uses[$parts[0]];
            return implode('\\', $parts);
        } elseif ($rc->inNamespace()) {
            return $rc->getNamespaceName() . '\\' . $name;
        } else {
            return $name;
        }
    }
    /**
     * @return array of [alias => class]
     */
    public static function getUseStatements(\ReflectionClass $class)
    {
        if (!isset(self::$cache[$name = $class->getName()])) {
            if ($class->isInternal()) {
                self::$cache[$name] = [];
            } else {
                $code = file_get_contents($class->getFileName());
                self::$cache = self::parseUseStatements($code, $name) + self::$cache;
            }
        }
        return self::$cache[$name];
    }
    /**
     * @param string $type
     * @return bool
     */
    public static function isBuiltinType($type)
    {
        return in_array(strtolower($type), ['string', 'int', 'float', 'bool', 'array', 'callable'], TRUE);
    }
    /**
     * Parses PHP code.
     *
     * @param  string
     * @return array of [class => [alias => class, ...]]
     */
    public static function parseUseStatements($code, $forClass = NULL)
    {
        $tokens = token_get_all($code);
        $namespace = $class = $classLevel = $level = NULL;
        $res = $uses = [];
        while (list(, $token) = each($tokens)) {
            switch (is_array($token) ? $token[0] : $token) {
                case T_NAMESPACE:
                    $namespace = ltrim(self::fetch($tokens, [T_STRING, T_NS_SEPARATOR]) . '\\', '\\');
                    $uses = [];
                    break;
                case T_CLASS:
                case T_INTERFACE:
                case T_TRAIT:
                    if ($name = self::fetch($tokens, T_STRING)) {
                        $class = $namespace . $name;
                        $classLevel = $level + 1;
                        $res[$class] = $uses;
                        if ($class === $forClass) {
                            return $res;
                        }
                    }
                    break;
                case T_USE:
                    while (!$class && ($name = self::fetch($tokens, [T_STRING, T_NS_SEPARATOR]))) {
                        $name = ltrim($name, '\\');
                        if (self::fetch($tokens, '{')) {
                            while ($suffix = self::fetch($tokens, [T_STRING, T_NS_SEPARATOR])) {
                                if (self::fetch($tokens, T_AS)) {
                                    $uses[self::fetch($tokens, T_STRING)] = $name . $suffix;
                                } else {
                                    $tmp = explode('\\', $suffix);
                                    $uses[end($tmp)] = $name . $suffix;
                                }
                                if (!self::fetch($tokens, ',')) {
                                    break;
                                }
                            }
                        } elseif (self::fetch($tokens, T_AS)) {
                            $uses[self::fetch($tokens, T_STRING)] = $name;
                        } else {
                            $tmp = explode('\\', $name);
                            $uses[end($tmp)] = $name;
                        }
                        if (!self::fetch($tokens, ',')) {
                            break;
                        }
                    }
                    break;
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case '{':
                    $level++;
                    break;
                case '}':
                    if ($level === $classLevel) {
                        $class = $classLevel = NULL;
                    }
                    $level--;
            }
        }
        return $res;
    }
    private static function fetch(& $tokens, $take)
    {
        $res = NULL;
        while ($token = current($tokens)) {
            list($token, $s) = is_array($token) ? $token : [$token, $token];
            if (in_array($token, (array) $take, TRUE)) {
                $res .= $s;
            } elseif (!in_array($token, [T_DOC_COMMENT, T_WHITESPACE, T_COMMENT], TRUE)) {
                break;
            }
            next($tokens);
        }
        return $res;
    }
}

//$method = new ReflectionMethod(\Fixture\Dto\OrderExample::class, "isSameAs");

//$tokenParser = new \Doctrine\Common\Annotations\TokenParser($method->getDocComment());
//
//
//var_dump($method->getDocComment());
//
//$factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
//$docblock = $factory->create($method->getDocComment());
//
//
//// Contains the summary for this DocBlock
//$summary = $docblock->getSummary();
//
//// Contains \phpDocumentor\Reflection\DocBlock\Description object
//$description = $docblock->getDescription();
//
//// You can either cast it to string
//$description = (string) $docblock->getDescription();
//
//// Or use the render method to get a string representation of the Description.
//$description = $docblock->getDescription()->render();
//
///** @var \phpDocumentor\Reflection\DocBlock\Tags\Param $tag */
//$tag = $docblock->getTagsByName("param")[0];
//
//
//var_dump($tag->getType());


$useStatements = new UseStatements();
$statements = $useStatements->getUseStatements(new ReflectionClass(\Fixture\Dto\OrderExample::class));

//var_dump($statements);
$rc = new ReflectionClass(\Fixture\Dto\OrderExample::class);
//var_dump($useStatements->expandClassName("OrderExample", $rc));

$code = file_get_contents($rc->getFileName());
$tokens = token_get_all($code);

$statements = [];
$getStatement = false;
$currentStatement = "";
$lastClassNamePart = "";
foreach ($tokens as $token) {
    if (is_array($token)) {
        if ($token[0] == T_USE) {
            $getStatement = true;
            continue;
        }

        if ($getStatement) {
            $currentStatement .= $token[1];
            $lastClassNamePart = $token[1];
            continue;
        }
    }
    if ($token === ';' && $getStatement) {
        $statements[$lastClassNamePart] = $currentStatement;
        $lastClassNamePart = "";
        $getStatement = false;
        $currentStatement = "";
    }
}

var_dump($statements);