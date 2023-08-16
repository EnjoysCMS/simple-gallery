<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Entities;


use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use EnjoysCMS\Core\Pagination\Pagination;

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
            )
        ;
    }

    public function getOffsetItemsQuery(Pagination $pagination): Query
    {
        return $this->getOffsetItemsQueryBuilder($pagination)->getQuery();
    }

    public function getOffsetItems(Pagination $pagination, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        return $this->getOffsetItemsQuery($pagination)->getResult($hydrationMode);
    }
}
