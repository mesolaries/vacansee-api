<?php

namespace App\Service\Scraper;

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
     * Scrapes website's detailed view and returns a list of scraped data.
     *
     * @param array  $urls     URLs to scrape
     * @param string $category Website's data category (e.g. it, design and etc.)
     *
     * @return array A list of scraped data
     */
    public function scrape(array $urls, string $category): array;
}
