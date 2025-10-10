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
                $this->logger->warning('Mensaje recibido no es Email, omitiendo', [
                    'clase' => get_class($email)
                ]);
                return;
            }
            
            $this->logger->info('Procesando email desde la cola', [
                'asunto' => $email->getSubject(),
                'clase' => get_class($email),
                'adjuntos' => count($email->getAttachments())
            ]);

            // Si es un email con adjuntos especiales (ContactoEmailWithAttachments), procesarlos primero
            if ($email instanceof ContactoEmailWithAttachments) {
                $attachmentsData = $email->getAttachmentsData();
                
                if (!empty($attachmentsData)) {
                    $this->logger->info('Procesando email con adjuntos especiales desde la cola', [
                        'cantidad' => count($attachmentsData),
                        'archivos' => array_map(fn($att) => $att['filename'], $attachmentsData)
                    ]);
                    
                    // Procesar los adjuntos que vienen serializados desde la cola
                    $email->processAttachments();
                }
            }

            // Enviar usando Microsoft Graph API
            $this->graphMailer->send($email);

            $this->logger->info('Email procesado exitosamente desde la cola', [
                'asunto' => $email->getSubject()
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Error al procesar email desde la cola', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-lanzar la excepción para que Messenger pueda reintentarlo
            throw $e;
        }
    }
}
