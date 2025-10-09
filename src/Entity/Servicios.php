<?php

namespace App\Entity;

use App\Repository\ServiciosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'Servicios')]
#[ORM\Entity(repositoryClass: ServiciosRepository::class,readOnly: true)]
class Servicios
{
    #[ORM\Id]
    #[ORM\Column(name: "serviceID", nullable: true, options: ["default" => NULL])]
    private ?int $serviceid = NULL;

    #[ORM\Column(name: "serviceDate", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $servicedate = null;

    #[ORM\Column(name: "serviceEmpresa", length: 100, nullable: true)]
    private ?string $serviceempresa = 'NULL';

    #[ORM\Column(name: "serviceCUIT", length: 15, nullable: true)]
    private ?string $servicecuit = 'NULL';

    #[ORM\Column(name: "serviceContacto", length: 200, nullable: true)]
    private ?string $servicecontacto = 'NULL';

    #[ORM\Column(name: "serviceEmail", length: 50, nullable: true)]
    private ?string $serviceemail = 'NULL';

    #[ORM\Column(name: "serviceDireccion", length: 250, nullable: true)]
    private ?string $servicedireccion = 'NULL';

    #[ORM\Column(name: "serviceCodPostal", length: 15, nullable: true)]
    private ?string $servicecodpostal = 'NULL';

    #[ORM\Column(name: "serviceTelefono", length: 15, nullable: true)]
    private ?string $servicetelefono = 'NULL';

    #[ORM\Column(name: "serviceTransporte", length: 15, nullable: true)]
    private ?string $servicetransporte = 'NULL';

    #[ORM\Column(name: "serviceTransporteNombre", length: 150, nullable: true)]
    private ?string $servicetransportenombre = 'NULL';

    #[ORM\Column(name: "serviceTransporteDireccion", length: 250, nullable: true)]
    private ?string $servicetransportedireccion = 'NULL';

    #[ORM\Column(name: "servicepaisID", nullable: true, options: ["default" => NULL])]
    private ?int $servicepaisid = NULL;

    #[ORM\Column(name: "serviceProvinciaID", nullable: true, options: ["default" => NULL])]
    private ?int $serviceprovinciaid = NULL;

    #[ORM\Column(name: "serviceLocalidad", length: 200, nullable: true)]
    private ?string $servicelocalidad = 'NULL';

    #[ORM\Column(name: "serviceMarcaID", nullable: true, options: ["default" => NULL])]
    private ?int $servicemarcaid = NULL;

    #[ORM\Column(name: "serviceCodCatalogo", length: 25, nullable: true)]
    private ?string $servicecodcatalogo = 'NULL';

    #[ORM\Column(name: "serviceSerie", length: 15, nullable: true)]
    private ?string $serviceserie = 'NULL';

    #[ORM\Column(name: "serviceNroSerie", length: 25, nullable: true)]
    private ?string $servicenroserie = 'NULL';

    #[ORM\Column(name: "serviceFalla", length: 500, nullable: true)]
    private ?string $servicefalla = 'NULL';

    #[ORM\Column(name: "serviceMeses", length: 2, nullable: true)]
    private ?string $servicemeses = 'NULL';

    #[ORM\Column(name: "serviceTypeID", nullable: true, options: ["default" => NULL])]
    private ?int $servicetypeid = NULL;

    #[ORM\Column(name: "serviceNroSeguimiento", length: 25, nullable: true)]
    private ?string $servicenroseguimiento = 'NULL';

    #[ORM\Column(name: "serviceNroTicket", length: 25, nullable: true)]
    private ?string $servicenroticket = 'NULL';

    #[ORM\Column(name: "serviceNroRMA", length: 25, nullable: true)]
    private ?string $servicenrorma = 'NULL';

    #[ORM\Column(name: "serviceSucursalID", nullable: true, options: ["default" => NULL])]
    private ?int $servicesucursalid = NULL;

    #[ORM\Column(name: "serviceAnalistaID", nullable: true, options: ["default" => NULL])]
    private ?int $serviceanalistaid = NULL;

    #[ORM\Column(name: "serviceVendedorID", nullable: true, options: ["default" => NULL])]
    private ?int $servicevendedorid = NULL;

    #[ORM\Column(name: "serviceObservaciones", length: 500, nullable: true)]
    private ?string $serviceobservaciones = 'NULL';

    #[ORM\Column(name: "serviceStatus", nullable: true, options: ["default" => NULL])]
    private ?int $servicestatus = NULL;

    public function getServiceid(): ?int
    {
        return $this->serviceid;
    }

    public function setServiceid(?int $serviceid): static
    {
        $this->serviceid = $serviceid;

        return $this;
    }

    public function getServicedate(): ?\DateTimeInterface
    {
        return $this->servicedate;
    }

    public function setServicedate(?\DateTimeInterface $servicedate): static
    {
        $this->servicedate = $servicedate;

        return $this;
    }

    public function getServiceempresa(): ?string
    {
        return $this->serviceempresa;
    }

    public function setServiceempresa(?string $serviceempresa): static
    {
        $this->serviceempresa = $serviceempresa;

        return $this;
    }

    public function getServicecuit(): ?string
    {
        return $this->servicecuit;
    }

    public function setServicecuit(?string $servicecuit): static
    {
        $this->servicecuit = $servicecuit;

        return $this;
    }

    public function getServicecontacto(): ?string
    {
        return $this->servicecontacto;
    }

    public function setServicecontacto(?string $servicecontacto): static
    {
        $this->servicecontacto = $servicecontacto;

        return $this;
    }

    public function getServiceemail(): ?string
    {
        return $this->serviceemail;
    }

    public function setServiceemail(?string $serviceemail): static
    {
        $this->serviceemail = $serviceemail;

        return $this;
    }

    public function getServicedireccion(): ?string
    {
        return $this->servicedireccion;
    }

    public function setServicedireccion(?string $servicedireccion): static
    {
        $this->servicedireccion = $servicedireccion;

        return $this;
    }

    public function getServicecodpostal(): ?string
    {
        return $this->servicecodpostal;
    }

    public function setServicecodpostal(?string $servicecodpostal): static
    {
        $this->servicecodpostal = $servicecodpostal;

        return $this;
    }

    public function getServicetelefono(): ?string
    {
        return $this->servicetelefono;
    }

    public function setServicetelefono(?string $servicetelefono): static
    {
        $this->servicetelefono = $servicetelefono;

        return $this;
    }

    public function getServicetransporte(): ?string
    {
        return $this->servicetransporte;
    }

    public function setServicetransporte(?string $servicetransporte): static
    {
        $this->servicetransporte = $servicetransporte;

        return $this;
    }

    public function getServicetransportenombre(): ?string
    {
        return $this->servicetransportenombre;
    }

    public function setServicetransportenombre(?string $servicetransportenombre): static
    {
        $this->servicetransportenombre = $servicetransportenombre;

        return $this;
    }

    public function getServicetransportedireccion(): ?string
    {
        return $this->servicetransportedireccion;
    }

    public function setServicetransportedireccion(?string $servicetransportedireccion): static
    {
        $this->servicetransportedireccion = $servicetransportedireccion;

        return $this;
    }

    public function getServicepaisid(): ?int
    {
        return $this->servicepaisid;
    }

    public function setServicepaisid(?int $servicepaisid): static
    {
        $this->servicepaisid = $servicepaisid;

        return $this;
    }

    public function getServiceprovinciaid(): ?int
    {
        return $this->serviceprovinciaid;
    }

    public function setServiceprovinciaid(?int $serviceprovinciaid): static
    {
        $this->serviceprovinciaid = $serviceprovinciaid;

        return $this;
    }

    public function getServicelocalidad(): ?string
    {
        return $this->servicelocalidad;
    }

    public function setServicelocalidad(?string $servicelocalidad): static
    {
        $this->servicelocalidad = $servicelocalidad;

        return $this;
    }

    public function getServicemarcaid(): ?int
    {
        return $this->servicemarcaid;
    }

    public function setServicemarcaid(?int $servicemarcaid): static
    {
        $this->servicemarcaid = $servicemarcaid;

        return $this;
    }

    public function getServicecodcatalogo(): ?string
    {
        return $this->servicecodcatalogo;
    }

    public function setServicecodcatalogo(?string $servicecodcatalogo): static
    {
        $this->servicecodcatalogo = $servicecodcatalogo;

        return $this;
    }

    public function getServiceserie(): ?string
    {
        return $this->serviceserie;
    }

    public function setServiceserie(?string $serviceserie): static
    {
        $this->serviceserie = $serviceserie;

        return $this;
    }

    public function getServicenroserie(): ?string
    {
        return $this->servicenroserie;
    }

    public function setServicenroserie(?string $servicenroserie): static
    {
        $this->servicenroserie = $servicenroserie;

        return $this;
    }

    public function getServicefalla(): ?string
    {
        return $this->servicefalla;
    }

    public function setServicefalla(?string $servicefalla): static
    {
        $this->servicefalla = $servicefalla;

        return $this;
    }

    public function getServicemeses(): ?string
    {
        return $this->servicemeses;
    }

    public function setServicemeses(?string $servicemeses): static
    {
        $this->servicemeses = $servicemeses;

        return $this;
    }

    public function getServicetypeid(): ?int
    {
        return $this->servicetypeid;
    }

    public function setServicetypeid(?int $servicetypeid): static
    {
        $this->servicetypeid = $servicetypeid;

        return $this;
    }

    public function getServicenroseguimiento(): ?string
    {
        return $this->servicenroseguimiento;
    }

    public function setServicenroseguimiento(?string $servicenroseguimiento): static
    {
        $this->servicenroseguimiento = $servicenroseguimiento;

        return $this;
    }

    public function getServicenroticket(): ?string
    {
        return $this->servicenroticket;
    }

    public function setServicenroticket(?string $servicenroticket): static
    {
        $this->servicenroticket = $servicenroticket;

        return $this;
    }

    public function getServicenrorma(): ?string
    {
        return $this->servicenrorma;
    }

    public function setServicenrorma(?string $servicenrorma): static
    {
        $this->servicenrorma = $servicenrorma;

        return $this;
    }

    public function getServicesucursalid(): ?int
    {
        return $this->servicesucursalid;
    }

    public function setServicesucursalid(?int $servicesucursalid): static
    {
        $this->servicesucursalid = $servicesucursalid;

        return $this;
    }

    public function getServiceanalistaid(): ?int
    {
        return $this->serviceanalistaid;
    }

    public function setServiceanalistaid(?int $serviceanalistaid): static
    {
        $this->serviceanalistaid = $serviceanalistaid;

        return $this;
    }

    public function getServicevendedorid(): ?int
    {
        return $this->servicevendedorid;
    }

    public function setServicevendedorid(?int $servicevendedorid): static
    {
        $this->servicevendedorid = $servicevendedorid;

        return $this;
    }

    public function getServiceobservaciones(): ?string
    {
        return $this->serviceobservaciones;
    }

    public function setServiceobservaciones(?string $serviceobservaciones): static
    {
        $this->serviceobservaciones = $serviceobservaciones;

        return $this;
    }

    public function getServicestatus(): ?int
    {
        return $this->servicestatus;
    }

    public function setServicestatus(?int $servicestatus): static
    {
        $this->servicestatus = $servicestatus;

        return $this;
    }
}
