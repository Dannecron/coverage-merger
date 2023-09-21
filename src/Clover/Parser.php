<?php

declare(strict_types=1);

namespace Dannecron\CoverageMerger\Clover;

use Dannecron\CoverageMerger\Clover\Dto\ClassDto;
use Dannecron\CoverageMerger\Clover\Dto\FileDto;
use Dannecron\CoverageMerger\Clover\Dto\LineDto;
use Dannecron\CoverageMerger\Clover\Exceptions\ParseException;

class Parser implements ElementsDictionary
{
    /**
     * @param \SimpleXMLElement $xml
     * @param string|null $packageName
     * @return FileDto
     * @throws ParseException
     */
    public function parseFile(\SimpleXMLElement $xml, ?string $packageName = null): FileDto
    {
        $file = new FileDto($packageName);

        foreach ($xml->children() as $child) {
            $file = $this->parseChildXml($child, $file);
        }

        return $file;
    }

    public function parseClass(\SimpleXMLElement $xml): ClassDto
    {
        $properties = $this->getAttributes($xml);
        $properties = \array_map(static fn (mixed $val): string => (string) $val, $properties);

        return new ClassDto($properties);
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return LineDto
     * @throws ParseException
     */
    public function parseLine(\SimpleXMLElement $xml): LineDto
    {
        $properties = $this->getAttributes($xml);
        $properties = \array_map(static fn (mixed $val): string => (string) $val, $properties);

        $count = $properties['count'] ?? null;

        if ($count === null) {
            throw new ParseException('Unable to parse line, missing count attribute');
        }

        unset($properties['count']);

        return new LineDto($properties, (int) $count);
    }

    public function getAttributes(\SimpleXMLElement $xml): array
    {
        return ((array) $xml->attributes())['@attributes'] ?? [];
    }

    /**
     * @param \SimpleXMLElement $childXml
     * @param FileDto $file
     * @return FileDto
     * @throws ParseException
     */
    private function parseChildXml(\SimpleXMLElement $childXml, FileDto $file): FileDto
    {
        $name = $childXml->getName();
        $attributes = $this->getAttributes($childXml);

        if ($name === self::ELEMENT_NAME_CLASS) {
            $name = $attributes['name'] ?? '';
            $file->mergeClass((string) $name, $this->parseClass($childXml));

            return $file;
        }

        if ($name === self::ELEMENT_NAME_LINE) {
            $file->mergeLine((int) $attributes['num'], $this->parseLine($childXml));
        }

        return $file;
    }
}
