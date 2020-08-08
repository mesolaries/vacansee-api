<?php

namespace App\Command;

use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use App\Service\Scraper\ScraperChainService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ScrapeVacancyCommand extends Command
{
    protected static $defaultName = 'app:scrape:vacancy';

    private ScraperChainService $chain;

    // Data published interval (-1 day = Scrape data published today)
    private const INTERVAL = '-1 day';

    public function __construct(ScraperChainService $chain, string $name = null)
    {
        $this->chain = $chain;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Scrape new vacancies and save them to database.')
            ->addOption(
                'provider',
                'p',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                "Scrape only this vacancy provider.\n" .
                "Choose from: [" . implode(', ', array_keys($this->chain->getScrapers())) . "]",
                array_keys($this->chain->getScrapers())
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $provider = $input->getOption('provider');

        $services = array_intersect_key($this->chain->getScrapers(), array_flip($provider));

        if (!count($services)) {
            throw new InvalidOptionException(
                "The provider(s) you've entered are invalid. Please, choose from: [" .
                implode(', ', array_keys($this->chain->getScrapers())) .
                ']'
            );
        }

        $io->writeln("Give me a moment. I'm scraping...");

        $scraping_start_time = time();

        $count = 0;
        foreach ($services as $alias => $service) {
            $count += $this->scrape($alias);
        }

        $scraping_end_time = time();

        $io->success("Success! Added $count new vacancies.");
        $io->note("Scraping took " . ($scraping_end_time - $scraping_start_time) . " seconds.");

        return 0;
    }

    /**
     * @param $alias
     *
     * @return int
     */
    private function scrape($alias)
    {
        /** @var AbstractScraperService $scraper */
        $scraper = $this->chain->getScraper($alias);

        $urls = $scraper->getCategoriesUrls();

        $count = 0;

        foreach ($urls as $category => $url) {
            $spotted_urls = $scraper->spot($url, strtotime(self::INTERVAL));
            $filtered_urls = $scraper->filter($spotted_urls, Vacancy::class);
            $vacancies = $scraper->scrape($filtered_urls, $category);
            $scraper->flush($vacancies);
            $count += count($filtered_urls);
        }

        return $count;
    }
}
