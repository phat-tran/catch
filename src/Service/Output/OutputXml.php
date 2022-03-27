<?php

namespace App\Service\Output;


use App\Service\Enums\OutputType;
use Exception;
use SimpleXMLElement;

/**
 * Class OutputXml handles Xml output.
 */
class OutputXml extends Output
{
    const ROOT_NODE = '<OrderReports/>';

    /**
     * Generates the report and return current object.
     *
     * @return static
     * @throws Exception
     */
    public function write(): static
    {
        $this->validateFileHandle();
        $xml = new SimpleXMLElement(self::ROOT_NODE);
        self::reportToXml($this->orderReports, $xml);
        fwrite($this->handle, $xml->asXML());

        return $this;
    }

    /**
     * Converts report to xml.
     *
     * @param mixed            $rows
     * @param SimpleXMLElement $xml The SimpleXMLElement object.
     */
    private static function reportToXml(mixed $rows, SimpleXMLElement &$xml)
    {
        foreach ($rows as $key => $value)
        {
            // If it is a normal field, add to xml.

            if (!is_array($value) && !is_object($value))
            {
                $xml->addChild($key, $value);
            }

            // If it is an array, recursive this method with new elements.

            if (is_array($value))
            {
                $item = $xml->addChild($key);
                self::reportToXml($value, $item);
            }

            if (is_object($value))
            {
                $item = $xml->addChild(self::getClassShortName(get_class($value)));
                self::reportToXml($value, $item);
            }
        }
    }

    /**
     * A simple function to get class name from an object (without namespace).
     *
     * @param string $value
     *
     * @return string
     */
    private static function getClassShortName(string $value): string
    {
        return substr($value, strrpos($value, '\\') + 1, strlen($value));
    }

    /**
     * Gets output file extension.
     *
     * @return string
     */
    protected function getOutputFileExtension(): string
    {
        return OutputType::XML->value;
    }

    /**
     * Validates output file.
     *
     * @return bool
     */
    public function validateOutputFile(): bool
    {
        return true;
    }
}
