<?php

namespace App\Service\Output;


use App\Service\Enums\OutputType;

/**
 * Class OutputJson handles Json output.
 */
class OutputJson extends Output
{
    /**
     * Generates the report and return current object.
     *
     * @return static
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
        return true;
    }
}
