<?php

namespace App\Controller\Secure\Internal\User;

use App\Entity\ExternalUserData;
use App\Entity\User;
use App\Entity\UserCustomer;
use App\Entity\UserRole;
use App\Form\UsuarioClienteRacklatinaType;
use App\Form\UsuarioRacklatinaType;
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
        private ExternalUserDataRepository $externalUserDataRepository
        )
    {
        $this->userRepository = $userRepository;
        $this->userRoleRepository = $userRoleRepository;
        $this->roleRepository = $roleRepository;
        $this->entityManager = $entityManager;
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

        $roles_internos_ids = array_map(function($rol) {
            return $rol->getId();
        }, $roles_internos_array);

        $usuarios_empleados_array = array_map(function($rol_id) {
            return $this->userRoleRepository->findBy(['role' => $rol_id]);
        },$roles_internos_ids);
        
        $usuarios_empleados_ids = [];

        foreach($usuarios_empleados_array as $roles_grupo) {
            foreach($roles_grupo as $user_role) {
                $usuarios_empleados_ids[] = $user_role->getUser()->getId();
            }
        }

        $usuarios_empleados_ids_unicos = array_unique($usuarios_empleados_ids);
        $users = [];

        foreach($usuarios_empleados_ids_unicos as $user_id)
        {
            $users[] =$this->userRepository->find($user_id);
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

        $roles_internos_ids = array_map(function($rol) {
            return $rol->getId();
        }, $roles_internos_array);

        $usuarios_empleados_array = array_map(function($rol_id) {
            return $this->userRoleRepository->findBy(['role' => $rol_id]);
        },$roles_internos_ids);
        
        $usuarios_empleados_ids = [];
        
        foreach($usuarios_empleados_array as $roles_grupo) {
            foreach($roles_grupo as $user_role) {
                $usuarios_empleados_ids[] = $user_role->getUser()->getId();
            }
        }

        $usuarios_empleados_ids_unicos = array_unique($usuarios_empleados_ids);
        $users = [];

        foreach($usuarios_empleados_ids_unicos as $user_id)
        {
            $aux = $this->userRepository->findOneBy(["id" => $user_id]);
            // dd($aux->getEmail());
            if($aux)
            {
                $users[] = $aux;
            }
        }
        // dd($users);
        return $users;
    }
    #[Route('/modal-alta', name: 'app_usuarios_racklatina_alta')]
    public function abrirModalAltaUsuario(): Response
    {
        $roles = $this->roleRepository->findBy(["type" => "internal"]);

        $roles_aux = array_map(function($rol) {
            return [
                    "id" => $rol->getId(),
                    "nombre" => $rol->getName()
            ];
        }, $roles);
        return $this->render('secure/internal/user/_modal_alta_usuario.html.twig', ["roles" => $roles_aux]);
    }
    #[Route('/create', name: 'app_usuarios_racklatina_create', methods: ['POST'])]
    public function createUsuarioRacklatina(Request $request): Response
    {
        $json = json_decode($request->getContent(), true);
        $form = $this->createForm(UsuarioRacklatinaType::class);
        $form->submit($json);
        if ( $form->isValid())
        {
            $email = $request->request->get('email') ?? null;
            $password = $request->request->get('password');
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');
            $dni = $request->request->get('nationalIdNumber');
            $rol_id = $request->request->all ('roles')[0];
            $rol = $this->roleRepository->find(id: $rol_id);

            $usuario = new User();
    
            $usuario->setEmail($email);
            $usuario->setPassword($password);
            $usuario->setFirstName($firstName);
            $usuario->setLastName($lastName);
            $usuario->setNationalIdNumber($dni);
    
            $this->entityManager->persist($usuario);
    
            $usuario_rol = new UserRole();
    
            $usuario_rol->setUser($usuario);
            $usuario_rol->setRole($rol);
    
            $this->entityManager->persist($usuario_rol);
            $this->entityManager->flush();
    
            $this->addFlash('success', 'Usuario creado exitosamente');
        }
        else
        {
            $this->addFlash('error', 'Error al crear el usuario');
        }

        return $this->redirectToRoute('app_secure_internal_user_user_racklatina');
    }

    #[Route('/modal-alta-cliente', name: 'app_usuarios_cliente_alta')]
    public function abrirModalAltaUsuarioCliente(SectorsRepository $sectorsRepository): Response
    {
        $data["sectores"] = $sectorsRepository->findAll();
        $data["segmentos"]= [];
        $data["paises"] =[];
        $data["provincias"] = [];

        return $this->render('secure/internal/user/_modal_alta_usuario_cliente.html.twig',$data);
    }
        /**
         * Endpoint que recibe datos para el alta del usuario cliente
         *
         * Recibe por POST:
         *      - firstName: nombre del usuario
         *      - lastName: apellido del usuario
         *      - email: email del usuario
         *      - password: password del usuario
         *      - roles: array con un solo elemento, el id del rol
         *      - nationalIdNumber: dni del usuario
         *      - empresa: empresa del usuario
         *      - telefono: telefono del usuario
         *      - segmento: segmento del usuario
         *      - sector: sector del usuario
         *      - cargo: cargo del usuario
         *      - pais: pais del usuario
         *      - provincia: provincia del usuario
         *
         * Devuelve un json con el mensaje de exito o error
         * y redirige a la ruta app_secure_internal_user_user_cliente
         */
    #[Route('/createCliente', name: 'app_usuarios_cliente_create', methods: ['POST'])]
    public function createUsuarioRacklatinaCliente(Request $request): Response
    {
        $json = json_decode($request->getContent(), true);
        $form = $this->createForm(UsuarioClienteRacklatinaType::class);
        $form->submit($json);
        if ( $form->isValid())
        {
            $nombre = $request->request->get('firstName');
            $apellido = $request->request->get('lastName');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $dni = $request->request->get('nationalIdNumber');
            
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($password);
            $user->setFirstName($nombre);
            $user->setLastName($apellido);
            $user->setNationalIdNumber( $dni);
            
            $this->entityManager->persist($user);

            $externalUserData = new ExternalUserData();
            $externalUserData->setCompanyName($request->request->get('empresa'));
            $externalUserData->setPhoneNumber($request->request->get('telefono'));
            $externalUserData->setSegmento($request->request->get('segmento'));
            $externalUserData->setSector($request->request->get('sector'));
            $externalUserData->setJobTitle($request->request->get('cargo'));
            $externalUserData->setPais($request->request->get('pais'));
            $externalUserData->setProvincia($request->request->get('provincia'));
            $externalUserData->setUser($user);

            $this->entityManager->persist($externalUserData);


            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($this->roleRepository->find(id: 2));
            
            $this->entityManager->persist($userRole);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Usuario creado exitosamente');
        }
        else
        {
            $this->addFlash('error', 'Error al crear el usuario');
        }

        return $this->redirectToRoute('app_secure_internal_user_user_cliente');
    }
    #[Route('/editarCliente', name: 'app_usuarios_cliente_editar', methods: ['POST'])]
    public function editarCliente(Request $request)
    {
        $id = $request->request->get('id');
        $user = $this->userRepository->find($id) ?? null;
        
        if (!$user) {
            throw $this->createNotFoundException('Usuario no encontrado');
        }
        $externalUserData = $this->externalUserDataRepository->findOneBy(['user' => $user->getId()])??null; // Aquí deberías obtener los datos del UserCustomer si tienes esa entidad
        $sectores = $this->sectoresRepository->findAll();
        $data = [
            "user" => $user,
            "externalUserData" => $externalUserData,
            "provincias" => ["Bs As","CABA"], // Llenar con los datos reales si los tienes
            "paises" => ["Argentina","Chile"],
            "sectores" =>$sectores,
            "segmentos" => ["Consumo","Produccion"],
            "isViewMode" => true // Flag para indicar que es modo vista
        ];
        
        return $this->render('secure/internal/user/_modal_editar_usuario_cliente.html.twig', $data);
    }
    
    #[Route('/guardar', name: 'app_usuarios_cliente_editar_guardar', methods: ['POST'])]
    public function guardarEdicionCliente(Request $request)
    {
        $id = $request->request->get('id');
        $user = $this->userRepository->find($id) ?? null;
        
        if (!$user) {
            $this->addFlash('error', 'No se encontro el id del usuario');
            return $this->redirectToRoute('app_secure_internal_user_user_cliente');
        }

        $user->setEmail($request->request->get('email'));
        $user->setPassword($request->request->get('password'));
        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));
        $user->setNationalIdNumber($request->request->get('dni'));

        $externalUserData = $this->externalUserDataRepository->findOneBy(['user' => $user->getId()]);
        $externalUserData->setCompanyName($request->request->get('empresa'));
        $externalUserData->setPhoneNumber($request->request->get('telefono'));
        $externalUserData->setSegmento($request->request->get('segmento'));
        $externalUserData->setJobTitle($request->request->get('cargo'));
        $externalUserData->setPais($request->request->get('pais'));
        $externalUserData->setProvincia($request->request->get('provincia'));
        $externalUserData->setUser($user);
        
        $sector = $this->sectoresRepository->find($request->request->get('sector'));
        if($sector){
            $externalUserData->setSector($sector);
        }

        $this->entityManager->persist($externalUserData);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $this->redirectToRoute('app_secure_internal_user_user_cliente');
    }
        
    #[Route('/borrarCliente', name: 'app_usuarios_cliente_eliminar', methods: ['POST'])]
    public function borrarCliente(Request $request) 
    {
        $id = $request->request->get('id');
        $user = $this->userRepository->find($id);
        if($user){
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_secure_internal_user_user_cliente');
        }
        $this->addFlash('error', 'No se encontro el usuario a eliminar');
        return $this->redirectToRoute('app_secure_internal_user_user_cliente');
        
    }
    #[Route('/verCliente', name: 'app_usuarios_ver', methods: ['POST'])]
    public function verClienteEnDetalle(Request $request, SectorsRepository $sectorsRepository)
    {
        $id = $request->request->get('id');
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'Error al buscar el usuario');
            return $this->render('secure/internal/user/_modal_ver_usuario_cliente.html.twig', $data);
        }
        
        $externalDataUser = $this->externalUserDataRepository->findOneBy(['user' => $user->getId()]);
        $sector = $externalDataUser?->getSector()?->getName();

        $data = [
            "user" => $user,
            "externalDataUser" => $externalDataUser,
            "sector" => $sector,
            "segmentos" => [], 
            "paises" => [], 
            "provincias" => [], 
            "isViewMode" => true 
        ];
        
        return $this->render('secure/internal/user/_modal_ver_usuario_cliente.html.twig', $data);
    }
}
