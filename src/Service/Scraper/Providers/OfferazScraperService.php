<?php

namespace App\Service\Scraper\Providers;

use App\Entity\Category;
use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Exception;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class OfferazScraperService extends AbstractScraperService
{
    private const BASE_URL = 'https://offer.az';

    protected const CATEGORIES_URLS = [
        'it' => 'https://www.offer.az/category/it-vakansiyalari',
        'design' => 'https://www.offer.az/category/dizayn-incesenet-vakansiyalari',
        'service' => 'https://www.offer.az/category/musteri-xidmeti-vakansiyalari',
        'marketing' => 'https://www.offer.az/category/marketinq-vakansiyalari',
        'sales' => 'https://www.offer.az/category/satis-vakansiyalari',
        'finance' => 'https://www.offer.az/category/maliyye-vakansiyalari',
        'medical' => 'https://www.offer.az/category/tibb-vakansiyalari',
        'legal' => 'https://www.offer.az/category/huquq-vakansiyalari',
        'education' => 'https://www.offer.az/category/tehsil-vakansiyalari',
        'other' => 'https://www.offer.az/is-elanlari',
    ];

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function spot(string $url, int $timestamp): array
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $pagination = $crawler->filter('.page-numbers.current');

        if ($pagination->count()) {
            $pagination = $pagination->last()->nextAll();
        }

        $nodes = $crawler->filter('#last_posts div.container div.cards a.card');

        $links = [];

        $vacancyRepository = $this->getEntityManager()->getRepository(Vacancy::class);

        foreach ($nodes as $node) {
            $node = new Crawler($node, self::BASE_URL);

            $dateText = $node->filter('div.overlay p.params')->text();

            preg_match('/\d{2}\.\d{2}\.\d{4}/', $dateText, $matches);

            $date = $matches[0];
            $date = new \DateTime($date, new \DateTimeZone('Asia/Baku'));
            $date->setTimezone(new \DateTimeZone('UTC'));

            $link = $node->link()->getUri();

            if ($date->getTimestamp() <= $timestamp || $vacancyRepository->findOneBy(['url' => $link])) {
                return $links;
            }

            $links[] = $link;
        }

        // If it's the last page, it shouldn't have a pagination link, or it may have a special class
        if ($pagination->count()) {
            $nextPageUrl = $client->click($pagination->first()->link())->getUri();
            $links = array_merge($links, $this->spot($nextPageUrl, $timestamp));
        }

        return $links;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function scrape(string $url, Category $category)
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $title = $crawler->filter('#breadcrumbs .breadcrumb_last')->first()->text();
        $company = $crawler->filter('ul.data-list li')->eq(2)->filter('span.value')->text();
        $salary = $crawler->filter('ul.data-list li')->eq(4)->filter('span.value')->text();
        $salary = (int) $salary ? $salary : null;

        // Remove child node from description with social links
        $crawler->filter('div.container div.content')->children()->each(
            function (Crawler $crawler) {
                foreach ($crawler as $node) {
                    if ($crawler->matches('.social-box')) {
                        $node->parentNode->removeChild($node);
                    }
                }
            }
        );

        $description_node = $crawler->filter('div.container div.content')->first();

        $description = $description_node->text();
        $description_html = $description_node->html();

        $date = $crawler->filter('ul.data-list li')->eq(0)->filter('span.value')->text();

        $datetime = new \DateTime($date);

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
