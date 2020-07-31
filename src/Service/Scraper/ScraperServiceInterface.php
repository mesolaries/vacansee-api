<?php

namespace App\Service\Scraper;

interface ScraperServiceInterface
{
    /**
     * @param string $url
     * @param int    $timestamp
     *
     * @return array
     */
    public function spot(string $url, int $timestamp): array;

    /**
     * @param array  $urls
     *
     * @param string $category
     *
     * @return array
     */
    public function scrape(array $urls, string $category): array;
}
