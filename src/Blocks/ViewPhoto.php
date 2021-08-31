<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use Twig\Environment;
use DoctrineExtensions\Query\Mysql\Rand;

class ViewPhoto extends AbstractBlock
{

    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }

    public function view()
    {
        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);
        $em->getConfiguration()->addCustomStringFunction('RAND', Rand::class);

        /** @var Environment $twig */
        $twig = $this->container->get(Environment::class);

        return $twig->render(
            (string)$this->getOption('template'),
            [
                'images' => $this->getImages($em->getRepository(Image::class)),
                'options' => $this->getOptions()
            ]
        );
    }

    private function getImages(EntityRepository $repository)
    {
        $qb = $repository->createQueryBuilder('i');


        $qb = match ($this->getOption('orderItems')) {
            default => $qb->orderBy('i.id', 'ASC'),
            'desc' => $qb->orderBy('i.id', 'DESC'),
            'random' => $qb->orderBy('RAND()'),
        };


        return $qb->getQuery()
            ->setMaxResults(
                (int)$this->getOption('cntItems', 10)
            )->getResult();
    }


}