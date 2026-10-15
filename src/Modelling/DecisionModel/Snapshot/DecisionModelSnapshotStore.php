<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel\Snapshot;

use function array_key_exists;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Gateway\DocumentStore;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Store\Document\DocumentException;

use function is_string;

use JsonException;
use Psr\Container\ContainerInterface;

use function sprintf;

use Throwable;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class DecisionModelSnapshotStore
{
    public const COLLECTION_PREFIX = 'decision_model_snapshots_';

    /**
     * @param array<class-string, array{thresholdTrigger: int, documentStore: string}> $settingsByModelClass
     */
    public function __construct(
        private readonly array $settingsByModelClass,
        private readonly ContainerInterface $container,
        private readonly ConversionService $conversionService,
        private readonly LoggingGateway $logger,
    ) {
    }

    public function isEnabledFor(string $modelClass): bool
    {
        return array_key_exists($modelClass, $this->settingsByModelClass);
    }

    public function load(string $modelClass, string $scopeKey, string $foldShape): ?DecisionModelSnapshot
    {
        if (! $this->isEnabledFor($modelClass)) {
            return null;
        }

        try {
            $document = $this->documentStoreFor($modelClass)->findDocument(self::collectionFor($modelClass), $scopeKey);
        } catch (DocumentException $documentException) {
            return $this->ignore($modelClass, $scopeKey, $documentException->getMessage());
        }

        if ($document === null) {
            return null;
        }

        if (! is_string($document)) {
            return $this->ignore($modelClass, $scopeKey, 'stored document is not a snapshot envelope');
        }

        try {
            $envelope = DecisionModelSnapshotEnvelope::fromJson($document);
        } catch (JsonException $jsonException) {
            return $this->ignore($modelClass, $scopeKey, $jsonException->getMessage());
        }

        if ($envelope->foldShape !== $foldShape) {
            return $this->ignore($modelClass, $scopeKey, 'the model folds a different set of events than when the snapshot was taken');
        }

        try {
            $state = $this->conversionService->convert(
                $envelope->serializedState,
                Type::string(),
                MediaType::createApplicationJson(),
                Type::create($modelClass),
                MediaType::createApplicationXPHP(),
            );
        } catch (Throwable $conversionFailure) {
            return $this->ignore($modelClass, $scopeKey, $conversionFailure->getMessage());
        }

        if (! $state instanceof $modelClass) {
            return $this->ignore($modelClass, $scopeKey, 'the stored state did not convert back into the model class');
        }

        return new DecisionModelSnapshot($state, $envelope->coveredPosition);
    }

    public function pendingWriteFor(
        string $modelClass,
        string $scopeKey,
        string $foldShape,
        object $state,
        int $coveredPosition,
        int $previouslyCoveredPosition,
    ): ?PendingDecisionModelSnapshot {
        if (! $this->isEnabledFor($modelClass) || $coveredPosition - $previouslyCoveredPosition < $this->thresholdFor($modelClass)) {
            return null;
        }

        return new PendingDecisionModelSnapshot(
            $modelClass,
            $scopeKey,
            (new DecisionModelSnapshotEnvelope($this->serialize($modelClass, $state), $coveredPosition, $foldShape))->toJson(),
        );
    }

    public function write(PendingDecisionModelSnapshot $pending): void
    {
        $this->documentStoreFor($pending->modelClass)->upsertDocument(
            self::collectionFor($pending->modelClass),
            $pending->scopeKey,
            $pending->envelopeJson,
            DocumentStore::LAST_WRITE_WINS,
        );
    }

    private static function collectionFor(string $modelClass): string
    {
        return self::COLLECTION_PREFIX . $modelClass;
    }

    private function thresholdFor(string $modelClass): int
    {
        return $this->settingsByModelClass[$modelClass]['thresholdTrigger'];
    }

    private function serialize(string $modelClass, object $state): string
    {
        $modelType = Type::create($modelClass);

        if (! $this->conversionService->canConvert($modelType, MediaType::createApplicationXPHP(), Type::string(), MediaType::createApplicationJson())) {
            throw ConfigurationException::create(sprintf(
                'DecisionModel %s is configured for snapshots, but nothing converts it between application/x-php and application/json, so its folded state cannot be stored. '
                . 'Register a #[MediaTypeConverter] for %s, or install a serializer package such as ecotone/jms-converter.',
                $modelClass,
                $modelClass,
            ));
        }

        $serialized = $this->conversionService->convert(
            $state,
            $modelType,
            MediaType::createApplicationXPHP(),
            Type::string(),
            MediaType::createApplicationJson(),
        );

        if (! is_string($serialized)) {
            throw ConfigurationException::create(sprintf(
                'DecisionModel %s is configured for snapshots, but its application/x-php to application/json converter returned %s instead of a JSON string.',
                $modelClass,
                get_debug_type($serialized),
            ));
        }

        return $serialized;
    }

    private function documentStoreFor(string $modelClass): DocumentStore
    {
        $reference = $this->settingsByModelClass[$modelClass]['documentStore'];

        if (! $this->container->has($reference)) {
            throw ConfigurationException::create(sprintf(
                "DecisionModel %s is configured to snapshot into document store '%s', which is not registered. "
                . 'Register that document store, or pass DocumentStore::class to DynamicConsistencyBoundaryConfiguration::withSnapshotsFor() to use the default one.',
                $modelClass,
                $reference,
            ));
        }

        return $this->container->get($reference);
    }

    public function logIgnored(string $modelClass, string $scopeKey, string $reason): void
    {
        $this->ignore($modelClass, $scopeKey, $reason);
    }

    private function ignore(string $modelClass, string $scopeKey, string $reason): null
    {
        $this->logger->error(sprintf(
            'Snapshot of DecisionModel %s for %s ignored to self-heal system, folding the whole history instead. Reason: %s',
            $modelClass,
            $scopeKey,
            $reason,
        ));

        return null;
    }
}
