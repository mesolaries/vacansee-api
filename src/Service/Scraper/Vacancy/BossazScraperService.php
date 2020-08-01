<?php

namespace App\Service\Scraper\Vacancy;

use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class BossazScraperService extends AbstractScraperService
{

    private const BASE_URL = 'https://en.boss.az';

    /**
     * {@inheritdoc}
     */
    public function spot(string $url, int $timestamp): array
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $pagination = $crawler->filter('nav.pagination span.next > a');

        $nodes = $crawler->filter('.results-i');
        $finished = false;
        $links = [];

        foreach ($nodes as $node) {
            $node = new Crawler($node, self::BASE_URL);
            $link = $node->filter('a.results-i-link')->link();
            $crawler = $client->click($link);
            $date = $crawler->filter('.bumped_on.params-i-val')->text();
            if (strtotime($date) <= $timestamp) {
                $finished = true;
                break;
            }
            $link = $crawler->filter('a.lang-switcher.az')->link();
            $links[] = $client->click($link)->getUri();
        }

        // If it's the last page, it shouldn't have a pagination link or it may have a special class
        if ($pagination->count() && !$finished) {
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
