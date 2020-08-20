<?php

namespace App\Service\Scraper;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractScraperService implements ScraperServiceInterface
{
    protected const CATEGORIES_URLS = [];

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getEntityManager()
    {
        return $this->em;
    }

    public function getCategoriesUrls()
    {
        return $this::CATEGORIES_URLS;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function spot(string $url, int $timestamp): array;

    /**
     * {@inheritdoc}
     */
    abstract public function scrape(string $url, Category $category);
}
