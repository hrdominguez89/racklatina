<?php

namespace App\Controller\Secure\External\Contacto;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

#[Route('/contacto')]
final class ContactoController extends AbstractController
{
    #[Route('/enviar', name: 'app_contacto_enviar', methods: ['POST'])]
    public function enviarContacto(
        Request $request, 
        MailerInterface $mailer
    ): JsonResponse {
        try {
            
            $mensaje = $request->request->get('mensaje');
            $adjuntos = $request->files->get('adjuntos', []);
            
            if (empty(trim($mensaje))) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'El mensaje es obligatorio.'
                ], 400);
            }

            $user = $this->getUser();
            $userName = $user ? ($user->getFirstName()." ".$user->getLastName() ?? 'Usuario') : 'Usuario Desconocido';

            $email = (new Email())
                ->from($_ENV["MAIL_FROM"])
                ->to($_ENV["MAIL_FROM"]) // Cambiar por el email de soporte real
                ->subject('Nuevo mensaje de contacto desde el portal')
                ->html($this->renderView('emails/contacto.html.twig', [
                    'mensaje' => $mensaje,
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
                        'tamaño' => $this->formatBytes($archivo->getSize()),
                        'tipo' => $archivo->getMimeType()
                    ];
                }
            }
           
            // Enviar el email
            $mailer->send($email);

            return new JsonResponse([
                'success' => true,
                'message' => 'Su mensaje ha sido enviado correctamente. Nos pondremos en contacto con usted a la brevedad.',
                'data' => [
                    'adjuntos_procesados' => count($archivosAdjuntos),
                    'fecha_envio' => date('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {

            return new JsonResponse([
                'success' => false,
                'message' => 'Ha ocurrido un error al enviar su mensaje. Por favor, int�ntelo nuevamente.' . $e->getMessage(),  
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