<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Uid\Uuid;


final class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserRepository $userRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_csrf_token');

            if (!$csrfTokenManager->isTokenValid(new CsrfToken('forgot_password', $submittedToken))) {
                throw $this->createAccessDeniedException('Token CSRF inválido.');
            }

            $email = $request->request->get('email');

            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user && !$user->getDeletedAt()) {
                $user->setResetPasswordToken(Uuid::v4()->toRfc4122());
                $user->setResetPasswordTokenExiresAt(new \DateTimeImmutable('+30 minutes'));

                $em->flush();


                $resetUrl = $this->generateUrl(
                    'app_change_password',
                    ['token' => $user->getResetPasswordToken()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $email = (new Email())
                    ->from($_ENV['MAIL_FROM'])
                    ->to($user->getEmail())
                    ->subject('Recuperación de contraseña - Racklatina')
                    ->html($this->renderView('emails/reset_password.html.twig', [
                        'user' => $user,
                        'resetUrl' => $resetUrl
                    ]));

                $mailer->send($email);
            }

            // Siempre mostramos éxito, exista o no el usuario
            $this->addFlash('success', 'Te enviamos un correo con las instrucciones para restablecer tu contraseña.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('forgot_password/forgot_password.html.twig');
    }

    #[Route('/change-password/{token}', name: 'app_change_password')]
    public function change(
        Request $request,
        string $token,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer
    ): Response {
        $user = $em->getRepository(User::class)->findOneBy(['resetPasswordToken' => $token]);

        if (!$user || $user->getResetPasswordTokenExiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('danger', 'El enlace para cambiar la contraseña es inválido o ha expirado. Por favor, solicita uno nuevo.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirm = $request->request->get('confirm_password');

            if (!$this->isCsrfTokenValid('change_password', $request->request->get('_csrf_token'))) {
                throw $this->createAccessDeniedException('CSRF token inválido.');
            }

            if (!$password || $password !== $confirm) {
                $this->addFlash('danger', 'Las contraseñas no coinciden.');
                return $this->redirectToRoute('app_change_password', ['token' => $token]);
            }

            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
                $this->addFlash('danger', 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.');
                return $this->redirectToRoute('app_change_password', ['token' => $token]);
            }

            // Guardar nueva contraseña
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setResetPasswordToken(null);
            $user->setResetPasswordTokenExiresAt(null);
            $em->flush();

            $mail = (new Email())
                ->from($_ENV['MAIL_FROM'])
                ->to($user->getEmail())
                ->subject('Confirmación de cambio de contraseña')
                ->html($this->renderView('emails/password_changed.html.twig', [
                    'user' => $user,
                ]));

            $mailer->send($mail);

            $this->addFlash('success', 'Contraseña actualizada correctamente. Ya podés iniciar sesión.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('forgot_password/change_password.html.twig', [
            'token' => $token,
        ]);
    }
}
