<?php

namespace App\Service\Spotter;

interface SpotterServiceInterface
{
    /**
     * @param string $url
     * @param int    $timestamp
     *
     * @return array
     */
    public function spot(string $url, int $timestamp): array;
}
