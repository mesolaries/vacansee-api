<?php

namespace App\Service\Scraper\Providers;

use App\Entity\Category;
use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;

class ProjobsScraperService extends AbstractScraperService
{
    private const WEB_URL = 'https://projobs.az/jobdetails';
    private const API_URL = 'https://core.projobs.az/v1/vacancies';

    protected const CATEGORIES_URLS = [
        'it' => 'https://core.projobs.az/v1/vacancies?category=17&page=1',
        'design' => 'https://core.projobs.az/v1/vacancies?category=36&page=1',
        'service' => 'https://core.projobs.az/v1/vacancies?category=61&page=1',
        'marketing' => 'https://core.projobs.az/v1/vacancies?category=11&page=1',
        'administration' => 'https://core.projobs.az/v1/vacancies?category=25&page=1',
        'sales' => 'https://core.projobs.az/v1/vacancies?category=32&page=1',
        'finance' => 'https://core.projobs.az/v1/vacancies?category=1&page=1',
        'medical' => 'https://core.projobs.az/v1/vacancies?category=74&page=1',
        'legal' => 'https://core.projobs.az/v1/vacancies?category=43&page=1',
        'education' => 'https://core.projobs.az/v1/vacancies?category=48&page=1',
        'other' => 'https://core.projobs.az/v1/vacancies?page=1',
    ];

    private int $page = 1;

    /**
     * {@inheritdoc}
     */
    public function spot(string $url, int $timestamp): array
    {
        $client = new Client();

        $client->request('GET', $url);

        $content = json_decode($client->getResponse()->getContent(), true);

        $vacancies = $content['data'];
        $finished = false;
        $links = [];

        foreach ($vacancies as $vacancy) {
            $created_at = strtotime($vacancy['createdAt']) + date('Z');
            if ($created_at <= $timestamp) {
                $finished = true;
                break;
            }
            $links[] = $this->makeWebUrl($vacancy['id']);
        }


        $nextPageUrl = preg_replace('/page=[0-9]+/', 'page=' . ($this->page + 1), $url);
        $client->request('GET', $nextPageUrl);
        $nextPageContent = json_decode($client->getResponse()->getContent(), true);
        $vacancies = $nextPageContent['data'];
        if (count($vacancies) && !$finished) {
            $this->page++;
            $links = array_merge($links, $this->spot($nextPageUrl, $timestamp));
        }

        return $links;
    }

    /**
     * {@inheritDoc}
     */
    public function scrape(array $urls, Category $category): array
    {
        $client = new Client();

        $vacancies = [];
        foreach ($urls as $url) {
            $url = $this->makeApiUrl($url);

            $client->request('GET', $url);
            $content = json_decode($client->getResponse()->getContent(), true);
            $data = $content['data'];

            $created_at = strtotime($data['createdAt']) + date('Z');
            $datetime = new \DateTime();
            $datetime->setTimestamp($created_at);

            $vacancy = new Vacancy();

            $vacancy->setTitle($data['name']);
            $vacancy->setCompany($data['companyName']);
            $vacancy->setDescription($data['description']);
            $vacancy->setDescriptionHtml($data['description']);
            $vacancy->setSalary($data['salary'] . ' ' . $data['currency']['name']);
            $vacancy->setCategory($category);
            $vacancy->setUrl($this->makeWebUrl($data['id']));
            $vacancy->setCreatedAt($datetime);

            $vacancies[] = $vacancy;
        }

        return $vacancies;
    }

    private function makeApiUrl($url): string
    {
        $url_parts = explode('/', $url);
        $id = end($url_parts);

        return self::API_URL . "/$id";
    }

    private function makeWebUrl($id): string
    {
        return self::WEB_URL . "/$id";
    }
}
