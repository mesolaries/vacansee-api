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

    private $container;
    private $parameters;

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
            ->setDescription('Spot new vacancies from available providers')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, "Spot vacancies only from this provider.\n See available providers in service parameters")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $provider_option = $input->getOption('provider');

        if ($provider_option) {
            $io->note(sprintf('You passed a provider option: %s', $provider_option));

            try {
                $scraper = $this->get($provider_option);
                $providers = $this->parameters->get('app.vacancy.providers');
                $listUrlsByCategory = $providers[$provider_option]['urls']['categories'];

                foreach ($listUrlsByCategory as $category => $url) {
                    $urls = $scraper->filter($scraper->spot($url, strtotime('-1 day')), Vacancy::class);
                    $vacancies = $scraper->scrape($urls, $category);
                    $scraper->flush($vacancies);
                }
                // Implement
            } catch (\Throwable $t) {
                throw new InvalidOptionException($t->getMessage());
            }
        } else {
            foreach (self::getSubscribedServices() as $id => $service) {
                /** @var AbstractScraperService $scraper */
                $scraper = $this->get($id);
                $providers = $this->parameters->get('app.vacancy.providers');
                $listUrlsByCategory = $providers[$id]['urls']['categories'];

                foreach ($listUrlsByCategory as $category => $url) {
                    $urls = $scraper->filter($scraper->spot($url, strtotime('-1 day')), Vacancy::class);
                    $vacancies = $scraper->scrape($urls, $category);
                    $scraper->flush($vacancies);
                }
            }
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return 0;
    }

}
