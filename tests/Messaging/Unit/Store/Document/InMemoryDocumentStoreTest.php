<?php

namespace Test\Ecotone\Dbal\Store\Document;

use Ecotone\Messaging\Store\Document\DocumentException;
use Ecotone\Messaging\Store\Document\DocumentStore;
use PHPUnit\Framework\TestCase;

class InMemoryDocumentStoreTest extends TestCase
{
    public function test_adding_document_to_collection()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $this->assertEquals(0, $documentStore->countDocuments('users'));

        $documentStore->addDocument('users', '123', '{"name":"Johny"}');

        $this->assertEquals('{"name":"Johny"}', $documentStore->getDocument('users', '123'));
        $this->assertEquals(1, $documentStore->countDocuments('users'));
    }

    public function test_updating_document()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $this->assertEquals(0, $documentStore->countDocuments('users'));

        $documentStore->addDocument('users', '123', '{"name":"Johny"}');
        $documentStore->updateDocument('users', '123', '{"name":"Franco"}');

        $this->assertEquals('{"name":"Franco"}', $documentStore->getDocument('users', '123'));
    }

    public function test_throwing_exception_when_updating_non_existing_document()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $this->assertEquals(0, $documentStore->countDocuments('users'));

        $this->expectException(DocumentException::class);

        $documentStore->updateDocument('users', '123', '{"name":"Franco"}');
    }

    public function test_adding_document_as_object_should_return_object()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $this->assertEquals(0, $documentStore->countDocuments('users'));

        $documentStore->addDocument('users', '123', new \stdClass());

        $this->assertEquals(new \stdClass(), $documentStore->getDocument('users', '123'));
    }

    public function test_adding_non_json_document_should_fail()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $this->assertEquals(0, $documentStore->countDocuments('users'));

        $this->expectException(DocumentException::class);

        $documentStore->addDocument('users', '123', '{"name":');
    }

    public function test_deleting_document()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $documentStore->addDocument('users', '123', '{"name":"Johny"}');
        $documentStore->deleteDocument('users', '123');

        $this->assertEquals(0, $documentStore->countDocuments('users'));
        $this->expectException(DocumentException::class);

        $documentStore->getDocument('users', '123');
    }

    public function test_dropping_collection()
    {
        $documentStore = $this->getMemoryDocumentStore();
        $documentStore->addDocument('users', '123', '{"name":"Johny"}');
        $documentStore->addDocument('users', '124', '{"name":"Johny"}');

        $documentStore->dropCollection('users');

        $this->assertEquals(0, $documentStore->countDocuments('users'));
    }

    public function test_retrieving_whole_collection()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $this->assertEquals([],$documentStore->getAllDocuments('users'));

        $documentStore->addDocument('users', '123', '{"name":"Johny"}');
        $documentStore->addDocument('users', '124', '{"name":"Franco"}');

        $this->assertEquals(
            ['{"name":"Johny"}', '{"name":"Franco"}'],
            $documentStore->getAllDocuments('users')
        );
    }

    public function test_replacing_document()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $this->assertEquals(0, $documentStore->countDocuments('users'));

        $documentStore->addDocument('users', '123', '{"name":"Johny"}');
        $documentStore->upsertDocument('users', '123', '{"name":"Johny Mac"}');

        $this->assertEquals('{"name":"Johny Mac"}', $documentStore->getDocument('users', '123'));
    }

    public function test_excepting_if_trying_to_add_document_twice()
    {
        $documentStore = $this->getMemoryDocumentStore();

        $this->expectException(DocumentException::class);

        $documentStore->addDocument('users', '123', '{"name":"Johny"}');
        $documentStore->addDocument('users', '123', '{"name":"Johny Mac"}');
    }

    private function getMemoryDocumentStore(): DocumentStore
    {
        return \Ecotone\Messaging\Store\Document\InMemoryDocumentStore::createEmpty();
    }
}