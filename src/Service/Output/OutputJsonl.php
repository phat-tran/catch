<?php

namespace App\Service\Output;


use App\Service\Enums\OutputType;

/**
 * Class OutputJsonl handles Jsonl output.
 */
class OutputJsonl extends Output
{
    /**
     * This flag indicates whether this output can handle write line by line to output file.
     *
     * @var bool
     */
    protected bool $writeEachLine = true;

    /**
     * Generates the report and return current object.
     *
     * @return static
     */
    public function write(): static
    {
        $this->validateFileHandle();

        foreach ($this->orderReports as $orderReport)
        {
            fwrite($this->handle, json_encode($orderReport) . "\n");
        }

        return $this;
    }

    /**
     * Gets output file extension.
     *
     * @return string
     */
    protected function getOutputFileExtension(): string
    {
        return OutputType::JSONL->value;
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
