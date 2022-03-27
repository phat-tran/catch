<?php

namespace App\Tests;


use App\Service\EmailService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

/**
 * Class EmailServiceTest consists test cases for EmailService class.
 */
class EmailServiceTest extends TestCase
{
    /**
     * Tests send email with report.
     *
     * @throws TransportExceptionInterface
     */
    public function testSendReport(): void
    {
        $mailer       = new Mailer(Transport::fromDsn($_ENV['MAILER_DSN']));
        $emailService = new EmailService($mailer);

        // Create dummy file to test email attachment.

        $dummyFileName = 'email_test' . time() . '.csv';
        $dummyFilePath = $_ENV['ORDERS_FILE_SAVED_PATH'] . '/' . $dummyFileName;
        $handle        = fopen($dummyFilePath, 'w');
        fwrite($handle, 'Test Send Report');
        fclose($handle);

        // Send the report.

        $emailService->sendReport(
            'random.email@email.com',
            ['random.email@email.com'],
            $dummyFileName,
            $dummyFilePath,
        );

        // Delete the dummy file after the test.

        unlink($dummyFilePath);
        $this->assertTrue(true);
    }
}
