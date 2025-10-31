<?php

namespace App\EventListener;

use App\Email\ContactoEmailWithAttachments;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\MessageEvent;
use Psr\Log\LoggerInterface;

#[AsEventListener(event: MessageEvent::class, method: 'onMessageSend')]
class MailerAttachmentListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function onMessageSend(MessageEvent $event): void
    {
        $email = $event->getMessage();
        
        if ($email instanceof ContactoEmailWithAttachments) {
            $attachments = $email->getAttachmentsData();
            $this->logger->info('Procesando email con adjuntos', [
                'cantidad_adjuntos' => count($attachments),
                'adjuntos' => array_map(fn($att) => $att['filename'], $attachments)
            ]);
            
            // Procesar adjuntos justo antes del envío
            $email->processAttachments();
            
            $this->logger->info('Adjuntos procesados correctamente');
        }
    }
}
