<?php

namespace App\Command;

use App\Entity\CustomerRequest;
use App\Entity\ExternalUserData;
use App\Entity\User;
use App\Entity\UserCustomer;
use App\Entity\UserRole;
use App\Enum\CustomerRequestStatus;
use App\Enum\CustomerRequestType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Crea usuarios de prueba para cada rol del sistema',
)]
class CreateTestUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private RoleRepository $roleRepo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $password = 'Test1234!';

        $usuarios = [
            // email                              firstName    lastName     cuit          roleName              internal
            ['test.superadmin@racklatina.com.ar', 'Super',    'Admin',     99000001,  'ROLE_SUPER_ADMIN',   true],
            ['test.admin@racklatina.com.ar',       'Admin',    'Test',      99000002,  'ROLE_ADMIN',         true],
            ['test.comprador@racklatina.com.ar',   'Comprador','Test',      99000003,  'ROLE_COMPRADOR',     false],
            ['test.admin-ext@racklatina.com.ar',   'Administ', 'Test',      99000004,  'ROLE_ADMINISTRACION',false],
            ['test.ingeniero1@racklatina.com.ar',  'Ingeniero','N1 Test',   99000005,  'ROLE_INGENIERO_N1',  false],
            ['test.ingeniero2@racklatina.com.ar',  'Ingeniero','N2 Test',   99000006,  'ROLE_INGENIERO_N2',  false],
        ];

        $creados = 0;
        $omitidos = 0;

        foreach ($usuarios as [$email, $firstName, $lastName, $cuit, $roleName, $isInternal]) {
            $existente = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existente) {
                if (!$isInternal) {
                    $yaAsociado = $this->em->getRepository(UserCustomer::class)->findOneBy([
                        'user' => $existente,
                        'cliente' => '01000225',
                    ]);
                    if (!$yaAsociado) {
                        $customerRequest = new CustomerRequest();
                        $customerRequest->setRequestType(CustomerRequestType::REPRESENTACION);
                        $customerRequest->setStatus(CustomerRequestStatus::APROBADO);
                        $customerRequest->setUserRequest($existente);
                        $customerRequest->setData(['cliente' => '01000225', 'razon_social' => 'YPF S.A.']);
                        $this->em->persist($customerRequest);

                        $userCustomer = new UserCustomer();
                        $userCustomer->setUser($existente);
                        $userCustomer->setCliente('01000225');
                        $userCustomer->setCustomerRequest($customerRequest);
                        $this->em->persist($userCustomer);

                        $io->writeln("  <info>EMPRESA</info> {$email} → YPF S.A. (01000225)");
                        $creados++;
                    } else {
                        $io->writeln("  <comment>SKIP</comment> {$email} (ya tiene empresa asociada)");
                        $omitidos++;
                    }
                } else {
                    $io->writeln("  <comment>SKIP</comment> {$email} (ya existe)");
                    $omitidos++;
                }
                continue;
            }

            $role = $this->roleRepo->findOneBy(['name' => $roleName]);
            if (!$role) {
                $io->writeln("  <error>ERROR</error> Rol {$roleName} no encontrado en DB");
                continue;
            }

            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setNationalIdNumber($cuit);
            $user->setPassword($this->hasher->hashPassword($user, $password));

            if (!$isInternal) {
                $ext = new ExternalUserData();
                $ext->setUser($user);
                $ext->setPhoneNumber('1100000000');
                $ext->setJobTitle('Test');
                $ext->setCompanyName('Empresa Test');
                $ext->setVerified(true);
                $ext->setProfileCompleted(true);
                $this->em->persist($ext);

                $customerRequest = new CustomerRequest();
                $customerRequest->setRequestType(CustomerRequestType::REPRESENTACION);
                $customerRequest->setStatus(CustomerRequestStatus::APROBADO);
                $customerRequest->setUserRequest($user);
                $customerRequest->setData(['cliente' => '01000225', 'razon_social' => 'YPF S.A.']);
                $this->em->persist($customerRequest);

                $userCustomer = new UserCustomer();
                $userCustomer->setUser($user);
                $userCustomer->setCliente('01000225');
                $userCustomer->setCustomerRequest($customerRequest);
                $this->em->persist($userCustomer);
            }

            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($role);

            $this->em->persist($user);
            $this->em->persist($userRole);
            $creados++;

            $io->writeln("  <info>OK</info>  {$email} → {$roleName}");
        }

        $this->em->flush();

        $io->newLine();
        $io->success("Creados: {$creados} | Omitidos: {$omitidos}");
        $io->writeln("Password para todos: <comment>{$password}</comment>");

        return Command::SUCCESS;
    }
}
