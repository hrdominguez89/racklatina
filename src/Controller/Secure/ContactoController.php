<?php

namespace App\Controller\Secure;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Psr\Log\LoggerInterface;

#[Route('/secure/contacto')]
final class ContactoController extends AbstractController
{
    #[Route('/enviar', name: 'app_contacto_enviar', methods: ['POST'])]
    public function enviarContacto(
        Request $request, 
        MailerInterface $mailer,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $mensaje = $request->request->get('mensaje');
            $adjuntos = $request->files->get('adjuntos', []);
            
            // Validar mensaje
            if (empty(trim($mensaje))) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'El mensaje es obligatorio.'
                ], 400);
            }

            // Obtener informaci�n del usuario actual
            $user = $this->getUser();
            $userName = $user ? ($user->getFirstName()." ".$user->getLastName() ?? 'Usuario') : 'Usuario Desconocido';

            // Crear el email
            $email = (new Email())
                ->from($_ENV["FROM"])
                ->to('soporte@racklatina.com') // Cambiar por el email de soporte real
                ->subject('Nuevo mensaje de contacto desde el portal')
                ->html($this->renderView('emails/contacto.html.twig', [
                    'mensaje' => $mensaje,
                    'usuario_nombre' => $userName,
                    'usuario_email' => $_ENV["FROM"],
                    'fecha' => new \DateTime(),
                    'tiene_adjuntos' => !empty($adjuntos)
                ]));

            // Procesar adjuntos
            $archivosAdjuntos = [];
            $maxFileSize = 10 * 1024 * 1024; // 10MB
            $allowedMimeTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'image/jpeg',
                'image/png',
                'image/gif'
            ];

            if (!empty($adjuntos)) {
                foreach ($adjuntos as $archivo) {
                    // Validar tama�o
                    if ($archivo->getSize() > $maxFileSize) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'El archivo ' . $archivo->getClientOriginalName() . ' excede el tama�o m�ximo de 10MB.'
                        ], 400);
                    }

                    // Validar tipo de archivo
                    if (!in_array($archivo->getMimeType(), $allowedMimeTypes)) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'El archivo ' . $archivo->getClientOriginalName() . ' no tiene un formato permitido.'
                        ], 400);
                    }

                    // Agregar adjunto al email
                    $email->addPart(new DataPart(new File($archivo->getPathname()), $archivo->getClientOriginalName()));
                    
                    $archivosAdjuntos[] = [
                        'nombre' => $archivo->getClientOriginalName(),
                        'tama�o' => $this->formatBytes($archivo->getSize()),
                        'tipo' => $archivo->getMimeType()
                    ];
                }
            }

            // Enviar el email
            $mailer->send($email);

            // Log del env�o
            $logger->info('Mensaje de contacto enviado', [
                'usuario' => $user->getId(),
                'adjuntos' => count($archivosAdjuntos),
                'fecha' => date('Y-m-d H:i:s')
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Su mensaje ha sido enviado correctamente. Nos pondremos en contacto con usted a la brevedad.',
                'data' => [
                    'adjuntos_procesados' => count($archivosAdjuntos),
                    'fecha_envio' => date('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            $logger->error('Error al enviar mensaje de contacto', [
                'error' => $e->getMessage(),
                'usuario' => $this->getUser() ? $this->getUser()->getUserIdentifier() : 'desconocido'
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Ha ocurrido un error al enviar su mensaje. Por favor, int�ntelo nuevamente.'
            ], 500);
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}