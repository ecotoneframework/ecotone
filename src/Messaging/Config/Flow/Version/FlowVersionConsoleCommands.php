<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Flow\Version;

use Ecotone\Api\Attribute\ConsoleParameterOption;
use Ecotone\Api\Console\ConsoleWriter;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Support\InvalidArgumentException;

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
 *
 * @internal
 */
final class FlowVersionConsoleCommands
{
    public function store(ConfiguredMessagingSystem $messagingSystem, ConsoleWriter $writer, FlowVersions $versions, string $label, bool $replace = false): void
    {
        $versions->store(label: $label, graph: $messagingSystem->getMessageFlowGraph(), replace: $replace);

        $writer->success("Stored flow version {$label} in {$versions->location}/{$label}.json");
    }

    public function versions(ConsoleWriter $writer, FlowVersions $versions): void
    {
        $writer->table(
            ['Label', 'Stored at', 'Nodes', 'Edges'],
            array_map(fn (FlowVersion $version): array => [$version->label, $version->storedAt, count($version->graph->nodes()), count($version->graph->edges())], $versions->all()),
        );
    }

    public function compare(
        ConfiguredMessagingSystem $messagingSystem,
        ConsoleWriter $writer,
        FlowVersions $versions,
        #[ConsoleParameterOption] ?string $from = null,
        #[ConsoleParameterOption] ?string $to = null,
        #[ConsoleParameterOption] string $format = 'text',
        bool $failOnChange = false,
    ): void {
        if (! in_array($format, ['text', 'json', 'mermaid'], true)) {
            throw ConfigurationException::create("ecotone:flow:compare was asked for format `{$format}`, which it cannot produce. Pass --format=text for a list of the changes, --format=json for the added, removed and changed nodes and edges, or --format=mermaid for a Mermaid flowchart with the changes marked.");
        }
        $fromVersion = $from === null ? $this->latestOf($versions) : $versions->of($from);
        $toVersion = $to === null ? null : $versions->of($to);
        $comparison = $fromVersion->graph->compareTo($toVersion === null ? $messagingSystem->getMessageFlowGraph() : $toVersion->graph);
        $sides = "from version {$fromVersion->label} to " . ($toVersion === null ? 'the current code' : "version {$toVersion->label}");

        $writer->write(match ($format) {
            'text' => "Message flow {$sides}\n" . $comparison->toText(),
            'json' => json_encode(['from' => $fromVersion->label, 'to' => $toVersion?->label, ...$comparison->toArray()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
            'mermaid' => $comparison->toMermaid(),
        });

        if ($failOnChange && $comparison->hasChanges()) {
            throw InvalidArgumentException::create(sprintf(
                'The message flow changed %s: %d added, %d removed, %d changed. Accept the new flow by storing it with `ecotone:flow:store <label>` and committing the file, or run ecotone:flow:compare without --failOnChange to only report the changes.',
                $sides,
                count($comparison->addedNodes) + count($comparison->addedEdges),
                count($comparison->removedNodes) + count($comparison->removedEdges),
                count($comparison->changedNodes) + count($comparison->changedEdges),
            ));
        }
    }

    private function latestOf(FlowVersions $versions): FlowVersion
    {
        $stored = $versions->all();
        if ($stored === []) {
            throw InvalidArgumentException::create("ecotone:flow:compare has no stored flow version to compare with, because {$versions->location} holds none. Store the current flow first with `ecotone:flow:store <label>`, for example on the branch you compare against, or name a stored version with --from.");
        }

        return $stored[count($stored) - 1];
    }
}
