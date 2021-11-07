<?php

namespace App\Service\Scraper\Providers;

use App\Entity\Category;
use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;

class JobsearchScraperService extends AbstractScraperService
{
    private const WEB_URL = 'https://jobsearch.az/vacancies';
    private const API_URL = 'https://jobsearch.az/api-az/vacancies-az';

    protected const CATEGORIES_URLS = [
        'it' => self::API_URL.'?categories=1076',
        'design' => self::API_URL.'?categories=886',
        'marketing' => self::API_URL.'?categories=1288',
        'administration' => self::API_URL.'?categories=850',
        'sales' => self::API_URL.'?categories=1411',
        'finance' => self::API_URL.'?categories=1263',
        'medical' => self::API_URL.'?categories=937',
        'legal' => self::API_URL.'?categories=1375',
        'education' => self::API_URL.'?categories=1004',
        'other' => self::API_URL,
    ];

    /**
     * {@inheritDoc}
     */
    public function spot(string $url, int $timestamp): array
    {
        $client = new Client();

        // Adds X-Requested-With header
        $client->xmlHttpRequest('GET', $url);

        $content = json_decode($client->getResponse()->getContent(), true);

        $vacancies = $content['items'];

        $links = [];

        $vacancyRepository = $this->getEntityManager()->getRepository(Vacancy::class);

        foreach ($vacancies as $vacancy) {
            $createdAt = strtotime($vacancy['created_at']);
            $link = $this->makeWebUrl($vacancy['slug']);
            $isVip = $vacancy['is_vip'];

            if ($createdAt <= $timestamp || $vacancyRepository->findOneBy(['url' => $link])) {
                if ($isVip) {
                    continue;
                }

                return $links;
            }

            $links[] = $link;
        }

        if (array_key_exists('next', $content)) {
            $links = array_merge($links, $this->spot($content['next'], $timestamp));
        }

        return array_unique($links);
    }

    /**
     * {@inheritDoc}
     */
    public function scrape(string $url, Category $category)
    {
        $client = new Client();

        $url = $this->makeApiUrl($url);

        $client->xmlHttpRequest('GET', $url);

        $content = json_decode($client->getResponse()->getContent(), true);

        $createdAt = strtotime($content['created_at']);
        $datetime = new \DateTime();
        $datetime->setTimestamp($createdAt);

        $vacancy = new Vacancy();

        $vacancy
            ->setTitle($content['title'])
            ->setCompany($content['company']['title'])
            ->setDescription(strip_tags($content['text']))
            ->setDescriptionHtml($content['text'])
            ->setCategory($category)
            ->setUrl($this->makeWebUrl($content['slug']))
            ->setCreatedAt($datetime);

        $this->getEntityManager()->persist($vacancy);
        $this->getEntityManager()->flush();
    }

    private function makeApiUrl(string $url): string
    {
        $url_parts = explode('/', $url);
        $slug = end($url_parts);

        return self::API_URL."/$slug";
    }

    private function makeWebUrl(string $slug): string
    {
        return self::WEB_URL."/$slug";
    }
}
