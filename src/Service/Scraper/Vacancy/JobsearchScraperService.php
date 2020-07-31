<?php

namespace App\Service\Scraper\Vacancy;

use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use Goutte\Client;

class JobsearchScraperService extends AbstractScraperService
{
    public function spot(string $url, int $timestamp): array
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $links = [];

        $vacancies = $crawler->filter('table.hotvac')->first()->filter('tr')->nextAll()->reduce(function ($node) use ($timestamp) {
            $date = $node->filter('td.date_text')->first()->text();

            return strtotime($date) > $timestamp;
        });

        $vacancies->filter('a.hotv_text')->each(
            function ($node) use (&$links, $url) {
                $links[] = $url.'/'.$node->attr('href');
            }
        );

        return $links;
    }

    public function scrape(array $urls, string $category): array
    {
        $client = new Client();
        $vacancies = [];
        foreach ($urls as $url) {
            $crawler = $client->request('GET', $url);

            $title = $crawler
                ->evaluate('/html/body/table/tr[3]/td/table/tr/td[2]/table/tr/td[1]/table/tr[3]/td/table/tr[2]/td[2]/table/tr[1]/td')
                ->html();
            $title = trim(explode('</span>', $title)[1]);

            $company = $crawler
                ->evaluate('/html/body/table/tr[3]/td/table/tr/td[2]/table/tr/td[1]/table/tr[3]/td/table/tr[2]/td[2]/table/tr[2]/td[1]')
                ->html();
            $company = trim(explode('</span>', $company)[1]);

            $description = $crawler
                ->evaluate('/html/body/table/tr[3]/td/table/tr/td[2]/table/tr/td[1]/table/tr[3]/td/table/tr[4]/td[2]/table/tr/td')
                ->text();


            $vacancy = new Vacancy();

            $vacancy->setTitle($title);
            $vacancy->setCompany($company);
            $vacancy->setDescription($description);
            $vacancy->setCategory($category);
            $vacancy->setUrl($url);

            $vacancies[] = $vacancy;
        }

        return $vacancies;
    }
}
