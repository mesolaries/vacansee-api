<?php

namespace App\Service\Scraper\Providers;

use App\Entity\Category;
use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class BossazScraperService extends AbstractScraperService
{
    private const BASE_URL = 'https://en.boss.az';

    protected const CATEGORIES_URLS = [
        'it' => 'https://en.boss.az/vacancies?search%5Bcategory_id%5D=38',
        'design' => 'https://en.boss.az/vacancies?search%5Bcategory_id%5D=43',
        'service' => 'https://en.boss.az/vacancies?search%5Bcategory_id%5D=133',
        'marketing' => 'https://en.boss.az/vacancies?search%5Bcategory_id%5D=37',
        'administration' => 'https://en.boss.az/vacancies?search%5Bcategory_id%5D=44',
        'sales' => 'https://en.boss.az/vacancies?search%5Bcategory_id%5D=40',
        'finance' => 'https://en.boss.az/vacancies?search%5Bcategory_id%5D=36',
        'medical' => 'https://en.boss.az/vacancies?search%5Bcategory_id%5D=138',
        'legal' => 'https://en.boss.az/vacancies?search%5Bcategory_id%5D=39',
        'education' => 'https://en.boss.az/vacancies?search%5Bcategory_id%5D=46',
        'other' => 'https://en.boss.az/vacancies',
    ];

    /**
     * {@inheritdoc}
     */
    public function spot(string $url, int $timestamp): array
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $pagination = $crawler->filter('nav.pagination span.next > a');

        $nodes = $crawler->filter('.results-i');

        $links = [];

        $vacancyRepository = $this->getEntityManager()->getRepository(Vacancy::class);

        foreach ($nodes as $node) {
            $node = new Crawler($node, self::BASE_URL);
            $link = $node->filter('a.results-i-link')->link();
            $crawler = $client->click($link);

            $date = $crawler->filter('.bumped_on.params-i-val')->text();
            $link = $crawler->filter('a.lang-switcher.az')->link();
            $link = $client->click($link)->getUri();

            if (strtotime($date) <= $timestamp || $vacancyRepository->findOneBy(['url' => $link])) {
                return $links;
            }

            $links[] = $link;
        }

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
    public function scrape(string $url, Category $category)
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $title = $crawler->filter('.post-title')->first()->text();
        $company = $crawler->filter('.post-company')->first()->filter('a')->text();
        $salary = $crawler->filter('.post-salary.salary')->first()->text();
        $salary = (int)$salary ? $salary : null;
        $description = $crawler->filter('.post-cols.post-info')->first()->text();
        $description_html = $crawler->filter('.post-cols.post-info')->first()->html();

        // Go to the english version of site to take a date
        $link = $crawler->filter('a.lang-switcher.en')->link();
        $crawler = $client->click($link);

        $date = $crawler->filter('.bumped_on.params-i-val')->text();

        $datetime = new \DateTime();
        $datetime->setTimestamp(strtotime($date));

        $vacancy = new Vacancy();

        $vacancy->setTitle($title);
        $vacancy->setCompany($company);
        $vacancy->setDescription($description);
        $vacancy->setDescriptionHtml($description_html);
        $vacancy->setSalary($salary);
        $vacancy->setCategory($category);
        $vacancy->setUrl($url);
        $vacancy->setCreatedAt($datetime);

        $this->getEntityManager()->persist($vacancy);
        $this->getEntityManager()->flush();
    }
}
