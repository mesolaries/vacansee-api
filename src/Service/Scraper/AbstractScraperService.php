<?php

namespace App\Service\Scraper;

use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractScraperService implements ScraperServiceInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function spot(string $url, int $timestamp): array;

    /**
     * {@inheritdoc}
     */
    abstract public function scrape(array $urls, string $category): array;

    /**
     * @param array  $urls
     * @param string $entityClassName
     * @param string $filterBy
     *
     * @return array
     */
    public function filter(array $urls, string $entityClassName, string $filterBy = 'url')
    {
        return array_values(array_filter($urls, function ($v) use ($entityClassName, $filterBy) {
            return !$this->em->getRepository($entityClassName)->findOneBy([$filterBy => $v]);
        }));
    }

    public function flush(array $entities)
    {
        foreach ($entities as $entity) {
            $this->em->persist($entity);
        }

        $this->em->flush();
    }
}
