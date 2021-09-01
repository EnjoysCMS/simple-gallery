<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Query\Mysql\Rand;
use Enjoys\SimpleCache\CacheException;
use Enjoys\SimpleCache\Cacher\FileCache;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use Psr\SimpleCache\InvalidArgumentException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ViewPhoto extends AbstractBlock
{

    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws CacheException
     * @throws LoaderError
     * @throws InvalidArgumentException
     */
    public function view()
    {
        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);
        $em->getConfiguration()->addCustomStringFunction('RAND', Rand::class);

        /** @var Environment $twig */
        $twig = $this->container->get(Environment::class);

        $cacher = new FileCache(['path' => $_ENV['TEMP_DIR'] . '/cache/blocks']);

        if (null === $result = $cacher->get($this->block->getAlias())) {
            $result = $this->getImages($em->getRepository(Image::class));
            $cacher->set($this->block->getAlias(), $result, (int)$this->getOption('cacheTime', 0));
        }

        return $twig->render(
            (string)$this->getOption('template'),
            [
                'images' => $result,
                'options' => $this->getOptions(),
                'config' => Config::getConfig()->getAll()
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