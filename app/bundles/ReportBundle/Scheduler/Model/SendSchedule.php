<?php

namespace Mautic\ReportBundle\Scheduler\Model;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Exception\FileTooBigException;

class SendSchedule
{
    private \Mautic\EmailBundle\Helper\MailHelper $mailer;

    public function __construct(
        MailHelper $mailer,
        private MessageSchedule $messageSchedule,
        private FileHandler $fileHandler
    ) {
        $this->mailer          = $mailer->getMailer();
    }

    /**
     * @param string $filePath
     */
    public function send(Scheduler $scheduler, $csvFilePath): void
    {
        $this->mailer->reset(true);

        $transformer = new ArrayStringTransformer();
        $report      = $scheduler->getReport();
        $emails      = $transformer->reverseTransform($report->getToAddress());
        $subject     = $this->messageSchedule->getSubject($report);
        $message     = $this->messageSchedule->getMessageForAttachedFile($report);

        try {
            // Try to send the CSV file as an email attachement.
            $this->fileHandler->fileCanBeAttached($csvFilePath);
            $this->mailer->attachFile($csvFilePath, basename($csvFilePath), 'text/csv');
        } catch (FileTooBigException $e) {
            $zipFilePath = $this->fileHandler->zipIt($csvFilePath);
            try {
                // Try to send the ZIP file as an email attachement.
                $this->fileHandler->fileCanBeAttached($zipFilePath);
                $this->mailer->attachFile($zipFilePath, basename($zipFilePath), 'application/zip');
            } catch (FileTooBigException $e) {
                // Send the ZIP file as link in the email message.
                $this->fileHandler->moveZipToPermanentLocation($report, $zipFilePath);
                $message = $this->messageSchedule->getMessageForLinkedFile($report);
            }
        }

        $this->mailer->setTo($emails);
        $this->mailer->setSubject($subject);
        $this->mailer->setBody($message);
        $this->mailer->parsePlainText($message);
        $this->mailer->send(true);
    }
}
