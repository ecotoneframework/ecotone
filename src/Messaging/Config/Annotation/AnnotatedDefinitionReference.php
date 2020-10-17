<?php


namespace Ecotone\Messaging\Config\Annotation;


use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\ClassReference;

class AnnotatedDefinitionReference
{
    public static function getReferenceFor(AnnotatedFinding $annotatedDefinition): string
    {
        if ($annotatedDefinition->hasClassAnnotation(ClassReference::class)) {
            /** @var ClassReference $reference */
            $reference = $annotatedDefinition->getClassAnnotationsWithType(ClassReference::class)[0];

            return $reference->getReferenceName();
        }

        return $annotatedDefinition->getClassName();
    }

    public static function getSingleAnnotationForClass(AnnotationFinder $annotationFinder, string $className, string $annotationClassName): ?object
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