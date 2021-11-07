<?php

namespace App\Service\Scraper;

use App\Entity\Category;

interface ScraperServiceInterface
{
    /**
     * Scrapes website's list view and returns URLs which needed to be scraped for detailed info.
     *
     * @param string $url       List view URL
     * @param int    $timestamp Start date to compare with website's data created at date (if present)
     *
     * @return array Spotted list of URLs
     */
    public function spot(string $url, int $timestamp): array;

    /**
     * Scrapes website's detailed view and flushes to database.
     *
     * @param string   $urls     URLs to scrape
     * @param Category $category Website's data category (e.g. it, design and etc.)
     *
     * @return mixed
     */
    public function scrape(string $urls, Category $category);
}
