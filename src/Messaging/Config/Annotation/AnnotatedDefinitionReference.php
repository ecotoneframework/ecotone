<?php


namespace Ecotone\Messaging\Config\Annotation;


use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotationFinder;

class AnnotatedDefinitionReference
{
    public static function getReferenceFor(AnnotatedDefinition $annotatedDefinition) : string
    {
        return (property_exists($annotatedDefinition->getAnnotationForClass(), 'referenceName') && $annotatedDefinition->getAnnotationForClass()->referenceName) ? $annotatedDefinition->getAnnotationForClass()->referenceName : $annotatedDefinition->getClassName();
    }

    public static function getSingleAnnotationForClass(AnnotationFinder $annotationFinder, string $className, string $annotationClassName) : ?object
    {
        $annotationClasses = $annotationFinder->getAnnotationsForClass($className);

        foreach ($annotationClasses as $annotationClass) {
            if (get_class($annotationClass) === $annotationClassName) {
                return $annotationClass;
            }
        }

        return null;
    }
}