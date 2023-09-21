<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Clover;

use Dannecron\CoverageMerger\Clover\Dto\Accumulator;
use Dannecron\CoverageMerger\Clover\Exceptions\HandleException;
use Dannecron\CoverageMerger\Clover\Exceptions\ParseException;

class Handler implements ElementsDictionary
{
    public function __construct(
        private readonly Parser $parser,
    ) {
    }

    /**
     * @param \SimpleXMLElement ...$documents
     * @return Accumulator
     * @throws HandleException
     */
    public function handle(\SimpleXMLElement ...$documents): Accumulator
    {
        $accumulator = new Accumulator();

        foreach ($documents as $document) {
            $accumulator = $this->handleSingleDocument($document, $accumulator);
        }

        return $accumulator;
    }

    /**
     * @param \SimpleXMLElement $document
     * @param Accumulator|null $accumulator
     * @return Accumulator
     * @throws HandleException
     */
    public function handleSingleDocument(
        \SimpleXMLElement $document,
        ?Accumulator $accumulator = null
    ): Accumulator {
        if ($accumulator === null) {
            $accumulator = new Accumulator();
        }

        $name = $document->getName();

        if ($name !== self::ELEMENT_NAME_COVERAGE) {
            throw new HandleException('Unexpected element: not coverage');
        }

        foreach ($document->children() as $project) {
            $accumulator = $this->handleProject($project, $accumulator);
        }

        return $accumulator;
    }

    /**
     * @param \SimpleXMLElement $project
     * @param Accumulator $accumulator
     * @return Accumulator
     * @throws HandleException
     */
    private function handleProject(\SimpleXMLElement $project, Accumulator $accumulator): Accumulator
    {
        $name = $project->getName();
        if ($name !== self::ELEMENT_NAME_PROJECT) {
            throw new HandleException('Unexpected element: not project');
        }

        return $this->handleItems($project->children(), $accumulator);
    }

    /**
     * @param \SimpleXMLElement $items
     * @param Accumulator $accumulator
     * @param string|null $packageName
     * @return Accumulator
     * @throws ParseException
     */
    private function handleItems(
        \SimpleXMLElement $items,
        Accumulator $accumulator,
        ?string $packageName = null,
    ): Accumulator {
        foreach ($items as $item) {
            $accumulator = $this->handleItem($item, $accumulator, $packageName);
        }

        return $accumulator;
    }

    /**
     * @param \SimpleXMLElement $item
     * @param Accumulator $accumulator
     * @param string|null $packageName
     * @return Accumulator
     * @throws ParseException
     */
    private function handleItem(
        \SimpleXMLElement $item,
        Accumulator $accumulator,
        ?string $packageName = null,
    ): Accumulator {
        $name = $item->getName();

        if ($name === self::ELEMENT_NAME_PACKAGE) {
            $attributes = $this->parser->getAttributes($item);
            $attributePackageName = $attributes['name'] ?? null;

            // Don't return here so that the package's files are still parsed regardless
            if ($attributePackageName === null) {
                return $accumulator;
            }

            return $this->handleItems($item->children(), $accumulator, (string) $attributePackageName);
        }

        if ($name === self::ELEMENT_NAME_FILE) {
            return $this->handleFile($item, $accumulator, $packageName);
        }

        return $accumulator;
    }

    /**
     * @param \SimpleXMLElement $xmlFile
     * @param Accumulator $accumulator
     * @param string|null $packageName
     * @return Accumulator
     * @throws ParseException
     */
    private function handleFile(
        \SimpleXMLElement $xmlFile,
        Accumulator $accumulator,
        ?string $packageName = null,
    ): Accumulator {
        $attributes = $this->parser->getAttributes($xmlFile);
        $fileName = $attributes['name'] ?? null;

        if ($fileName === null) {
            return $accumulator;
        }

        $file = $this->parser->parseFile($xmlFile, $packageName);

        return $accumulator->addFile((string) $fileName, $file);
    }
}
