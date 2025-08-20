<?php

namespace App\Controller;

use App\Entity\CustomerRequest;
use App\Entity\ExternalUserData;
use App\Entity\User;
use App\Entity\UserRole;
use App\Enum\CustomerRequestStatus;
use App\Enum\CustomerRequestType;
use App\Form\RegistrationFormType;
use App\Repository\RoleRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RegisterController extends AbstractController
{
    public function __construct(private MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
    #[Route('/registro', name: 'app_register')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        RoleRepository $roleRepository,
        MailerInterface $mailer
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            $external = new ExternalUserData();
            $external->setCompanyName($form->get('companyName')->getData());
            $external->setPhoneNumber($form->get('phoneNumber')->getData());
            $external->setJobTitle($form->get('jobTitle')->getData());
            $external->setUser($user);
            $user->setExternalUserData($external);

            // Asignar rol
            $role = $roleRepository->find($form->get('role')->getData());
            $userRole = new UserRole();
            $userRole->setRole($role);
            $userRole->setUser($user);
            $user->addUserRole($userRole);

            // ✅ Generar token UUID y vencimiento a 48 horas
            $user->setAccountToken(Uuid::v4()->toRfc4122());
            $user->setAccountTokenExpiresAt(new \DateTimeImmutable('+48 hours'));

            $em->persist($user);
            $em->persist($external);
            $em->persist($userRole);
            try
            {
                $em->flush();
                $confirmationUrl = $this->generateUrl(
                'app_confirm_account',
                ['token' => $user->getAccountToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
                );
                $email = (new Email())
                    ->from($_ENV['MAIL_FROM'])
                    ->to($user->getEmail())
                    ->subject('Confirmación de cuenta - Racklatina')
                    ->html($this->renderView('emails/confirm_account.html.twig', [
                        'user' => $user,
                        'confirmationUrl' => $confirmationUrl
                    ]));

                $mailer->send($email);
                // MODIFICAR LA ENTIDAD PARA QUE DNI SEA NULEABLE TODO
                // TESTEAR EL CASO EN EL QUE EL CLIENTE NO HAYA VERIFICADO SSU CUENTA Y SE LE APRUEBE LA SOLICITUD TODO
                // TESTEAR EL EMAIL. TODO
                $clientesJson = $request->request->get('clientes');
                $clientes = json_decode($clientesJson, true);
                $user = $this->getUser();
                $representados = $clientes;
                $solicitud = $this->generarSolicitudDeRepresentacion($user,$em,$clientes);
                $this->enviarMailDeSolicitudDeRepresentacion($user,$representados,$solicitud->getId());
                return $this->redirectToRoute('app_login');
            }
            catch(UniqueConstraintViolationException $ex)
            {
                $this->addFlash('danger', 'El DNI ingresado ya se encuentra registrado.');
                return $this->redirectToRoute('app_register');
            }
            catch(Exception $ex)
            {
                $this->addFlash('danger', 'Error: '.$ex->getMessage());
                return $this->redirectToRoute('app_register');
            }
        }
        return $this->render('register/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/registro/confirmar/{token}', name: 'app_confirm_account')]
    public function confirmAccount(string $token, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['accountToken' => $token]);

        if (!$user || $user->getAccountTokenExpiresAt() < new \DateTimeImmutable()) {
            //hacer mensaje de error.
            $this->addFlash('danger', 'El enlace de confirmación ha expirado o es inválido.');
            return $this->redirectToRoute('app_login');
        }

        $user->getExternalUserData()->setVerified(true);
        $user->setAccountToken(null);
        $user->setAccountTokenExpiresAt(null);
        $em->flush();

        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($user->getEmail())
            ->subject('¡Cuenta verificada correctamente!')
            ->html($this->renderView('emails/account_verified.html.twig', [
                'user' => $user,
            ]));

        $mailer->send($email);

        $this->addFlash('success', 'Cuenta validada con éxito. Ya podés iniciar sesión.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/registro/reenviar-confirmacion', name: 'app_resend_confirmation', methods: ['GET', 'POST'])]
    public function resendConfirmation(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        if ($request->isMethod('POST')) {
            $dni = $request->request->get('dni');

            if (!$dni || !is_numeric($dni)) {
                $this->addFlash('danger', 'Debés ingresar un DNI válido.');
                return $this->redirectToRoute('app_resend_confirmation');
            }

            $user = $em->getRepository(User::class)->findOneBy(['nationalIdNumber' => $dni]);

            if (!$user || $user->isInternal()) {
                // No revelar existencia del usuario
                $this->addFlash('success', 'Se te envió un nuevo correo de confirmación.');
                return $this->redirectToRoute('app_login');
            }

            $external = $user->getExternalUserData();

            if ($external->isVerified()) {
                $this->addFlash('info', 'Tu cuenta ya está verificada. Podés iniciar sesión.');
                return $this->redirectToRoute('app_login');
            }

            // Regenerar token y expiración
            $user->setAccountToken(Uuid::v4()->toRfc4122());
            $user->setAccountTokenExpiresAt(new \DateTimeImmutable('+48 hours'));
            $em->flush();

            $confirmationUrl = $this->generateUrl(
                'app_confirm_account',
                ['token' => $user->getAccountToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $email = (new Email())
                ->from($_ENV['MAIL_FROM'])
                ->to($user->getEmail())
                ->subject('Confirmación de cuenta - Racklatina')
                ->html($this->renderView('emails/confirm_account.html.twig', [
                    'user' => $user,
                    'confirmationUrl' => $confirmationUrl
                ]));

            $mailer->send($email);

            $this->addFlash('success', 'Se te envió un nuevo correo de confirmación.');
            return $this->redirectToRoute('app_login');
        }

        // Renderizar formulario
        return $this->render('register/resend_token_validation.html.twig');
    }

    public function generarSolicitudDeRepresentacion($user,$em,$clientes)
    {
        $solicitud = new CustomerRequest();
        $solicitud->setRequestType(CustomerRequestType::REPRESENTACION);
        $solicitud->setStatus(CustomerRequestStatus::PENDIENTE);
        $solicitud->setUserRequest($this->getUser());
        $solicitud->setData($clientes);

        $em->persist($solicitud);
        $em->flush();
        return $solicitud;
    }
    public function enviarMailDeSolicitudDeRepresentacion($representante,$representados,$solicitud_id)
    {
         $template = "emails/solicitud_de_representacion.html.twig";
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($_ENV['MAIL_CENTRO_RAC'])
            ->subject('Solicitud de Representación')
            ->html($this->renderView($template, [
                'solicitud_id' => $solicitud_id,
                'representante' => $representante,
                'representados' => $representados
            ]));
        $this->mailer->send($email);
    }
}