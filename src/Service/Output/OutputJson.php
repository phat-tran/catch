<?php

namespace App\Service\Output;


use App\Service\Enums\OutputType;
use Exception;

/**
 * Class OutputJson handles Json output.
 */
class OutputJson extends Output
{
    /**
     * Generates the report and return current object.
     *
     * @return static
     * @throws Exception
     */
    public function write(): static
    {
        $this->validateFileHandle();
        fwrite($this->handle, json_encode($this->orderReports));

        return $this;
    }

    /**
     * Gets output file extension.
     *
     * @return string
     */
    protected function getOutputFileExtension(): string
    {
        return OutputType::JSON->value;
    }

    /**
     * Validates output file.
     *
     * @return bool
     */
    public function validateOutputFile(): bool
    {
        // TODO: Validate output file to see it has valid format.

        return true;
    }
}
