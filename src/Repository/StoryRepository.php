<?php

namespace App\Repository;

use App\Entity\Story;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Story>
 */
class StoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Story::class);
    }

    /**
     * @param list<string> $tagSlugs
     * @return Story[]
     */
    public function findAllFilteredByTags(array $tagSlugs): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.tags', 't')
            ->addSelect('t')
            ->leftJoin('s.author', 'a')
            ->addSelect('a')
            ->distinct()
            ->orderBy('s.id', 'DESC');

        if (count($tagSlugs) > 0) {
            $qb->andWhere('t.slug IN (:slugs)')
                ->setParameter('slugs', $tagSlugs);
        }

        return $qb->getQuery()->getResult();
    }

}
