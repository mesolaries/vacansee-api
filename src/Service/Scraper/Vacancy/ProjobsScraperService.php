<?php

namespace App\Service\Scraper\Vacancy;

use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;

class ProjobsScraperService extends AbstractScraperService
{
    private const WEB_URL = 'https://projobs.az/jobdetails';
    private const API_URL = 'https://core.projobs.az/v1/vacancies';

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
    public function scrape(array $urls, string $category): array
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
