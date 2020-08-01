<?php

namespace App\Service\Scraper\Vacancy;

use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class JobsearchScraperService extends AbstractScraperService
{
    private const BASE_URL = 'https://jobsearch.az';

    /**
     * {@inheritDoc}
     */
    public function spot(string $url, int $timestamp): array
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $links = [];

        $vacancies = $crawler->filter('table.hotvac')
            ->first()
            ->filter('tr')
            ->nextAll();

        foreach ($vacancies as $vacancy) {
            $vacancy = new Crawler($vacancy, self::BASE_URL);
            $date = $vacancy->filter('td.date_text')->first()->text();
            if (strtotime($date) <= $timestamp) {
                break;
            }
            $links[] = $vacancy->filter('a.hotv_text')->first()->link()->getUri();
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
            $crawler = $client->request('GET', $url);

            $title = $crawler
                ->evaluate(
                    '/html/body/table/tr[3]/td/table/tr/td[2]/table/tr/td[1]/table/tr[3]/td/table/tr[2]/td[2]/table/tr[1]/td'
                )
                ->html();
            $title = trim(explode('</span>', $title)[1]);

            $company = $crawler
                ->evaluate(
                    '/html/body/table/tr[3]/td/table/tr/td[2]/table/tr/td[1]/table/tr[3]/td/table/tr[2]/td[2]/table/tr[2]/td[1]'
                )
                ->html();
            $company = trim(explode('</span>', $company)[1]);

            $description = $crawler
                ->evaluate(
                    '/html/body/table/tr[3]/td/table/tr/td[2]/table/tr/td[1]/table/tr[3]/td/table/tr[4]/td[2]/table/tr/td'
                )
                ->text();

            $description_html = $crawler
                ->evaluate(
                    '/html/body/table/tr[3]/td/table/tr/td[2]/table/tr/td[1]/table/tr[3]/td/table/tr[4]/td[2]/table/tr/td'
                )
                ->html();

            $date = $crawler
                ->evaluate(
                    '/html/body/table/tr[3]/td/table/tr/td[2]/table/tr/td[1]/table/tr[3]/td/table/tr[2]/td[2]/table/tr[3]/td[1]'
                )
                ->html();
            $date = trim(explode('</span>', $date)[1]);

            $datetime = new \DateTime();
            $datetime->setTimestamp(strtotime($date));

            $vacancy = new Vacancy();

            $vacancy->setTitle($title);
            $vacancy->setCompany($company);
            $vacancy->setDescription($description);
            $vacancy->setDescriptionHtml($description_html);
            $vacancy->setCategory($category);
            $vacancy->setUrl($url);
            $vacancy->setCreatedAt($datetime);

            $vacancies[] = $vacancy;
        }

        return $vacancies;
    }
}
