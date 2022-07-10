<?php

namespace Ecotone\Dbal\DocumentStore;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Dbal\Configuration\DbalMessagePublisherConfiguration;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Ecotone\Messaging\Store\Document\DocumentStoreMessageChannel;
use Ecotone\Messaging\Store\Document\InMemoryDocumentStore;

#[ModuleAnnotation]
class DbalDocumentStoreModule implements AnnotationModule
{
    const ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME = "ecotone.dbal.documentStore.collectionName";
    const ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID = "ecotone.dbal.documentStore.documentId";
    const COLLECTION_NAME_PARAMETER = "collectionName";
    const DOCUMENT_ID_PARAMETER = "documentId";
    const DOCUMENT_PARAMETER = "document";

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $dbalConfiguration = $this->getDbalConfiguration($extensionObjects);

        if (!$dbalConfiguration->isEnableDbalDocumentStore()) {
            return;
        }

        $referenceName = $dbalConfiguration->getDbalDocumentStoreReference();
        $inMemoryDocumentStore = InMemoryDocumentStore::createEmpty();

        $configuration
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($referenceName, DocumentStore::class, "dropCollection", DocumentStoreMessageChannel::dropCollection($referenceName))
                    ->withParameterConverters(
                        [
                            GatewayHeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME)
                        ]
                    )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($referenceName, DocumentStore::class, "addDocument", DocumentStoreMessageChannel::addDocument($referenceName))
                    ->withParameterConverters(
                        [
                            GatewayHeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                            GatewayHeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID),
                            GatewayPayloadBuilder::create(self::DOCUMENT_PARAMETER)
                        ]
                    )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($referenceName, DocumentStore::class, "updateDocument", DocumentStoreMessageChannel::updateDocument($referenceName))
                    ->withParameterConverters(
                        [
                            GatewayHeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                            GatewayHeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID),
                            GatewayPayloadBuilder::create(self::DOCUMENT_PARAMETER)
                        ]
                    )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($referenceName, DocumentStore::class, "upsertDocument", DocumentStoreMessageChannel::upsertDocument($referenceName))
                    ->withParameterConverters(
                        [
                            GatewayHeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                            GatewayHeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID),
                            GatewayPayloadBuilder::create(self::DOCUMENT_PARAMETER)
                        ]
                    )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($referenceName, DocumentStore::class, "deleteDocument", DocumentStoreMessageChannel::deleteDocument($referenceName))
                    ->withParameterConverters(
                        [
                            GatewayHeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                            GatewayHeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID)
                        ]
                    )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($referenceName, DocumentStore::class, "getDocument", DocumentStoreMessageChannel::getDocument($referenceName))
                    ->withParameterConverters(
                        [
                            GatewayHeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                            GatewayHeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID)
                        ]
                    )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($referenceName, DocumentStore::class, "findDocument", DocumentStoreMessageChannel::findDocument($referenceName))
                    ->withParameterConverters(
                        [
                            GatewayHeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                            GatewayHeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID)
                        ]
                    )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($referenceName, DocumentStore::class, "countDocuments", DocumentStoreMessageChannel::countDocuments($referenceName))
                    ->withParameterConverters(
                        [
                            GatewayHeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME)
                        ]
                    )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($referenceName, DocumentStore::class, "getAllDocuments", DocumentStoreMessageChannel::getAllDocuments($referenceName))
                    ->withParameterConverters(
                        [
                            GatewayHeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME)
                        ]
                    )
            )
            ->registerMessageHandler(
                new DbalDocumentStoreBuilder(DocumentStoreMessageChannel::dropCollection($referenceName), "dropCollection", $dbalConfiguration->isInitializeDbalDocumentStore(), $dbalConfiguration->getDocumentStoreConnectionReference(), $dbalConfiguration->isInMemoryDocumentStore(), $inMemoryDocumentStore, [
                    HeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME)
                ])
            )
            ->registerMessageHandler(
                new DbalDocumentStoreBuilder(DocumentStoreMessageChannel::addDocument($referenceName), "addDocument", $dbalConfiguration->isInitializeDbalDocumentStore(), $dbalConfiguration->getDocumentStoreConnectionReference(), $dbalConfiguration->isInMemoryDocumentStore(), $inMemoryDocumentStore, [
                    HeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                    HeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID),
                    PayloadBuilder::create(self::DOCUMENT_PARAMETER),
                ])
            )
            ->registerMessageHandler(
                new DbalDocumentStoreBuilder(DocumentStoreMessageChannel::updateDocument($referenceName), "updateDocument", $dbalConfiguration->isInitializeDbalDocumentStore(), $dbalConfiguration->getDocumentStoreConnectionReference(), $dbalConfiguration->isInMemoryDocumentStore(), $inMemoryDocumentStore, [
                    HeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                    HeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID),
                    PayloadBuilder::create(self::DOCUMENT_PARAMETER),
                ])
            )
            ->registerMessageHandler(
                new DbalDocumentStoreBuilder(DocumentStoreMessageChannel::upsertDocument($referenceName), "upsertDocument", $dbalConfiguration->isInitializeDbalDocumentStore(), $dbalConfiguration->getDocumentStoreConnectionReference(), $dbalConfiguration->isInMemoryDocumentStore(), $inMemoryDocumentStore, [
                    HeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                    HeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID),
                    PayloadBuilder::create(self::DOCUMENT_PARAMETER),
                ])
            )
            ->registerMessageHandler(
                new DbalDocumentStoreBuilder(DocumentStoreMessageChannel::deleteDocument($referenceName), "deleteDocument", $dbalConfiguration->isInitializeDbalDocumentStore(), $dbalConfiguration->getDocumentStoreConnectionReference(), $dbalConfiguration->isInMemoryDocumentStore(), $inMemoryDocumentStore, [
                    HeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                    HeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID)
                ])
            )
            ->registerMessageHandler(
                new DbalDocumentStoreBuilder(DocumentStoreMessageChannel::getDocument($referenceName), "getDocument", $dbalConfiguration->isInitializeDbalDocumentStore(), $dbalConfiguration->getDocumentStoreConnectionReference(), $dbalConfiguration->isInMemoryDocumentStore(), $inMemoryDocumentStore, [
                    HeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                    HeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID)
                ])
            )
            ->registerMessageHandler(
                new DbalDocumentStoreBuilder(DocumentStoreMessageChannel::findDocument($referenceName), "findDocument", $dbalConfiguration->isInitializeDbalDocumentStore(), $dbalConfiguration->getDocumentStoreConnectionReference(), $dbalConfiguration->isInMemoryDocumentStore(), $inMemoryDocumentStore, [
                    HeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME),
                    HeaderBuilder::create(self::DOCUMENT_ID_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_DOCUMENT_ID)
                ])
            )
            ->registerMessageHandler(
                new DbalDocumentStoreBuilder(DocumentStoreMessageChannel::countDocuments($referenceName), "countDocuments", $dbalConfiguration->isInitializeDbalDocumentStore(), $dbalConfiguration->getDocumentStoreConnectionReference(), $dbalConfiguration->isInMemoryDocumentStore(), $inMemoryDocumentStore, [
                    HeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME)
                ])
            )
            ->registerMessageHandler(
                new DbalDocumentStoreBuilder(DocumentStoreMessageChannel::getAllDocuments($referenceName), "getAllDocuments", $dbalConfiguration->isInitializeDbalDocumentStore(), $dbalConfiguration->getDocumentStoreConnectionReference(), $dbalConfiguration->isInMemoryDocumentStore(), $inMemoryDocumentStore, [
                    HeaderBuilder::create(self::COLLECTION_NAME_PARAMETER, self::ECOTONE_DBAL_DOCUMENT_STORE_COLLECTION_NAME)
                ])
            );
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof DbalConfiguration;
    }

    public function getModuleExtensions(array $serviceExtensions): array
    {
        $dbalConfiguration = $this->getDbalConfiguration($serviceExtensions);

        if ($dbalConfiguration->isEnableDocumentStoreAggregateRepository()) {
            return [new DocumentStoreAggregateRepositoryBuilder($dbalConfiguration->getDbalDocumentStoreReference())];
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }

    private function getDbalConfiguration(array $extensionObjects): DbalConfiguration
    {
        $dbalConfiguration = DbalConfiguration::createWithDefaults();
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof DbalConfiguration) {
                $dbalConfiguration = $extensionObject;
            }
        }
        return $dbalConfiguration;
    }
}