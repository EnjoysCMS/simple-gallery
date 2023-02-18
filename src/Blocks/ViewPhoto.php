<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\SimpleGallery\Blocks;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Query\Mysql\Rand;
use EnjoysCMS\Core\Components\Blocks\AbstractBlock;
use EnjoysCMS\Core\Entities\Block as Entity;
use EnjoysCMS\Module\SimpleGallery\Config;
use EnjoysCMS\Module\SimpleGallery\Entities\Image;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ViewPhoto extends AbstractBlock
{
    public function __construct(private ContainerInterface $container, Entity $block)
    {
        parent::__construct($block);
    }

    public static function getBlockDefinitionFile(): string
    {
        return __DIR__ . '/../../blocks.yml';
    }

    /**

     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function view(): string
    {
        /** @var EntityManager $em */
        $em = $this->container->get(EntityManager::class);
        $em->getConfiguration()->addCustomStringFunction('RAND', Rand::class);

        /** @var Environment $twig */
        $twig = $this->container->get(Environment::class);

        $cache = new FilesystemAdapter('blocks', directory: $_ENV['TEMP_DIR'] . '/cache');

        $cacheId = md5($this->block->getAlias() . json_encode($this->getOptions()));
        $result = $cache->get($cacheId, function (ItemInterface $item) use ($em) {
            $item->expiresAfter((int)$this->getOption('cacheTime', 0));
            return $this->getImages($em->getRepository(Image::class));
        });

        return $twig->render(
            (string)$this->getOption('template'),
            [
                'images' => $result,
                'options' => $this->getOptions(),
                'config' => $this->container->get(Config::class)
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
            )->getResult()
        ;
    }


}
