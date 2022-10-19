<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Entities;


use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use EnjoysCMS\Core\Components\Pagination\Pagination;

final class ImageRepository extends EntityRepository
{
    public function getOffsetItemsQueryBuilder(Pagination $pagination): QueryBuilder
    {
        return  $this->createQueryBuilder('i')
            ->select('i')
            ->setFirstResult($pagination->getOffset())
            ->setMaxResults(
                $pagination->getLimitItems()
            )
        ;
    }

    public function getOffsetItemsQuery(Pagination $pagination): Query
    {
        return  $this->getOffsetItemsQueryBuilder($pagination)->getQuery();
    }

    public function getOffsetItems(Pagination $pagination, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        return $this->getOffsetItemsQuery($pagination)->getResult($hydrationMode);
    }
}
