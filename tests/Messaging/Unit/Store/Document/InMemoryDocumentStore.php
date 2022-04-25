<?php

namespace Test\Ecotone\Dbal\Store\Document;

use Ecotone\Messaging\Store\Document\DocumentException;
use PHPUnit\Framework\TestCase;

class InMemoryDocumentStore extends TestCase
{
    public function test_adding_document_to_collection()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $this->assertEquals(0, $documentStore->countDocuments('users'));

        $documentStore->addDocument('users', '123', 'Johny');

        $this->assertEquals('Johny', $documentStore->getDocument('users', '123'));
        $this->assertEquals(1, $documentStore->countDocuments('users'));
    }

    public function test_deleting_document()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $documentStore->addDocument('users', '123', 'Johny');
        $documentStore->deleteDocument('users', '123');

        $this->assertEquals(0, $documentStore->countDocuments('users'));
        $this->expectException(DocumentException::class);

        $documentStore->getDocument('users', '123');
    }

    public function test_dropping_collection()
    {
        $documentStore = $this->getMemoryDocumentStore();
        $documentStore->addDocument('users', '123', 'Johny');
        $documentStore->addDocument('users', '124', 'Johny');

        $documentStore->dropCollection('users');

        $this->assertEquals(0, $documentStore->countDocuments('users'));
    }

    public function test_replacing_document()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $this->assertEquals(0, $documentStore->countDocuments('users'));

        $documentStore->addDocument('users', '123', 'Johny');
        $documentStore->upsertDocument('users', '123', 'Johny Mac');

        $this->assertEquals('Johny Mac', $documentStore->getDocument('users', '123'));
    }

    public function test_excepting_if_trying_to_add_document_twice()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $this->expectException(DocumentException::class);

        $documentStore->addDocument('users', '123', 'Johny');
        $documentStore->addDocument('users', '123', 'Johny Mac');
    }

    private function getMemoryDocumentStore(): \Ecotone\Messaging\Store\Document\InMemoryDocumentStore
    {
        return \Ecotone\Messaging\Store\Document\InMemoryDocumentStore::createEmpty();
    }
}