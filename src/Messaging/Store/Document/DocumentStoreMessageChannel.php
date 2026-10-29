<?php

namespace Ecotone\Messaging\Store\Document;

/**
 * licence Apache-2.0
 */
final class DocumentStoreMessageChannel
{
    public static function dropCollection(string $referenceName): string
    {
        return $referenceName . '_dropCollection';
    }

    public static function addDocument(string $referenceName): string
    {
        return $referenceName . '_addDocument';
    }

    public static function updateDocument(string $referenceName): string
    {
        return $referenceName . '_updateDocument';
    }

    public static function upsertDocument(string $referenceName): string
    {
        return $referenceName . '_upsertDocument';
    }

    public static function deleteDocument(string $referenceName): string
    {
        return $referenceName . '_deleteDocument';
    }

    public static function getDocument(string $referenceName): string
    {
        return $referenceName . '_getDocument';
    }

    public static function findDocument(string $referenceName): string
    {
        return $referenceName . '_findDocument';
    }

    public static function getAllDocuments(string $referenceName): string
    {
        return $referenceName . '_getAllDocuments';
    }

    public static function countDocuments(string $referenceName): string
    {
        return $referenceName . '_countDocuments';
    }
}
