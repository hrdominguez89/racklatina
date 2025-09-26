<?php

namespace App\Controller\Secure\External\Contacto;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use App\Email\ContactoEmailWithAttachments;
use App\Entity\User;

#[Route('secure/clientes/contacto')]
final class ContactoController extends AbstractController
{
    #[Route('/enviar', name: 'app_contacto_enviar', methods: ['POST'])]
    public function enviarContacto(
        Request $request, 
        MailerInterface $mailer
    ): JsonResponse {
        try {
            $mensaje = $request->request->get('mensaje');
            $asunto = $request->request->get('asunto');
            if (empty(trim($mensaje))) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'El mensaje es obligatorio.'
                ], 400);
            }

            $user = $this->getUser();
            $userName = 'Usuario Desconocido';
            $userEmail = null;
            
            if ($user instanceof User) {
                $userName = $user->getFirstName() . " " . $user->getLastName();
                $userEmail = $user->getEmail();
            }
            $adress = $user->getEmail();
            
            $email = (new ContactoEmailWithAttachments())
                ->from($_ENV["MAIL_FROM"])
                ->to($_ENV["MAIL_FROM"],$adress)
                ->subject($asunto .": ". $userName)
                ->html($this->renderView('emails/contacto.html.twig', [
                    'mensaje' => $mensaje,
                    'usuario' => $userName,
                    'email_usuario' => $userEmail,
                    'fecha' => new \DateTime(),
                ]));

            // Agregar replyTo si el usuario tiene email
            if ($userEmail) {
                $email->replyTo($userEmail);
            }

            // Procesar adjuntos codificándolos en base64 para el envío asíncrono
            $archivosAdjuntos = [];
            $maxFileSize = 4 * 1024 * 1024; // 4MB por archivo
            $maxTotalSize = 20 * 1024 * 1024; // 20MB total
            $maxFiles = 5;
            $allowedMimeTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/jpg',
                'image/png'
            ];

            $uploadedFiles = $request->files->all();
            $totalSize = 0;
            $fileCount = 0;

            // Buscar archivos que vienen de Uppy (pueden tener nombres como archivo_0, archivo_1, etc.)
            foreach ($uploadedFiles as $key => $archivo) {
                if ($archivo && $archivo->isValid()) {
                    $fileCount++;
                    
                    // Validar número máximo de archivos
                    if ($fileCount > $maxFiles) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Solo se permiten máximo ' . $maxFiles . ' archivos.'
                        ], 400);
                    }

                    // Validar tamaño individual
                    if ($archivo->getSize() > $maxFileSize) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'El archivo "' . $archivo->getClientOriginalName() . '" excede el tamaño máximo de 4MB.'
                        ], 400);
                    }

                    $totalSize += $archivo->getSize();

                    // Validar tamaño total
                    if ($totalSize > $maxTotalSize) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'El tamaño total de los archivos excede el límite de 20MB.'
                        ], 400);
                    }

                    // Validar tipo de archivo
                    if (!in_array($archivo->getMimeType(), $allowedMimeTypes)) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'El archivo "' . $archivo->getClientOriginalName() . '" no tiene un formato permitido. Solo se aceptan: PNG, JPEG, JPG, PDF, DOC, DOCX.'
                        ], 400);
                    }

                    // Leer el contenido del archivo y agregarlo codificado en base64
                    $fileContent = file_get_contents($archivo->getPathname());
                    if ($fileContent === false) {
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Error al leer el archivo "' . $archivo->getClientOriginalName() . '".'
                        ], 500);
                    }
                    
                    $email->addAttachmentData(
                        $fileContent,
                        $archivo->getClientOriginalName(),
                        $archivo->getMimeType()
                    );
                    
                    $archivosAdjuntos[] = [
                        'nombre' => $archivo->getClientOriginalName(),
                        'tamaño' => $this->formatBytes($archivo->getSize()),
                        'tipo' => $archivo->getMimeType()
                    ];
                }
            }

            // Enviar el email (los adjuntos se procesarán automáticamente en el listener)
            $mailer->send($email);

            return new JsonResponse([
                'success' => true,
                'message' => 'Su mensaje ha sido enviado correctamente. Nos pondremos en contacto con usted a la brevedad.',
                'data' => [
                    'adjuntos_procesados' => count($archivosAdjuntos),
                    'tamaño_total' => $this->formatBytes($totalSize),
                    'fecha_envio' => date('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ha ocurrido un error al enviar su mensaje. Por favor, inténtelo nuevamente.',  
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