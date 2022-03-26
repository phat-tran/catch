<?php


namespace App\Service\Enums;

/**
 * Enum OutputType consists of various output types.
 */
enum OutputType: string
{
    case CSV   = 'csv';
    case JSON  = 'json';
    case XML   = 'xml';
    case JSONL = 'jsonl';
    case YAML  = 'yaml';

    /**
     * Gets all output type values.
     *
     * @return string[]
     */
    public static function getValues(): array
    {
        $list = [];

        foreach (OutputType::cases() as $outputType)
        {
            $list[] = $outputType->value;
        }

        return $list;
    }
}
