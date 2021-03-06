<?php

namespace App\Service\Output;


use App\Service\Enums\OutputType;
use Exception;
use Symfony\Component\Yaml\Yaml;

/**
 * Class OutputYaml handles Yaml output.
 */
class OutputYaml extends Output
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
        $orderReports = json_decode(json_encode($this->orderReports), true);
        $yaml         = Yaml::dump($orderReports);
        fwrite($this->handle, $yaml);

        return $this;
    }

    /**
     * Gets output file extension.
     *
     * @return string
     */
    protected function getOutputFileExtension(): string
    {
        return OutputType::YAML->value;
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
