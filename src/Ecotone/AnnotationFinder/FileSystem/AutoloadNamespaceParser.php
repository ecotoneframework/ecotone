<?php
declare(strict_types=1);

namespace Ecotone\AnnotationFinder\FileSystem;

/**
 * Interface AutoloadNamespaceParser
 * @package Ecotone\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AutoloadNamespaceParser
{
    /**
     * @return string[]
     */
    public function getNamespacesForGivenCatalog(array $autoloadData, string $catalogToLoad) : array;

    public function getFor(array $requiredNamespaces, array $autoload, bool $autoloadPsr4) : array;
}