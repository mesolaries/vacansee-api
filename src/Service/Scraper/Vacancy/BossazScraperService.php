<?php

namespace App\Service\Scraper\Vacancy;

use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;

class BossazScraperService extends AbstractScraperService
{
    /**
     * {@inheritdoc}
     */
    public function spot(string $url, int $timestamp): array
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $links = [];

        $crawler->filter('.results-i')->each(function ($node) use ($client, $timestamp, &$links) {
            $link = $node->selectLink('Read more')->link();

            $crawler = $client->click($link);
            $date = $crawler->filter('.bumped_on.params-i-val')->text();
            if (strtotime($date) > $timestamp) {
                $link = $crawler->filter('a.lang-switcher.az')->link();
                $links[] = $client->click($link)->getUri();
            }
        });

        return $links;
    }

    public function scrape(array $urls, string $category): array
    {
        $client = new Client();

        $vacancies = [];
        foreach ($urls as $url) {
            $crawler = $client->request('GET', $url);

            $title = $crawler->filter('.post-title')->first()->text();
            $company = $crawler->filter('.post-company')->first()->filter('a')->text();
            $description = $crawler->filter('.post-cols.post-info')->first()->text();
            $salary = $crawler->filter('.post-salary.salary')->first()->text();

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
