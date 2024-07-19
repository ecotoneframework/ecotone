<?php

namespace Ecotone\Messaging\Config\Annotation;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ClassReference;

/**
 * licence Apache-2.0
 */
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

    public static function getReferenceForClassName(AnnotationFinder $annotationRegistrationServiceFinder, string $className): string
    {
        $reference = $className;

        foreach ($annotationRegistrationServiceFinder->getAnnotationsForClass($className) as $annotation) {
            if ($annotation instanceof ClassReference) {
                $reference = $annotation->getReferenceName();
            }
        }

        return $reference;
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
