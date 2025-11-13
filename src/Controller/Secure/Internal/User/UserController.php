<?php

namespace App\Controller\Secure\Internal\User;

use App\Entity\ExternalUserData;
use App\Entity\User;
use App\Entity\UserCustomer;
use App\Entity\UserRole;
use App\Enum\UserRoleType;
use App\Form\UsuarioClienteRacklatinaType;
use App\Form\UsuarioRacklatinaType;
use App\Repository\ClientesRepository;
use App\Repository\ExternalUserDataRepository;
use App\Repository\RoleRepository;
use App\Repository\SectorsRepository;
use App\Repository\UserCustomerRepository;
use App\Repository\UserRepository;
use App\Repository\UserRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mime\Email;

#[Route('secure/user')]
final class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserRoleRepository $userRoleRepository,
        private RoleRepository $roleRepository,
        private EntityManagerInterface $entityManager,
        private UserCustomerRepository $userCustomerRepository,
        private SectorsRepository $sectoresRepository,
        private ExternalUserDataRepository $externalUserDataRepository,
        private MailerInterface $mailer
    ) {
        $this->userRepository = $userRepository;
        $this->userRoleRepository = $userRoleRepository;
        $this->roleRepository = $roleRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }
    /**La vista gral para el area de gestion de clientes */
    #[Route('/user_cliente', name: 'app_secure_internal_user_user_cliente')]
    public function userCliente(): Response
    {
        return $this->render('secure/internal/user/userCliente.html.twig');
    }
    /**La vista gral para el area de gestion de empleados */
    #[Route('/user_racklatina', name: 'app_secure_internal_user_user_racklatina')]
    public function userRacklatina(): Response
    {
        return $this->render('secure/internal/user/userRacklatina.html.twig');
    }
    /**Cada vista de gestion  de usuarios tiene un componente tabla
     * que se completa mediante esta funcion ajax y debe enviar el tipo  empleado o cliente
     */
    #[Route('/ajax', name: 'app_usuarios_ajax')]
    public function ajax(Request $request): Response
    {
        $tipo = $request->query->get('tipo', 'empleados');

        if ($tipo === 'empleados') {
            $empleados = $this->getEmpleados();
            return $this->render('secure/internal/user/tabla_empleados.html.twig', ['empleados' => $empleados]);
        }

        $clientes = $this->getClientes();
        return $this->render('secure/internal/user/tabla_clientes.html.twig', ['clientes' => $clientes]);
    }
    /**
     * Funcion auxiliar para el lsitado de los empleados
     * @return array
     */
    public function getEmpleados()
    {

        $roles_internos_array = $this->roleRepository->findBy(["type" => "internal"]);

        $roles_internos_ids = array_map(function ($rol) {
            return $rol->getId();
        }, $roles_internos_array);

        $usuarios_empleados_array = array_map(function ($rol_id) {
            return $this->userRoleRepository->findBy(['role' => $rol_id]);
        }, $roles_internos_ids);

        $usuarios_empleados_ids = [];

        foreach ($usuarios_empleados_array as $roles_grupo) {
            foreach ($roles_grupo as $user_role) {
                $usuarios_empleados_ids[] = $user_role->getUser()->getId();
            }
        }

        $usuarios_empleados_ids_unicos = array_unique($usuarios_empleados_ids);
        $users = [];

        foreach ($usuarios_empleados_ids_unicos as $user_id) {
            $aux = $this->userRepository->findOneBy(["id" => $user_id]);
            if ($aux) {
                $users[] = $aux;
            }
        }
        return $users;
    }
    /**
     * Funcion auxiliar para el lsitado de los clientes
     * @return array
     */
    public function getClientes()
    {
        $roles_internos_array = $this->roleRepository->findBy(["type" => "external"]);

        $roles_internos_ids = array_map(function ($rol) {
            return $rol->getId();
        }, $roles_internos_array);

        $usuarios_empleados_array = array_map(function ($rol_id) {
            return $this->userRoleRepository->findBy(['role' => $rol_id]);
        }, $roles_internos_ids);

        $usuarios_empleados_ids = [];

        foreach ($usuarios_empleados_array as $roles_grupo) {
            foreach ($roles_grupo as $user_role) {
                $usuarios_empleados_ids[] = $user_role->getUser()->getId();
            }
        }

        $usuarios_empleados_ids_unicos = array_unique($usuarios_empleados_ids);
        $users = [];

        foreach ($usuarios_empleados_ids_unicos as $user_id) {
            $aux = $this->userRepository->findOneBy(["id" => $user_id]);
            if ($aux) {
                $users[] = $aux;
            }
        }
        return $users;
    }

    // FUNCIONES PARA ALTAS DE USUARIOS
    #[Route('/crearUsuarios', name: 'app_usuarios_crear', methods: ['POST'])]
    public function crearUsuarios(Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $data = [];
        foreach ($request->request as $key => $value) {
            $data[$key] = $value;
        }
        $tipo_usuario = $data["tipo_usuario"];
        switch ($tipo_usuario) {
            case "empleado":
                $flag = $this->crearEmpleado($data, $passwordHasher);
                if ($flag) {
                    $this->addFlash('success', 'Se dio de alta el usuario.');
                } else {
                    $this->addFlash('danger', 'No se completo el alta de usuario empleado.');
                }
                return $this->redirectToRoute('app_secure_internal_user_user_racklatina');
            case "cliente":
                $flag = $this->crearCliente($data, $passwordHasher);
                if ($flag) {
                    $this->addFlash('success', 'Se dio de alta el usuario.');
                } else {
                    $this->addFlash('danger', 'No se completo el alta de usuario cliente.');
                }
                return $this->redirectToRoute('app_secure_internal_user_user_cliente');
        }
    }
    public function crearEmpleado($data, UserPasswordHasherInterface $passwordHasher)
    {
        $aux = $this->reutilizarUsuario($data);
        if($aux)
        {
            return true;
        }
        $form = $this->createForm(UsuarioRacklatinaType::class);
        $form->submit($data);

        if ($form->isValid()) {

            $email = $data['email'] ?? null;
            $password = $data['password'];
            $firstName = $data['firstName'];
            $lastName = $data['lastName'];
            $dni = $data['nationalIdNumber'];
            $rol_id = $data['roles'][0];
            $rol = $this->roleRepository->find(id: $rol_id);

            $usuario = new User();
            $usuario->setPassword($passwordHasher->hashPassword($usuario, $password));
            $usuario->setEmail($email);
            $usuario->setFirstName($firstName);
            $usuario->setLastName($lastName);
            $usuario->setNationalIdNumber($dni);

            $this->entityManager->persist($usuario);

            $usuario_rol = new UserRole();

            $usuario_rol->setUser($usuario);
            $usuario_rol->setRole($rol);

            $this->entityManager->persist($usuario_rol);
            $this->entityManager->flush();
            $this->enviarMailDeAlta($usuario,$password);
            return true;
        }
        return false;
    }
    public function crearCliente($data, UserPasswordHasherInterface $passwordHasher)
    {
        $aux = $this->reutilizarUsuario($data);
        if($aux)
        {
            return true;
        }
        $form = $this->createForm(UsuarioClienteRacklatinaType::class);
        $form->submit($data);
        if ($form->isValid()) {
            $nombre = $data['firstName'];
            $apellido = $data['lastName'];
            $email = $data['email'];
            $password = $data['password'];
            $dni = $data['dni'] ?? null;

            $user = new User();
            $user->setEmail($email);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setFirstName($nombre);
            $user->setLastName($apellido);
            if($dni != null && is_int($dni))
            {
                $user->setNationalIdNumber($dni);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $externalUserData = new ExternalUserData();

            $externalUserData->setCompanyName($data['empresa']);
            $externalUserData->setPhoneNumber($data['celular']);

            $sector = $this->sectoresRepository->find($data['sector']);
            $externalUserData->setSector($sector);

            $externalUserData->setSegmento($data['segmento']);
            $externalUserData->setJobTitle($data['cargo']);
            $externalUserData->setPais($data['pais']);
            $externalUserData->setProvincia($data['provincia']);
            $externalUserData->setUser($user);

            $this->entityManager->persist($externalUserData);

            $this->entityManager->flush();

            // Asignar múltiples roles si están especificados
            $roles = $data['roles'] ?? [2]; // Por defecto rol con ID 2 si no se especifican roles
            if (is_array($roles)) {
                foreach ($roles as $roleId) {
                    $role = $this->roleRepository->find($roleId);
                    if ($role) {
                        $userRole = new UserRole();
                        $userRole->setUser($user);
                        $userRole->setRole($role);
                        $this->entityManager->persist($userRole);
                    }
                }
            } else {
                // Si no es array, tratar como rol único
                $role = $this->roleRepository->find($roles);
                if ($role) {
                    $userRole = new UserRole();
                    $userRole->setUser($user);
                    $userRole->setRole($role);
                    $this->entityManager->persist($userRole);
                }
            }
            
            $this->entityManager->flush();
            $this->enviarMailDeAlta($user,$password);
            return true;
        }
        return false;
    }
    #[Route('/modal-alta-usuario', name: 'app_usuarios_modal_alta')]
    public function abrirModalAltaUsuario(Request $request, SectorsRepository $sectorsRepository): Response
    {
        if ($request->request->get('tipo_usuario') == "empleado") {
            $roles = $this->roleRepository->findBy(["type" => "internal"]);
            $roles_aux = array_map(function ($rol) {
                $aux =  str_replace("ROLE_", "", $rol->getName());
                $aux =  str_replace("_", " ", $aux);
                return [
                    "id" => $rol->getId(),
                    "nombre" => $aux
                ];
            }, $roles);
            return $this->render('secure/internal/user/_modal_alta_usuario.html.twig', ["roles" => $roles_aux]);
        } else {
            $roles = $this->roleRepository->findBy(["type" => "external"]);
            $roles_aux = array_map(function ($rol) {
                $aux =  str_replace("ROLE_", "", $rol->getName());
                $aux =  str_replace("_", " ", $aux);
                return [
                    "id" => $rol->getId(),
                    "nombre" => $aux
                ];
            }, $roles);
            
            $data["sectores"] = $sectorsRepository->findAll();
            $data["segmentos"] = [];
            $data["paises"] = [];
            $data["provincias"] = [];
            $data["roles"] = $roles_aux;

            return $this->render('secure/internal/user/_modal_alta_usuario_cliente.html.twig', $data);
        }
    }

    // FUNCIONES PARA EDICION DE USUARIOS
    #[Route('/abrir-modal-edicion-usuario', name: 'app_usuarios_editar', methods: ['POST'])]
    public function abrirModalEdicionUsuario(Request $request)
    {
        $id = $request->request->get('id');
        $user = $this->userRepository->find($id) ?? null;

        if (!$user) {
        }

        if ($request->request->get('tipo_usuario') == "empleado") {
            $data = [
                "user" => $user,
                "isViewMode" => true // Flag para indicar que es modo vista
            ];
            return $this->render('secure/internal/user/_modal_editar_usuario_empleado.html.twig', $data);
        };

        $externalUserData = $this->externalUserDataRepository->findOneBy(['user' => $user->getId()]) ?? null; // Aquí deberías obtener los datos del UserCustomer si tienes esa entidad
        $sectores = $this->sectoresRepository->findAll();
        
        // Obtener todos los roles externos disponibles
        $roles = $this->roleRepository->findBy(["type" => "external"]);
        $roles_aux = array_map(function ($rol) {
            $aux =  str_replace("ROLE_", "", $rol->getName());
            $aux =  str_replace("_", " ", $aux);
            return [
                "id" => $rol->getId(),
                "nombre" => $aux
            ];
        }, $roles);
        
        // Obtener los roles actuales del usuario
        $userRoles = $this->userRoleRepository->findBy(['user' => $user->getId()]);
        $currentRoleIds = array_map(function($userRole) {
            return $userRole->getRole()->getId();
        }, $userRoles);
        
        $data = [
            "user" => $user,
            "externalUserData" => $externalUserData,
            "mi_sector" => $externalUserData?->getSector(),
            "provincias" => ["Bs As", "CABA"], // Llenar con los datos reales si los tienes
            "paises" => ["Argentina", "Chile"],
            "sectores" => $sectores,
            "segmentos" => ["Consumo", "Produccion"],
            "roles" => $roles_aux,
            "currentRoleIds" => $currentRoleIds,
            "isViewMode" => true // Flag para indicar que es modo vista
        ];
        return $this->render('secure/internal/user/_modal_editar_usuario_cliente.html.twig', $data);
    }
    #[Route('/editar', name: 'app_usuarios_editar_guardar', methods: ['POST'])]
    public function editarUsuario(Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $id = $request->request->get('id');
        $user = $this->userRepository->find($id) ?? null;

        if ($request->request->get('tipo_usuario') == "empleado") {
            $user->setEmail($request->request->get('email'));
            if ($request->request->get('password') !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $request->request->get('password')));
            }
            $user->setFirstName($request->request->get('firstName'));
            $user->setLastName($request->request->get('lastName'));
            $dni = $request->request->get('dni') ?? null;
            if($dni)
            {
                $user->setNationalIdNumber($dni);
            }
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_secure_internal_user_user_racklatina');
        } else {
            $user->setEmail($request->request->get('email'));
            if ($request->request->get('password') !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $request->request->get('password')));
            }
            $user->setFirstName($request->request->get('firstName'));
            $user->setLastName($request->request->get('lastName'));
            $dni = $request->request->get('dni') ?? null;
            if($dni)
            {
                $user->setNationalIdNumber($dni);
            }
            $externalUserData = $this->externalUserDataRepository->findOneBy(['user' => $user->getId()]);
            $externalUserData->setCompanyName($request->request->get('empresa'));
            $externalUserData->setPhoneNumber($request->request->get('telefono'));
            $externalUserData->setSegmento($request->request->get('segmento'));
            $externalUserData->setJobTitle($request->request->get('cargo'));
            $externalUserData->setPais($request->request->get('pais'));
            $externalUserData->setProvincia($request->request->get('provincia'));
            $externalUserData->setUser($user);

            $sector = $this->sectoresRepository->find($request->request->get('sector'));
            if ($sector) {
                $externalUserData->setSector($sector);
            }

            $this->entityManager->persist($externalUserData);
            $this->entityManager->persist($user);
            
            // Actualizar roles del usuario
            // Primero eliminar todos los roles actuales del usuario
            $currentUserRoles = $this->userRoleRepository->findBy(['user' => $user->getId()]);
            foreach ($currentUserRoles as $userRole) {
                $this->entityManager->remove($userRole);
            }
            
            // Agregar los nuevos roles seleccionados
            $selectedRoles = $request->request->all('roles') ?? [];
            if (!empty($selectedRoles)) {
                foreach ($selectedRoles as $roleId) {
                    $role = $this->roleRepository->find($roleId);
                    if ($role) {
                        $userRole = new UserRole();
                        $userRole->setUser($user);
                        $userRole->setRole($role);
                        $this->entityManager->persist($userRole);
                    }
                }
            }
            
            $this->entityManager->flush();

            return $this->redirectToRoute('app_secure_internal_user_user_cliente');
        }
    }

    //FUNCIONES PARA VER USUARIOS

    #[Route("/verDetalle", name: "app_usuarios_ver_detalle", methods: ["POST"])]
    public function verDetalleUsuarios(Request $request, ClientesRepository $clientesRepository)
    {
        $id = $request->request->get('id');
        $user = $this->userRepository->find($id) ?? null;
        if (!$user) {
        }

        if ($request->request->get('tipo_usuario') == "empleado") {
            $data = [
                "user" => $user,
                "isViewMode" => true
            ];
            return $this->render('secure/internal/user/_modal_ver_usuario_empleado.html.twig', $data);
        } else {
            $externalDataUser = $this->externalUserDataRepository->findOneBy(['user' => $user->getId()]);
            $sector = $externalDataUser?->getSector()?->getName();
            $representados = $user->getUserCustomers();

            $clientesRepresentados = $representados->map(function ($userCustomer) use ($clientesRepository) {
                return $userCustomer->getCliente($clientesRepository);
            })->filter(function ($cliente) {
                return $cliente !== null;
            })->toArray();

            // Obtener los roles del usuario para mostrar en el modal
            $userRoles = $this->userRoleRepository->findBy(['user' => $user->getId()]);
            $rolesUsuario = array_map(function($userRole) {
                $roleName = $userRole->getRole()->getName();
                $aux = str_replace("ROLE_", "", $roleName);
                $aux = str_replace("_", " ", $aux);
                return ucfirst(strtolower($aux));
            }, $userRoles);

            $data = [
                "user" => $user,
                "externalDataUser" => $externalDataUser,
                "sector" => $sector,
                "segmentos" => [],
                "paises" => [],
                "provincias" => [],
                "isViewMode" => true,
                "representados" => $clientesRepresentados,
                "rolesUsuario" => $rolesUsuario,
            ];
            return $this->render('secure/internal/user/_modal_ver_usuario_cliente.html.twig', $data);
        }
    }

    //  FUNCION PARA ELIMINAR USUARIO
    #[Route('/eliminarusuario', name: 'app_usuarios_eliminar')] // faltan las rutas en el retorno 
    public function eliminarusuario(Request $request)
    {
        $id = $request->request->get('id');
        $user = $this->userRepository->find($id);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            return $this->json([
                'status' => true]);
        }
    }

    //FUNCION PARA ELIMINAR REPRESENTACION 
    #[Route('/eliminarrepresentado', name: 'app_usuarios_eliminar_representado')] // faltan las rutas en el retorno
    public function eliminarrepresentado(Request $request)
    {
        $representado = $request->request->get('representado');
        $user = $this->userRepository->find(2);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

    public function enviarMailDeAlta($user,$password)
    {
        $id = $user->getId();
        $userRoles = $this->userRoleRepository->findBy(['user' => $id]);
        $template = null;
        foreach ($userRoles as $userRole) {
            if ($userRole->getRole()->getType() === UserRoleType::INTERNAL) {
                $template = "emails/account_created_empleado.html.twig";
            }
        }
        if(!$template)
        {
            $template = "emails/account_created_cliente.html.twig";
        }
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($user->getEmail())
            ->subject('¡Se creo su cuenta en Racklatina!')
            ->html($this->renderView($template, [
                'user' => $user,
                'password' => $password,
            ]));

        $this->mailer->send($email);
    }
    public function reutilizarUsuario($data)
    {
        $this->entityManager->getFilters()->disable('softdeleteable');
        $user = $this->userRepository->findOneBy(["email" => $data["email"]]);
        if ($user) {
            $user->setDeletedAt(null);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->entityManager->getFilters()->enable('softdeleteable');
            return true;
        }
        $this->entityManager->getFilters()->enable('softdeleteable');
        return false;
    }
}
