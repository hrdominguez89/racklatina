<?php
namespace App\Services;
use App\Repository\EstadoClientesRepository;
use App\Repository\UserCustomerRepository;
use App\Repository\ClientesRepository;
use App\Repository\EstadoCuentaRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class EstadoCuentaService
{
    public function __construct(
        private UserCustomerRepository $userCustomerRepository,
        private ClientesRepository $clientesRepository,
        private EstadoClientesRepository $estadoCuentaRepository,
        private RequestStack $requestStack
    ) {}

    public function verificarYNotificarEstadoCuenta(int $userId): ?string
    {
        $usuarioCliente = $this->userCustomerRepository->findOneBy(["user" => $userId]);
        
        if (!$usuarioCliente) {
            return null;
        }
        $cliente = $usuarioCliente->getCliente($this->clientesRepository);
        if (!$cliente) {
            return null;
        }
        $codEst = $cliente->getCodigoEstado();
        if($codEst=='N')
        {
            return null;
        }
        $estadoCuenta = $this->estadoCuentaRepository->findOneBy(["codigoEstado" => $codEst]);
        if (!$estadoCuenta)
        {
            return null;
        }
        $detalleEstado = $estadoCuenta->getDetalleEstado();
        if(!$detalleEstado)
        {
            return null;
        }
        $session = $this->requestStack->getSession();
        $session->getFlashBag()->add("info", $detalleEstado);
        
        return $detalleEstado;
    }
    public function verificarYNotificarEstadoCuentaPorCliente($cliente_calipso)
    {
        $cliente = $this->clientesRepository->findOneBy(["codigoCalipso" => $cliente_calipso]);
        
        if (!$cliente) {
            return null;
        }
        
        $codEst = $cliente->getCodigoEstado();
        
        if($codEst=='N')
        {
            return null;
        }
        
        $estadoCuenta = $this->estadoCuentaRepository->findOneBy(["codigoEstado" => $codEst]);
        
        if (!$estadoCuenta)
        {
            return null;
        }
        
        $detalleEstado = $estadoCuenta?->getDetalleEstado() ?? null;
        
        if(!$detalleEstado)
        {
            return null;
        }

        $session = $this->requestStack->getSession();
        $session->getFlashBag()->add("info", $detalleEstado);

        return $detalleEstado;
    }
}