<?php

namespace App\Service\Scraper;

class ScraperChainService
{
    private array $scrapers;

    public function __construct()
    {
        $this->scrapers = [];
    }

    public function addScraper(ScraperServiceInterface $scraper, $alias)
    {
        $this->scrapers[$alias] = $scraper;
    }

    public function getScrapers()
    {
        return $this->scrapers;
    }

    public function getScraper($alias)
    {
        if (array_key_exists($alias, $this->scrapers)) {
            return $this->scrapers[$alias];
        }
    }
}
