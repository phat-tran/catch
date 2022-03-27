<?php

namespace App\Service\Output;


use App\Service\Model\OrderReport;
use Exception;

/**
 * The abstract class for all output subclasses.
 */
abstract class Output
{
    /**
     * This flag indicates whether this output can handle write line by line to output file.
     * Default is false which means we wait for all the data then write to the output file.
     * It's up to the subclass to handle write line by line if this flag is set to true in subclass.
     *
     * @var bool
     */
    protected bool $writeEachLine = false;

    /**
     * The input data.
     *
     * @var OrderReport[]
     */
    protected array $orderReports;

    /**
     * The output file path.
     *
     * @var string
     */
    protected string $outputFilePath;

    /**
     * Output file handle.
     *
     * @var resource
     */
    protected mixed $handle;

    /**
     * Constructor.
     *
     * @param string $outputFilePath The path to save the output file.
     * @param string $outputFileName The file name (without file extension) of the output file.
     */
    public function __construct(string $outputFilePath, string $outputFileName)
    {
        $this->setOutputFilePath($outputFilePath, $outputFileName);

        // Start fresh report by removing previous file.

        $this->deleteOutputFile();
    }

    /**
     * Flag to determine if we should write output line by line (handle big file size).
     *
     * @return bool
     */
    public function isWriteEachLine(): bool
    {
        return $this->writeEachLine;
    }

    /**
     * Gets file handle.
     *
     * @return resource
     */
    public function getHandle(): mixed
    {
        return $this->handle;
    }

    /**
     * Sets file handle.
     *
     * @param resource $handle
     */
    public function setHandle(mixed $handle): void
    {
        $this->handle = $handle;
    }

    /**
     * Starts opening file for writing.
     */
    public function startWritingFile(): void
    {
        $this->handle = fopen($this->getOutputFilePath(), 'a');
        $this->validateFileHandle();
    }

    /**
     * Close file handle.
     */
    public function stopWritingFile()
    {
        fclose($this->handle);
    }

    /**
     * Sets data for the report.
     *
     * @param OrderReport[] $orderReports
     */
    public function setOrderReports(array $orderReports): void
    {
        $this->orderReports = $orderReports;
    }

    /**
     * Gets output file path.
     *
     * @return string
     */
    public function getOutputFilePath(): string
    {
        return $this->outputFilePath;
    }

    /**
     * Deletes output file.
     *
     * @return bool
     */
    public function deleteOutputFile(): bool
    {
        return $this->outputFileExisted() && unlink($this->outputFilePath);
    }

    /**
     * Checks if output file existed or not.
     *
     * @return bool
     */
    public function outputFileExisted(): bool
    {
        return file_exists($this->outputFilePath);
    }

    /**
     * Checks if output file is empty.
     *
     * @return bool
     */
    public function outputFileEmpty(): bool
    {
        clearstatcache();

        return $this->outputFileExisted() && filesize($this->outputFilePath) === 0;
    }

    /**
     * Validates output file handle opened and ready to write to.
     *
     * @return void
     * @throws Exception
     */
    public function validateFileHandle(): void
    {
        if (!$this->handle)
        {
            throw new Exception('Error opening the output file to write: ' . $this->getOutputFilePath());
        }
    }

    /**
     * Sets output file path.
     *
     * @param string $outputFilePath The path to save the output file.
     * @param string $outputFileName The file name (without file extension) of the output file.
     */
    private function setOutputFilePath(string $outputFilePath, string $outputFileName): void
    {
        $this->outputFilePath = $outputFilePath . '/' . $outputFileName . '.' . $this->getOutputFileExtension();
    }

    /**
     * Writes report to output file and return current object.
     *
     * @return static
     */
    abstract public function write(): static;

    /**
     * Gets output file extension.
     *
     * @return string
     */
    abstract protected function getOutputFileExtension(): string;

    /**
     * Validates output file.
     *
     * @return bool
     */
    abstract public function validateOutputFile(): bool;
}
