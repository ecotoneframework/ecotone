<?php


namespace Test\Ecotone\AnnotationFinder\Unit\FileSystem;

use Ecotone\AnnotationFinder\FileSystem\AutoloadFileNamespaceParser;
use PHPUnit\Framework\TestCase;

/**
 * Class GetUsedPathsFromAutoloadTest
 * @package Test\Ecotone\AnnotationFinder\Unit\Unit\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AutoloadFileNamespaceParserTest extends TestCase
{
    public function test_retrieve_when_psr_4_namespace_is_equal_to_required()
    {
        $requiredNamepaces = ["Ecotone"];
        $autoload = [
            "Ecotone" => ["/src"]
        ];
        $autoloadPsr4 = true;
        $expectedPaths = ["/src"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieve_when_psr_0_namespace_is_equal_to_required()
    {
        $requiredNamepaces = ["Ecotone"];
        $autoload = [
            "Ecotone" => ["/src"]
        ];
        $autoloadPsr4 = false;
        $expectedPaths = ["/src/Ecotone"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieve_when_psr_4_namespace_is_longer_than_required()
    {
        $requiredNamepaces = ["Ecotone"];
        $autoload = [
            "Ecotone\Some" => ["/src"]
        ];
        $autoloadPsr4 = true;
        $expectedPaths = ["/src"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieve_when_psr_4_namespace_is_shorter_than_required()
    {
        $requiredNamepaces = ["Ecotone\Some\Domain"];
        $autoload = [
            "Ecotone" => ["/src"]
        ];
        $autoloadPsr4 = true;
        $expectedPaths = ["/src/Some/Domain"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieve_when_psr_4_namespaces_begins_with_similar_prefix()
    {
        $requiredNamepaces = ["Ecotone\Implementation"];
        $autoload = [
            "Ecotone\Test" => ["/src1"],
            "Ecotone\Implementation" => ["/src2"],
        ];
        $autoloadPsr4 = true;
        $expectedPaths = ["/src2"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieve_when_psr_4_namespaces_continues_in_path()
    {
        $requiredNamepaces = ["Ecotone\Test\Domain"];
        $autoload = [
            "Ecotone\Test" => ["/src1"]
        ];
        $autoloadPsr4 = true;
        $expectedPaths = ["/src1/Domain"];

        $this->validateExpectedPaths($requiredNamepaces, $autoload, $autoloadPsr4, $expectedPaths);
    }

    public function test_retrieving_src_catalog()
    {
        $getUsedPathsFromAutoload = new AutoloadFileNamespaceParser();

        $this->assertEquals(
            ["Ecotone\One", "Ecotone\Two"],
            $getUsedPathsFromAutoload->getNamespacesForGivenCatalog(
                [
                    "psr-4" => ["Ecotone\One" => "src"],
                    "psr-0" => ["Ecotone\Two" => "src"]
                ],
                "src"
            )
        );
    }

    public function test_retrieving_src_catalog_with_namespace_suffix()
    {
        $getUsedPathsFromAutoload = new AutoloadFileNamespaceParser();

        $this->assertEquals(
            ["Ecotone\One", "Ecotone\Two"],
            $getUsedPathsFromAutoload->getNamespacesForGivenCatalog(
                [
                    "psr-4" => ["Ecotone\\One\\" => "src/Ecotone"],
                    "psr-0" => ["Ecotone\\Two\\" => "src/Ecotone"]
                ],
                "src"
            )
        );
    }

    public function test_ignoring_when_src_has_no_namespace_defined()
    {
        $getUsedPathsFromAutoload = new AutoloadFileNamespaceParser();

        $this->assertEquals(
            [],
            $getUsedPathsFromAutoload->getNamespacesForGivenCatalog(
                [
                    "psr-4" => ["" => "src"]
                ],
                "src"
            )
        );
    }

    public function test_retrieving_when_more_than_one_target_directory()
    {
        $getUsedPathsFromAutoload = new AutoloadFileNamespaceParser();

        $this->assertEquals(
            ["Ecotone\One", "Ecotone\Two"],
            $getUsedPathsFromAutoload->getNamespacesForGivenCatalog(
                [
                    "psr-4" => ["Ecotone\One" => ["src", "tests"]],
                    "psr-0" => ["Ecotone\Two" => ["src", "tests"]]
                ],
                "src"
            )
        );
    }

    public function test_not_retrieving_when_not_in_src_catalog()
    {
        $getUsedPathsFromAutoload = new AutoloadFileNamespaceParser();

        $this->assertEquals(
            [],
            $getUsedPathsFromAutoload->getNamespacesForGivenCatalog(
                [
                    "psr-4" => ["Ecotone\One" => "tests"],
                    "psr-0" => ["Ecotone\Two" => "tests"]
                ],
                "src"
            )
        );
    }

    /**
     * @param array $requiredNamepaces
     * @param array $autoload
     * @param bool $autoloadPsr4
     * @param array $expectedPaths
     */
    private function validateExpectedPaths(array $requiredNamepaces, array $autoload, bool $autoloadPsr4, array $expectedPaths): void
    {
        $getUsedPathsFromAutoload = new AutoloadFileNamespaceParser();
        $resultsPaths = $getUsedPathsFromAutoload->getFor(
            $requiredNamepaces,
            $autoload,
            $autoloadPsr4
        );

        $this->assertEquals($expectedPaths, $resultsPaths);
    }
}