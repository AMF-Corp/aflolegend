<?php

// src/Repository/DeckRepository.php

namespace App\Repository;

use App\Entity\Deck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deck>
 */
class DeckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deck::class);
    }

    /**
     * Retourne tous les decks
     *
     * @return Deck[]
     */
    public function findAllWithCartes(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.cartes', 'c')
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }
}
