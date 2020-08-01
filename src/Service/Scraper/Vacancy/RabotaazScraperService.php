<?php

namespace App\Service\Scraper\Vacancy;

use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;

class RabotaazScraperService extends AbstractScraperService
{
    private const BASE_URL = 'https://www.rabota.az';

    /**
     * {@inheritdoc}
     */
    public function spot(string $url, int $timestamp = null): array
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $pagination = $crawler->filter('a.lb-orange-item.next');

        $links = [];

        $crawler->filter('#vacancy-list a.title-')->each(
            function ($node) use (&$links) {
                $links[] = self::BASE_URL . $node->attr('href');
            }
        );

        // If it's the last page, it shouldn't have a pagination link or it may have a special class
        if ($pagination->count()) {
            $nextPageUrl = $client->click($pagination->link())->getUri();
            $links = array_merge($links, $this->spot($nextPageUrl, $timestamp));
        }

        return $links;
    }

    /**
     * {@inheritdoc}
     */
    public function scrape(array $urls, string $category): array
    {
        $client = new Client();
        $vacancies = [];
        foreach ($urls as $url) {
            $crawler = $client->request('GET', $url);

            $title = $crawler->filter('.title-')->first()->text();
            $company = $crawler->filter('.employer-')->first()->filter('a')->text();
            $description = $crawler->filter('.details-')->first()->text();
            $salary = $crawler->filter('.salary-')->first()->text();

            $vacancy = new Vacancy();

            $vacancy->setTitle($title);
            $vacancy->setCompany($company);
            $vacancy->setDescription($description);
            $vacancy->setSalary($salary);
            $vacancy->setCategory($category);
            $vacancy->setUrl($url);

            $vacancies[] = $vacancy;
        }

        return $vacancies;
    }
}
