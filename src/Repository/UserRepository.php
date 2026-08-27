<?php

namespace App\Repository;

use App\Entity\User;
use App\Enum\UserRoleType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Búsqueda para el selector de usuario del panel interno.
     * Busca por nombre, apellido, email o razón social del cliente Calypso.
     * Solo devuelve usuarios externos activos con UserCustomer asociado.
     *
     * @return array<int, array{id:int,first_name:string,last_name:string,email:string,cliente_codigo:string|null,cliente_nombre:string|null,roles:string}>
     */
    /**
     * Búsqueda para el selector de usuario del panel interno.
     * Busca por nombre, apellido, email o razón social del cliente Calypso.
     * Solo devuelve usuarios externos activos.
     *
     * @return array<int, array{id:int,first_name:string,last_name:string,email:string,cliente_codigo:string|null,cliente_nombre:string|null}>
     */
    public function searchExternalUsersForSelector(string $query, int $limit = 15): array
    {
        $em   = $this->getEntityManager();
        $conn = $em->getConnection();
        $q    = '%' . mb_strtolower($query) . '%';

        $clientesTable = $em->getClassMetadata(\App\Entity\Clientes::class)->getTableName();

        // Derived table para obtener el primer UserCustomer por usuario
        // (evita producto cartesiano con múltiples roles/clientes).
        $sql = "
            SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                fuc.cliente         AS cliente_codigo,
                c.Razon_Social      AS cliente_nombre
            FROM user u
            INNER JOIN user_role ur ON ur.user_id = u.id
            INNER JOIN role r       ON r.id = ur.role_id AND r.type = 'external'
            LEFT JOIN (
                SELECT uc_inner.user_id, MIN(uc_inner.id) AS first_id
                FROM user_customer uc_inner
                WHERE uc_inner.deleted_at IS NULL
                GROUP BY uc_inner.user_id
            ) AS fuc_id ON fuc_id.user_id = u.id
            LEFT JOIN user_customer fuc ON fuc.id = fuc_id.first_id
            LEFT JOIN {$clientesTable} c ON c.Codigo_Calipso = fuc.cliente COLLATE utf8mb4_general_ci
            WHERE u.deleted_at IS NULL
              AND (
                  LOWER(u.first_name)    LIKE :q
                  OR LOWER(u.last_name)  LIKE :q
                  OR LOWER(u.email)      LIKE :q
                  OR LOWER(c.Razon_Social) LIKE :q
              )
            GROUP BY u.id, u.first_name, u.last_name, u.email, fuc.cliente, c.Razon_Social
            ORDER BY u.last_name, u.first_name
            LIMIT :lim
        ";

        return $conn->executeQuery($sql, ['q' => $q, 'lim' => $limit], ['lim' => \PDO::PARAM_INT])
                    ->fetchAllAssociative();
    }

    public function findExternalUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.userRoles', 'ur')
            ->innerJoin('ur.role', 'r')
            ->andWhere('r.type = :externalType')
            ->setParameter('externalType', UserRoleType::EXTERNAL)
            ->andWhere('u.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
