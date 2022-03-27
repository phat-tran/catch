<?php

namespace App\Command;


use App\Service\EmailService;
use App\Service\Enums\OutputType;
use App\Service\Output\Output;
use App\Service\ReportOrderService;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\RequestOrderService;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Throwable;

class ProcessOrderCommand extends Command
{
    const OPTION_OUTPUT_FILE_NAME          = 'filename';
    const OPTION_OUTPUT_TYPE               = 'type';
    const OPTION_RECIPIENT_EMAIL_ADDRESSES = 'email-to';
    const OPTION_GEOLOCATION               = 'geolocation';

    protected static $defaultName = 'app:process-orders';
    protected static $defaultDescription = 'Process orders from JSON file.';

    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_OUTPUT_FILE_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'Output file name. Only alphanumeric allowed.',
                $_ENV['ORDERS_OUTPUT_FILE_NAME'],
            )
            ->addOption(
                self::OPTION_OUTPUT_TYPE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Which output format do you like for the report? Allow ' .
                implode(', ', OutputType::getValues()),
                OutputType::CSV->value,
            )
            ->addOption(
                self::OPTION_RECIPIENT_EMAIL_ADDRESSES,
                null,
                InputOption::VALUE_OPTIONAL,
                'Recipient\'s email addresses to receive the report. Separated by comma.
                    E,g., person1@catch.com.au,person2@catch2.com.au',
            )
            ->addOption(
                self::OPTION_GEOLOCATION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Flag to fetch geolocation details. 1 means yes, 0 means no.',
                0,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try
        {
            $output->writeln([
                'Processing Orders...',
                '============',
                '',
            ]);

            if (!$output instanceof ConsoleOutputInterface)
            {
                throw new LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
            }

            // Validate input options (if provided).

            $outputFileName = $input->getOption(self::OPTION_OUTPUT_FILE_NAME);
            self::validateFileName($outputFileName);
            $outputType = $input->getOption(self::OPTION_OUTPUT_TYPE);
            $outputType = strtolower($outputType);
            self::validateOutputType($outputType);
            $emailAddressesOption = $input->getOption(self::OPTION_RECIPIENT_EMAIL_ADDRESSES);
            $emailAddresses       = [];

            if ($emailAddressesOption !== null)
            {
                self::validateEmailAddresses($emailAddressesOption);
                $emailAddresses = explode(',', $emailAddressesOption);
            }

            $getGeolocation = $input->getOption(self::OPTION_GEOLOCATION);
            self::validateGeolocation($getGeolocation);

            // Create request service for downloading the Jsonl file.

            $requestService = new RequestOrderService(
                $_ENV['ORDERS_JSON_FILE_URL'],
                $_ENV['ORDERS_FILE_SAVED_PATH'],
            );

            // To match with a list of Output subclasses we need to use ucfirst on output type value. E.g. csv -> Csv.

            $outputType       = OutputType::from($outputType);
            $outputEngineName = Output::class . ucfirst($outputType->value);

            // Create output engine to generate the output of the report.

            $outputEngine = new $outputEngineName(
                $_ENV['ORDERS_FILE_SAVED_PATH'],
                $outputFileName,
            );

            // Download the JSON file.

            $requestService->downloadOrders();

            // Create report service. This service will handle report generation.

            $reportService = new ReportOrderService(
                $requestService->getFilePath(),
                $outputEngine,
                (bool)$getGeolocation,
            );

            /**
             * Build report.
             * Read input file line by line.
             * Use Php generator and current line pointer to calculate percentage progressing.
             */

            foreach ($reportService->generateOrderReports() as &$percentage)
            {
                $output->writeln($percentage . '%');
            }

            if (!$reportService->getOutput()->outputFileExisted())
            {
                throw new Exception('Unexpected error. Output report file was not created.');
            }

            // Report is ready, show the file path.

            $output->writeln('Report is saved to ' . $reportService->getOutput()->getOutputFilePath());

            // Let's validate the output file. Is it valid or not?

            $reportService->getOutput()->validateOutputFile();

            // Send report by email (if email addresses provided).

            if (!empty($emailAddresses))
            {
                self::sendEmailWithReport($reportService->getOutput()->getOutputFilePath(), $emailAddresses);
                $output->writeln('Email sent!');
            }

            return Command::SUCCESS;
        }
        catch (Throwable $e)
        {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Sends email with the report as attachment.
     *
     * @param string $outputFilePath The output file path.
     * @param array  $emailAddresses The recipient's email addresses.
     *
     * @throws TransportExceptionInterface
     */
    private static function sendEmailWithReport(string $outputFilePath, array $emailAddresses): void
    {
        $emailService = new EmailService(new Mailer(Transport::fromDsn($_ENV['MAILER_DSN'])));
        $emailService->sendReport(
            $_ENV['MAILER_FROM'],
            $emailAddresses,
            basename($outputFilePath),
            $outputFilePath,
        );
    }

    /**
     * Validates file name.
     *
     * @param string|null $fileName The output file name.
     *
     * @return void
     * @throws Exception
     */
    private static function validateFileName(string|null $fileName): void
    {
        if ($fileName === null || $fileName === '' || !ctype_alnum($fileName))
        {
            throw new Exception('File name must only include alphanumeric. No space or special symbols allowed');
        }
    }

    /**
     * Validates output type.
     *
     * @param string $outputType The output type.
     *
     * @return void
     * @throws Exception
     */
    private static function validateOutputType(string $outputType): void
    {
        $outputType = OutputType::tryFrom($outputType);

        if ($outputType === null)
        {
            throw new Exception('Invalid output type. Try one of these: ' .
                implode(', ', OutputType::getValues()));
        }
    }

    /**
     * Validates recipient's email addresses.
     *
     * @param string $emailAddresses The email addresses separated by comma.
     *
     * @return void
     * @throws Exception
     */
    private static function validateEmailAddresses(string $emailAddresses): void
    {
        $emailAddresses = explode(',', $emailAddresses);

        foreach ($emailAddresses as $emailAddress)
        {
            if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL))
            {
                throw new Exception('One of the email provided is invalid.');
            }
        }
    }

    /**
     * Validates get geolocation.
     *
     * @param mixed $value
     *
     * @return void
     * @throws Exception
     */
    private static function validateGeolocation(mixed $value): void
    {
        if (!in_array($value, [
            0,
            1,
        ]))
        {
            throw new Exception('Geolocation should only be 1 or 0.');
        }
    }
}
