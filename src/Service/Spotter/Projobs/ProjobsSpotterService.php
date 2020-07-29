<?php

namespace App\Service\Spotter\Projobs;

use App\Service\Spotter\AbstractSpotterService;
use Goutte\Client;

class ProjobsSpotterService extends AbstractSpotterService
{
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
                $links[] = strtok($url, '?')."/$id";
            }
        }

        return $links;
    }
}
