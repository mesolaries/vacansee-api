<?php

namespace App\Service\Spotter\RabotaAz;

use App\Service\Spotter\AbstractSpotterService;
use Goutte\Client;

class RabotaAzSpotterService extends AbstractSpotterService
{
    public function spot(string $url, int $timestamp): array
    {
        $url_parts = parse_url($url);
        $base_url = $url_parts['scheme'].'://'.$url_parts['host'];

        $client = new Client();

        $crawler = $client->request('GET', $url);

        $links = [];

        // Get links
        $crawler->filter('#vacancy-list a.title-')->each(function ($node) use (&$links, $base_url) {
            $links[] = $base_url.$node->attr('href');
        });

        return $links;
    }
}
