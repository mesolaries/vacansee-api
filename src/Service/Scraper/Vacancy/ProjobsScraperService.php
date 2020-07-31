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
                $id = $vacancy['id'];
                $links[] = self::WEB_URL."/$id";
            }
        }

        return $links;
    }

    public function scrape(array $urls, string $category): array
    {
        $client = new Client();

        $vacancies = [];
        foreach ($urls as $url) {
            $url_parts = explode('/', $url);
            $id = end($url_parts);
            $url = self::API_URL."/$id";

            $client->request('GET', $url);
            $content = json_decode($client->getResponse()->getContent(), true);
            $content = $content['data'];

            $vacancy = new Vacancy();

            $vacancy->setTitle($content['name']);
            $vacancy->setCompany($content['companyName']);
            $vacancy->setDescription($content['description']);
            $vacancy->setSalary($content['salary'].' '.$content['currency']['name']);
            $vacancy->setCategory($category);
            $vacancy->setUrl(self::WEB_URL.'/'.$content['id']);

            $vacancies[] = $vacancy;
        }

        return $vacancies;
    }
}
