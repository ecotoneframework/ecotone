<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\ClassLocator;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\ClassMetadataReader;

/**
 * Class BaseAnnotationConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class BaseAnnotationConfiguration implements AnnotationConfiguration
{
    /**
     * @var ClassLocator
     */
    protected $classLocator;
    /**
     * @var ClassMetadataReader
     */
    protected $classMetadataReader;

    /**
     * @inheritDoc
     */
    public function setClassLocator(ClassLocator $classLocator): void
    {
        $this->classLocator = $classLocator;
    }

    /**
     * @inheritDoc
     */
    public function setClassMetadataReader(ClassMetadataReader $classMetadataReader): void
    {
        $this->classMetadataReader = $classMetadataReader;
    }
}