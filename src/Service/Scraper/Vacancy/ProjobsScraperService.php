<?php

namespace App\Service\Scraper\Vacancy;

use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;

class ProjobsScraperService extends AbstractScraperService
{
    const WEB_URL = 'https://projobs.az/jobdetails';
    const API_URL = 'https://core.projobs.az/v1/vacancies';

    /**
     * {@inheritdoc}
     */
    public function spot(string $url, int $timestamp): array
    {
        $client = new Client();

        $client->request('GET', $url);

        $content = json_decode($client->getResponse()->getContent(), true);

        $vacancies = $content['data'];

        $links = [];

        foreach ($vacancies as $vacancy) {
            $created_at = strtotime($vacancy['createdAt']) + date('Z');
            if ($created_at > $timestamp) {
                $links[] = $this->makeWebUrl($vacancy['id']);
            }
        }

        return $links;
    }

    public function scrape(array $urls, string $category): array
    {
        $client = new Client();

        $vacancies = [];
        foreach ($urls as $url) {
            $url = $this->makeApiUrl($url);

            $client->request('GET', $url);
            $content = json_decode($client->getResponse()->getContent(), true);
            $data = $content['data'];

            $vacancy = new Vacancy();

            $vacancy->setTitle($data['name']);
            $vacancy->setCompany($data['companyName']);
            $vacancy->setDescription($data['description']);
            $vacancy->setSalary($data['salary'].' '.$data['currency']['name']);
            $vacancy->setCategory($category);
            $vacancy->setUrl($this->makeWebUrl($data['id']));

            $vacancies[] = $vacancy;
        }

        return $vacancies;
    }

    private function makeApiUrl($url): string
    {
        $url_parts = explode('/', $url);
        $id = end($url_parts);

        return self::API_URL."/$id";
    }

    private function makeWebUrl($id): string
    {
        return self::WEB_URL."/$id";
    }
}
