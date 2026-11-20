<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Flow\Version;

use DateTimeZone;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Messaging\Flow\FlowEdge;
use Ecotone\Api\Messaging\Flow\FlowNode;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use Ecotone\Messaging\Support\InvalidArgumentException;
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
 *
 * @internal
 */
final class FlowVersions
{
    public const FORMAT_VERSION = 1;

    public function __construct(public readonly string $location, private EcotoneClockInterface $clock)
    {
    }

    public function store(string $label, MessageFlowGraph $graph, bool $replace): void
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $label) !== 1) {
            throw InvalidArgumentException::create("Flow version label `{$label}` cannot be used, because the label becomes the file name of the stored version. Use letters, digits, dots, dashes and underscores only, starting with a letter or a digit, for example `1.4.0` or a commit hash.");
        }
        $file = $this->fileOf($label);
        if (! $replace && is_file($file)) {
            throw InvalidArgumentException::create("Flow version `{$label}` is already stored in {$file}, and a stored version is not replaced unless asked. Store the current flow under another label, or pass --replace to store it again under this one.");
        }
        if (! is_dir($this->location) && ! @mkdir($this->location, 0o775, true) && ! is_dir($this->location)) {
            throw InvalidArgumentException::create("Flow version `{$label}` could not be stored, because the location {$this->location} does not exist and could not be created. Create that directory, or point FlowVersionsConfiguration::createWithLocation() at a directory the application can write to.");
        }
        $content = json_encode(
            ['formatVersion' => self::FORMAT_VERSION, 'label' => $label, 'storedAt' => $this->clock->now()->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM), 'graph' => self::sorted($graph)],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        if (@file_put_contents($file, $content . "\n") === false) {
            throw InvalidArgumentException::create("Flow version `{$label}` could not be written to {$file}. Make the location {$this->location} writable for the user running this command, or point FlowVersionsConfiguration::createWithLocation() at a directory that is.");
        }
    }

    /**
     * @return list<FlowVersion>
     */
    public function all(): array
    {
        $versions = [];
        foreach (is_dir($this->location) ? scandir($this->location) : [] as $name) {
            if (str_ends_with($name, '.json') && is_file($this->location . '/' . $name)) {
                $versions[] = $this->read($this->location . '/' . $name);
            }
        }
        usort($versions, fn (FlowVersion $first, FlowVersion $second): int => [strtotime($first->storedAt), $first->label] <=> [strtotime($second->storedAt), $second->label]);

        return $versions;
    }

    public function of(string $label): FlowVersion
    {
        $versions = $this->all();
        foreach ($versions as $version) {
            if ($version->label === $label) {
                return $version;
            }
        }
        $stored = array_map(fn (FlowVersion $version): string => "`{$version->label}`", $versions);

        throw InvalidArgumentException::create("Flow version `{$label}` is not stored in {$this->location}. Stored versions: " . ($stored === [] ? 'none' : implode(', ', $stored)) . ". Pass one of them, or store the current flow first with `ecotone:flow:store {$label}`.");
    }

    private function fileOf(string $label): string
    {
        return $this->location . '/' . $label . '.json';
    }

    private function read(string $file): FlowVersion
    {
        try {
            $stored = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw InvalidArgumentException::create("Flow version file {$file} is not valid JSON, so it cannot be read as a stored flow version. Restore the file from version control, or delete it and store the flow again with `ecotone:flow:store <label>`.");
        }
        if (! is_array($stored) || ! is_int($stored['formatVersion'] ?? null) || ! is_string($stored['label'] ?? null) || ! is_string($stored['storedAt'] ?? null) || strtotime($stored['storedAt']) === false || ! is_array($stored['graph']['nodes'] ?? null) || ! is_array($stored['graph']['edges'] ?? null)) {
            throw InvalidArgumentException::create("Flow version file {$file} does not hold `formatVersion`, `label`, `storedAt` and `graph`, so it was not written by `ecotone:flow:store`. Keep only stored flow versions in {$this->location}: move the file elsewhere, or point FlowVersionsConfiguration::createWithLocation() at a directory of its own.");
        }
        $named = basename($file, '.json');
        if ($stored['label'] !== $named) {
            throw InvalidArgumentException::create("Flow version file {$file} holds the label `{$stored['label']}`, but a stored version is named after its label, so the file was copied or renamed. Rename it to {$stored['label']}.json, or delete it and store the flow with `ecotone:flow:store {$named}`.");
        }
        if ($stored['formatVersion'] > self::FORMAT_VERSION) {
            throw InvalidArgumentException::create("Flow version file {$file} has format version {$stored['formatVersion']}, but this Ecotone reads format versions up to " . self::FORMAT_VERSION . ", so a newer Ecotone stored it. Upgrade Ecotone in this application, or store the flow again with `ecotone:flow:store {$named} --replace`.");
        }
        try {
            $graph = MessageFlowGraph::fromArray($stored['graph']);
        } catch (Throwable $unreadable) {
            throw InvalidArgumentException::create("Flow version file {$file} holds a graph this Ecotone cannot read ({$unreadable->getMessage()}). Upgrade Ecotone if a newer one stored it, restore the file from version control, or store the flow again with `ecotone:flow:store {$named} --replace`.");
        }

        return new FlowVersion(label: $stored['label'], storedAt: $stored['storedAt'], graph: $graph);
    }

    /**
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}
     */
    private static function sorted(MessageFlowGraph $graph): array
    {
        $nodes = $graph->nodes();
        usort($nodes, fn (FlowNode $first, FlowNode $second): int => strcmp($first->id, $second->id));
        $edges = $graph->edges();
        usort($edges, fn (FlowEdge $first, FlowEdge $second): int => strcmp($first->identity(), $second->identity()));

        return [
            'nodes' => array_map(fn (FlowNode $node): array => self::withSortedKeys($node->toArray()), $nodes),
            'edges' => array_map(fn (FlowEdge $edge): array => self::withSortedKeys($edge->toArray()), $edges),
        ];
    }

    /**
     * @param array<string, mixed> $element
     * @return array<string, mixed>
     */
    private static function withSortedKeys(array $element): array
    {
        $element['attributes'] = self::sortedAttributes($element['attributes']);

        return $element;
    }

    private static function sortedAttributes(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $value = array_map(self::sortedAttributes(...), $value);
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }
}
