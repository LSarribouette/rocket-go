<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 *
 * @method Sortie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sortie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sortie[]    findAll()
 * @method Sortie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function save(Sortie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Sortie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findWhereRegistered(int $participant_id) {
        return $this->createQueryBuilder('sortie')
                    ->leftJoin('sortie.participantsInscrits', 'participants_inscrits')
                    ->andWhere('participants_inscrits.id LIKE :participant_id')
                    ->setParameter('participant_id', $participant_id)
                    ->getQuery()
                    ->execute();
    }
//    /**
//     * @return Sortie[] Returns an array of Sortie objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Sortie
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function findAllDateDebutOlderThanAMonth()
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.dateDebut < :olderThan')
            ->setParameter('olderThan', new \DateTime('-1 months'))
            ->getQuery()
            ->getResult()
            ;
    }
    public function findAllOlderThanCloture()
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.dateCloture < :olderThan')
            ->setParameter('olderThan', new \DateTime)
            ->getQuery()
            ->getResult()
            ;
    }

}
