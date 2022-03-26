<?php

namespace App\Service\Output;


use App\Service\Enums\OutputType;

/**
 * Class OutputCsv handles Csv output.
 */
class OutputCsv extends Output
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

        if ($this->outputFileEmpty())
        {
            $headers = get_object_vars($this->orderReports[0]);
            fputcsv($this->handle, array_keys($headers));
        }

        // Now build the body of csv.

        foreach ($this->orderReports as $orderReport)
        {
            fputcsv($this->handle, array_values(get_object_vars($orderReport)));
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
        return OutputType::CSV->value;
    }

    /**
     * Validates output file.
     *
     * @return bool
     */
    public function validateOutputFile(): bool
    {
        /**
         * TODO: Let's use https://csvlint.io/ API to validate our CSV file.
         * But apparently it doesn't work...
         */

        return true;
    }
}
