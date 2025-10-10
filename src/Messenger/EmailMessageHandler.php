<?php

namespace App\Messenger;

use App\Services\MicrosoftGraphMailerService;
use App\Email\ContactoEmailWithAttachments;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class EmailMessageHandler
{
    public function __construct(
        private MicrosoftGraphMailerService $graphMailer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Maneja el envío de emails usando Microsoft Graph API
     */
    public function __invoke(SendEmailMessage $message): void
    {
        try {
            $email = $message->getMessage();
            
            // Verificar que sea un objeto Email (no RawMessage)
            if (!$email instanceof Email) {
                $this->logger->warning('Received non-Email message, skipping', [
                    'class' => get_class($email)
                ]);
                return;
            }
            
            $this->logger->info('Processing email from queue', [
                'subject' => $email->getSubject(),
                'class' => get_class($email)
            ]);

            // Si es un email con adjuntos, procesarlos primero
            if ($email instanceof ContactoEmailWithAttachments) {
                $attachmentsData = $email->getAttachmentsData();
                
                if (!empty($attachmentsData)) {
                    $this->logger->info('Processing email with attachments from queue', [
                        'count' => count($attachmentsData),
                        'files' => array_map(fn($att) => $att['filename'], $attachmentsData)
                    ]);
                    
                    // Procesar los adjuntos que vienen serializados desde la cola
                    $email->processAttachments();
                }
            }

            // Enviar usando Microsoft Graph API
            $this->graphMailer->send($email);

            $this->logger->info('Email processed successfully from queue', [
                'subject' => $email->getSubject()
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Error processing email from queue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-lanzar la excepción para que Messenger pueda reintentarlo
            throw $e;
        }
    }
}
