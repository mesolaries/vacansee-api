<?php

namespace App\Command;

use App\Service\Spotter\Bossaz\BossazSpotterService;
use App\Service\Spotter\Jobsearch\JobsearchSpotterService;
use App\Service\Spotter\Rabotaaz\RabotaazSpotterService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class SpotterSpotCommand extends Command implements ServiceSubscriberInterface
{
    protected static $defaultName = 'app:spotter:spot';
    protected static $params;
    protected $locator;
    const INTERVALS = [
        'daily' => '-1 day',
        'weekly' => '-7 days',
        'monthly' => '-30 days',
    ];

    public function __construct(ContainerInterface $locator, ParameterBagInterface $params, string $name = null)
    {
        parent::__construct($name);
        self::$params = $params;
        $this->locator = $locator;
    }

    public static function getSubscribedServices()
    {
        return
            [
                RabotaazSpotterService::class,
                JobsearchSpotterService::class,
                BossazSpotterService::class,
            ];
    }

    public function getService(string $service)
    {
        if ($this->locator->has($service)) {
            return $this->locator->get($service);
        }

        return null;
    }

    protected function configure()
    {
        $this
            ->setDescription('Spot new vacancies from available providers')
            ->addOption('interval', 'i', InputOption::VALUE_REQUIRED, "Spot vacancies on this interval.\n Choose from: [".implode(', ', array_keys(self::INTERVALS)).']', 'daily')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, "Spot vacancies only from this provider.\n See available providers in service parameters")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $interval_option = $input->getOption('interval');
        $provider_option = $input->getOption('provider');

        if ($interval_option) {
            $io->note(sprintf('You passed an interval option: %s', $interval_option));
        }

        if ($provider_option) {
            $io->note(sprintf('You passed a provider option: %s', $provider_option));
        }

        $providers = self::$params->get('app.providers');
        $links = [];

        foreach ($providers as $key => $provider) {
            if ($provider_option && $provider_option !== $key) {
                continue;
            }

            $service = $provider['service'];
            $spotter = $this->getService($service);
            $url = $provider['urls']['base'];
            if ($provider['hasFilters'] && $provider['hasCategories']) {
                $url = $provider['urls'][$interval_option]['all'];
                $all_links = $spotter->spot($url, time());

                $categories = $provider['urls'][$interval_option]['categories'];
                foreach ($categories as $category => $url) {
                    $category_links = $spotter->spot($url, time());

                    $all_links = array_diff($all_links, $category_links);

                    $links = array_merge_recursive($links, [$key => [$category => $category_links]]);
                }

                $links[$key]['other'] = $all_links;
            } elseif ($provider['hasCategories'] && !$provider['hasFilters']) {
                $url = $provider['urls']['all'];
                $all_links = $spotter->spot($url, strtotime(self::INTERVALS[$interval_option]));

                $categories = $provider['urls']['categories'];

                foreach ($categories as $category => $url) {
                    $category_links = $spotter->spot($url, strtotime(self::INTERVALS[$interval_option]));

                    $all_links = array_diff($all_links, $category_links);

                    $links = array_merge_recursive($links, [$key => [$category => $category_links]]);
                }

                $links[$key]['other'] = $all_links;
            } else {
                $category = $key;
                $links = array_merge_recursive($links, [$key => [$category => $spotter->spot($url, strtotime(self::INTERVALS[$interval_option]))]]);
            }
        }

        dump($links);

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return 0;
    }
}
