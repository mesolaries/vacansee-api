<?php

namespace App\Service\Spotter\Jobsearch;

use App\Service\Spotter\AbstractSpotterService;
use Goutte\Client;

class JobsearchSpotterService extends AbstractSpotterService
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
}
