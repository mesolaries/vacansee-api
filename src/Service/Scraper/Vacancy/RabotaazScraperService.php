<?php

namespace App\Service\Scraper\Vacancy;

use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;

class RabotaazScraperService extends AbstractScraperService
{
    const URLS = [
        'base' => 'https://www.rabota.az',
        'all' => 'https://www.rabota.az/vacancy/search?created=1&sortby=2',
        'categories' => [
            'it' => 'https://www.rabota.az/vacancy/search?created=1&sortby=2&category%%5B%%5D=6',
            'design' => 'https://www.rabota.az/vacancy/search?created=1&sortby=2&category%%5B%%5D=21',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function spot(string $url, int $timestamp = null): array
    {
        $url_parts = parse_url($url);
        $base_url = $url_parts['scheme'].'://'.$url_parts['host'];

        $client = new Client();

        $crawler = $client->request('GET', $url);

        $links = [];

        // Get links
        $crawler->filter('#vacancy-list a.title-')->each(function ($node) use (&$links, $base_url) {
            $links[] = $base_url.$node->attr('href');
        });

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
