<?php

namespace App\Services;

use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Microsoft\Graph\Generated\Users\Item\SendMail\SendMailPostRequestBody;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\Recipient;
use Microsoft\Graph\Generated\Models\EmailAddress;
use Microsoft\Graph\Generated\Models\ItemBody;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\Attachment;
use Microsoft\Graph\Generated\Models\FileAttachment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use GuzzleHttp\Psr7\Utils;

class MicrosoftGraphMailerService
{
    private GraphServiceClient $graphClient;
    private string $fromEmail;

    public function __construct(
        private LoggerInterface $logger,
        string $tenantId,
        string $clientId,
        string $clientSecret,
        string $fromEmail
    ) {
        $this->fromEmail = $fromEmail;
        
        try {
            // Crear el contexto de autenticación con Client Credentials
            $tokenRequestContext = new ClientCredentialContext(
                $tenantId,
                $clientId,
                $clientSecret
            );

            // Crear el cliente de Graph
            $this->graphClient = new GraphServiceClient($tokenRequestContext);
            
            $this->logger->info('Microsoft Graph client initialized successfully');
        } catch (\Throwable $e) {
            $this->logger->error('Error initializing Microsoft Graph client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Envía un email utilizando Microsoft Graph API
     */
    public function send(Email $email): void
    {
        try {
            $this->logger->info('Preparing to send email via Microsoft Graph', [
                'subject' => $email->getSubject(),
                'to' => $email->getTo() ? implode(', ', array_map(fn($addr) => $addr->getAddress(), $email->getTo())) : 'N/A',
            ]);

            // Crear el mensaje
            $message = $this->buildGraphMessage($email);
            
            // Crear el body de la petición
            $requestBody = new SendMailPostRequestBody();
            $requestBody->setMessage($message);
            $requestBody->setSaveToSentItems(true);

            // Enviar el email
            $this->graphClient
                ->users()
                ->byUserId($this->fromEmail)
                ->sendMail()
                ->post($requestBody)
                ->wait();

            $this->logger->info('Email sent successfully via Microsoft Graph', [
                'subject' => $email->getSubject()
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Error sending email via Microsoft Graph', [
                'error' => $e->getMessage(),
                'subject' => $email->getSubject(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Construye un objeto Message de Graph API desde un Email de Symfony
     */
    private function buildGraphMessage(Email $email): Message
    {
        $message = new Message();
        
        // Subject
        $message->setSubject($email->getSubject());
        
        // Body
        $body = new ItemBody();
        $htmlBody = $email->getHtmlBody();
        
        if ($htmlBody) {
            $body->setContentType(new BodyType(BodyType::HTML));
            $body->setContent($htmlBody);
        } else {
            $body->setContentType(new BodyType(BodyType::TEXT));
            $body->setContent($email->getTextBody() ?? '');
        }
        $message->setBody($body);
        
        // To Recipients
        if ($email->getTo()) {
            $toRecipients = [];
            foreach ($email->getTo() as $address) {
                $recipient = new Recipient();
                $emailAddress = new EmailAddress();
                $emailAddress->setAddress($address->getAddress());
                if ($address->getName()) {
                    $emailAddress->setName($address->getName());
                }
                $recipient->setEmailAddress($emailAddress);
                $toRecipients[] = $recipient;
            }
            $message->setToRecipients($toRecipients);
        }
        
        // CC Recipients
        if ($email->getCc()) {
            $ccRecipients = [];
            foreach ($email->getCc() as $address) {
                $recipient = new Recipient();
                $emailAddress = new EmailAddress();
                $emailAddress->setAddress($address->getAddress());
                if ($address->getName()) {
                    $emailAddress->setName($address->getName());
                }
                $recipient->setEmailAddress($emailAddress);
                $ccRecipients[] = $recipient;
            }
            $message->setCcRecipients($ccRecipients);
        }
        
        // BCC Recipients
        if ($email->getBcc()) {
            $bccRecipients = [];
            foreach ($email->getBcc() as $address) {
                $recipient = new Recipient();
                $emailAddress = new EmailAddress();
                $emailAddress->setAddress($address->getAddress());
                if ($address->getName()) {
                    $emailAddress->setName($address->getName());
                }
                $recipient->setEmailAddress($emailAddress);
                $bccRecipients[] = $recipient;
            }
            $message->setBccRecipients($bccRecipients);
        }
        
        // Reply To
        if ($email->getReplyTo()) {
            $replyToRecipients = [];
            foreach ($email->getReplyTo() as $address) {
                $recipient = new Recipient();
                $emailAddress = new EmailAddress();
                $emailAddress->setAddress($address->getAddress());
                if ($address->getName()) {
                    $emailAddress->setName($address->getName());
                }
                $recipient->setEmailAddress($emailAddress);
                $replyToRecipients[] = $recipient;
            }
            $message->setReplyTo($replyToRecipients);
        }
        
        // Attachments
        $attachments = $email->getAttachments();
        if (!empty($attachments)) {
            $graphAttachments = [];
            
            foreach ($attachments as $attachment) {
                $fileAttachment = new FileAttachment();
                $fileAttachment->setOdataType('#microsoft.graph.fileAttachment');
                $fileAttachment->setName($attachment->getFilename() ?? 'attachment');
                $fileAttachment->setContentType($attachment->getContentType());
                
                // Obtener el contenido del adjunto y convertirlo a Stream
                $content = $attachment->getBody();
                $stream = Utils::streamFor(base64_encode($content));
                $fileAttachment->setContentBytes($stream);
                
                $graphAttachments[] = $fileAttachment;
            }
            
            $message->setAttachments($graphAttachments);
            
            $this->logger->info('Added attachments to message', [
                'count' => count($graphAttachments)
            ]);
        }
        
        return $message;
    }
}
