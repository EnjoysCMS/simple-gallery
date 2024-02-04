<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Entities;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use EnjoysCMS\Core\Pagination\Pagination;

/**
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method list<Image> findAll()
 * @method list<Image> findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends  EntityRepository<Image>
 */
final class ImageRepository extends EntityRepository
{
    public function getOffsetItemsQueryBuilder(
        Pagination $pagination,
        string $orderField = 'id',
        string $orderDirection = 'asc'
    ): QueryBuilder {
        return $this->createQueryBuilder('i')
            ->select('i')
            ->orderBy(sprintf('i.%s', $orderField), $orderDirection)
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults(
                $pagination->getLimitItems()
            );
    }

    public function getOffsetItemsQuery(Pagination $pagination): Query
    {
        return $this->getOffsetItemsQueryBuilder($pagination)->getQuery();
    }


    /**
     * @return Image[]
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    public function getOffsetItems(
        Pagination $pagination,
        int|string $hydrationMode = AbstractQuery::HYDRATE_OBJECT
    ): array {
        return $this->getOffsetItemsQuery($pagination)->getResult($hydrationMode);
    }
}
