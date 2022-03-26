<?php

namespace App\Service;


use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


/**
 * Class EmailService handles email operations.
 */
class EmailService
{
    /**
     * The mailer object.
     *
     * @var MailerInterface
     */
    protected MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Sends email with report attachment.
     *
     * @param string   $from           Sender email address.
     * @param string[] $to             Recipient's email addresses.
     * @param string   $subject        The email subject.
     * @param string   $reportFilePath The report file path to be attached.
     *
     * @throws TransportExceptionInterface
     */
    public function sendReport(string $from, array $to, string $subject, string $reportFilePath)
    {
        $email = (new Email())
            ->from($from)
            ->to(...$to)
            ->subject($subject)
            ->attachFromPath($reportFilePath);

        $this->mailer->send($email);
    }
}
