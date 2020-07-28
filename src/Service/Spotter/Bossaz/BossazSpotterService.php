<?php

namespace App\Service\Spotter\Bossaz;

use App\Service\Spotter\AbstractSpotterService;
use Goutte\Client;

class BossazSpotterService extends AbstractSpotterService
{
    /**
     * {@inheritdoc}
     */
    public function spot(string $url, int $timestamp): array
    {
        $client = new Client();

        $crawler = $client->request('GET', $url);

        $links = [];

        $crawler->filter('.results-i')->each(function ($node) use ($client, $timestamp, &$links) {
            $link = $node->selectLink('Read more')->link();

            $crawler = $client->click($link);
            $date = $crawler->filter('.bumped_on.params-i-val')->text();
            if (strtotime($date) > $timestamp) {
                $links[] = $link->getUri();
            }
        });

        return $links;
    }
}
