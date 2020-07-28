<?php

namespace App\Service\Spotter;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractSpotterService implements SpotterServiceInterface
{
    protected $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function spot(string $url, int $timestamp): array;
}
