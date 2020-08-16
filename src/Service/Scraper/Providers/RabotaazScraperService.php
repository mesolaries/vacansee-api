<?php

namespace App\Service\Scraper\Providers;

use App\Entity\Category;
use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class RabotaazScraperService extends AbstractScraperService
{
    private const BASE_URL = 'https://www.rabota.az';

    protected const CATEGORIES_URLS = [
        'it' => 'https://www.rabota.az/vacancy/search?created=1&sortby=2&category%5B%5D=6',
        'design' => 'https://www.rabota.az/vacancy/search?created=1&sortby=2&category%5B%5D=21',
        'other' => 'https://www.rabota.az/vacancy/search?created=1&sortby=2',
    ];

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
    public function scrape(array $urls, Category $category): array
    {
        $client = new Client();
        $vacancies = [];
        foreach ($urls as $url) {
            $crawler = $client->request('GET', $url);

            $title = $crawler->filter('.title-')->first()->text();
            $company = $crawler->filter('.employer-')->first()->filter('b')->text();
            $salary = $crawler->filter('.salary-')->first()->text();
            $salary = (int)$salary ? $salary : null;

            // Remove child node from description with similar vacancies
            $crawler->filter('.details-')->children()->each(
                function (Crawler $crawler) {
                    foreach ($crawler as $node) {
                        if ($crawler->matches('.similar-vac-list')) {
                            $node->parentNode->removeChild($node);
                        }
                    }
                }
            );

            $description_node = $crawler->filter('.details-')->first();

            $description = $description_node->text();
            $description_html = $description_node->html();

            $vacancy = new Vacancy();

            $vacancy->setTitle($title);
            $vacancy->setCompany($company);
            $vacancy->setDescription($description);
            $vacancy->setDescriptionHtml($description_html);
            $vacancy->setSalary($salary);
            $vacancy->setCategory($category);
            $vacancy->setUrl($url);
            $vacancy->setCreatedAt(new \DateTime());

            $vacancies[] = $vacancy;
        }

        return $vacancies;
    }
}
