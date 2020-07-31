<?php

namespace App\Service\Scraper;

use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractScraperService implements ScraperServiceInterface
{
    private EntityManagerInterface $em;

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
     * Compares URLs against a database and returns filtered URLs list.
     *
     * @param array  $urls            URLs list to filter
     * @param string $entityClassName Entity class name to use for comparison
     * @param string $filterBy        Entity property to use for comparison
     *
     * @return array Filtered URLs
     */
    public function filter(array $urls, string $entityClassName, string $filterBy = 'url')
    {
        return array_values(array_filter($urls, function ($v) use ($entityClassName, $filterBy) {
            return !$this->em->getRepository($entityClassName)->findOneBy([$filterBy => $v]);
        }));
    }

    /**
     * Persists a list of entities and flushes.
     *
     * @param array $entities A list of entities to persist
     */
    public function flush(array $entities)
    {
        foreach ($entities as $entity) {
            $this->em->persist($entity);
        }

        $this->em->flush();
    }
}
