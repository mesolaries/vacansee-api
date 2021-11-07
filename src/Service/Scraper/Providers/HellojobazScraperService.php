<?php

namespace App\Service\Scraper\Providers;

use App\Entity\Category;
use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class HellojobazScraperService extends AbstractScraperService
{
    private const BASE_URL = 'https://www.hellojob.az';

    protected const CATEGORIES_URLS = [
        'it' => 'https://www.hellojob.az/texnologiya-it',
        'design' => 'https://www.hellojob.az/dizayn',
        'service' => 'https://www.hellojob.az/xidmet',
        'marketing' => 'https://www.hellojob.az/marketinq',
        'administration' => 'https://www.hellojob.az/inzibati',
        'sales' => 'https://www.hellojob.az/satis',
        'finance' => 'https://www.hellojob.az/maliyye',
        'medical' => 'https://www.hellojob.az/tibb-ve-eczaciliq',
        'legal' => 'https://www.hellojob.az/huquqsunasliq',
        'education' => 'https://www.hellojob.az/tehsil-ve-elm',
        'other' => 'https://www.hellojob.az/vakansiyalar',
    ];

    /**
     * {@inheritDoc}
     *
     * Due to not existence of proper date text and date filter working only with today's vacancies
     */
    public function spot(string $url, int $timestamp): array
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $pagination = $crawler->filter('ul.pagination li.page-item.active');

        if ($pagination->count()) {
            $pagination = $pagination->nextAll();
        }

        $nodes = $crawler->evaluate('/html/body/main/div[8]/div/div/div[2]/div');

        if (!$nodes->count()) {
            $nodes = $crawler->evaluate('/html/body/main/div[4]/div[3]/div/div[2]/div');
        }

        $nodes = $nodes->children();

        $links = [];

        $vacancyRepository = $this->getEntityManager()->getRepository(Vacancy::class);

        foreach ($nodes as $node) {
            $node = new Crawler($node, self::BASE_URL);
            $link = $node->filter('a.full')->link();

            $crawler = $client->click($link);

            $link = $client->click($link)->getUri();

            $date_row = $crawler->filter('div.elan_inner_specs.only_vacancy_specs ul li');

            $date = $date_row->eq($date_row->count() - 3)->filter('span')->text();

            if (false === mb_strpos(mb_strtolower($date), 'bu gün') ||
                $vacancyRepository->findOneBy(['url' => $link])) {
                return $links;
            }

            $links[] = $link;
        }

        // If it's the last page, it shouldn't have a pagination link or it may have a special class
        if ($pagination->count()) {
            $nextPageUrl = $client->click(
                $pagination->eq(0)->children('a.page-link')->link()
            )->getUri();

            $links = array_merge($links, $this->spot($nextPageUrl, $timestamp));
        }

        return $links;
    }

    /**
     * {@inheritDoc}
     */
    public function scrape(string $url, Category $category)
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $title = $crawler->filter('div.ei_name_andsalary h3')->first()->text();
        $company = $crawler->filter('p.company_name')->first()->text();
        $salary = $crawler->filter('div.ei_name_andsalary span.salary')->first()->text();
        $salary = (int) $salary ? $salary : null;

        $description_node = $crawler->filter('div.elan_inner_desc');

        $description = $description_node->first()->text().$description_node->last()->text();
        $description_html = $description_node->first()->html().$description_node->last()->html();

        $datetime = new \DateTime();

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
