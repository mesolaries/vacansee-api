<?php

namespace App\Command;

use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use App\Service\Scraper\Vacancy\BossazScraperService;
use App\Service\Scraper\Vacancy\JobsearchScraperService;
use App\Service\Scraper\Vacancy\ProjobsScraperService;
use App\Service\Scraper\Vacancy\RabotaazScraperService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class ScrapeVacancyCommand extends Command implements ServiceSubscriberInterface
{
    protected static $defaultName = 'app:scrape:vacancy';

    private ContainerInterface $container;
    private ParameterBagInterface $parameters;

    public function __construct(ContainerInterface $container, ParameterBagInterface $parameters, string $name = null)
    {
        parent::__construct($name);
        $this->container = $container;
        $this->parameters = $parameters;
    }

    public static function getSubscribedServices()
    {
        return [
            'bossaz' => BossazScraperService::class,
            'jobsearch' => JobsearchScraperService::class,
            'rabotaaz' => RabotaazScraperService::class,
            'projobs' => ProjobsScraperService::class,
        ];
    }

    protected function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Gets a container service by its id.
     *
     * @param string $id
     *
     * @return object The service
     */
    protected function get(string $id): object
    {
        return $this->container->get($id);
    }

    protected function configure()
    {
        $this
            ->setDescription('Scrape new vacancies and save them to database.')
            ->addOption(
                'provider',
                'p',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                "Scrape only this vacancy provider.\n Choose from: [" .
                implode(', ', array_keys(self::getSubscribedServices())) .
                ']',
                array_keys(self::getSubscribedServices())
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $provider = $input->getOption('provider');

        $services = array_intersect_key(self::getSubscribedServices(), array_flip($provider));

        if (!count($services)) {
            throw new InvalidOptionException(
                "The provider(s) you've entered are invalid. Please, choose from: [" .
                implode(', ', array_keys(self::getSubscribedServices())) .
                ']'
            );
        }

        $io->writeln("Give me a moment. I'm scraping...");

        $scraping_start_time = time();

        $count = 0;
        foreach ($services as $id => $service) {
            $count += $this->scrape($id);
        }

        $scraping_end_time = time();

        $io->success("Success! Added $count new vacancies.");
        $io->note("Scraping took " . ($scraping_end_time - $scraping_start_time) . " seconds.");

        return 0;
    }

    /**
     * @param $serviceId
     *
     * @return int
     */
    private function scrape($serviceId)
    {
        /** @var AbstractScraperService $scraper */
        $scraper = $this->get($serviceId);
        $parameters = $this->parameters->get('app.vacancy.providers');
        $urls = $parameters[$serviceId]['urls']['categories'];

        $count = 0;

        foreach ($urls as $category => $url) {
            $spotted_urls = $scraper->spot($url, strtotime('-30 day'));
            $filtered_urls = $scraper->filter($spotted_urls, Vacancy::class);
            $vacancies = $scraper->scrape($filtered_urls, $category);
            $scraper->flush($vacancies);
            $count += count($filtered_urls);
        }

        return $count;
    }
}
