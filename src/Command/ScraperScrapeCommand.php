<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use App\Service\Scraper\ScraperChainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ScraperScrapeCommand extends Command
{
    protected static $defaultName = 'app:scraper:scrape';

    private ScraperChainService $chain;

    private EntityManagerInterface $em;

    // Data published interval (-1 day = Scrape data published today)
    private const INTERVAL = '-1 day';

    public function __construct(ScraperChainService $chain, EntityManagerInterface $em, string $name = null)
    {
        $this->chain = $chain;
        $this->em = $em;
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
            $io->note("Scraping $alias provider...");
            $count += $this->scrape($alias, $io);
        }

        $scraping_end_time = time();

        $io->success("Success! Added $count new vacancies.");
        $io->note("Scraping took " . ($scraping_end_time - $scraping_start_time) . " seconds.");

        return 0;
    }

    /**
     * @param string       $alias
     *
     * @param SymfonyStyle $io
     *
     * @return int
     */
    private function scrape(string $alias, SymfonyStyle $io)
    {
        /** @var AbstractScraperService $scraper */
        $scraper = $this->chain->getScraper($alias);

        $urls = $scraper->getCategoriesUrls();

        $count = 0;

        foreach ($urls as $categorySlug => $url) {
            if (!array_key_exists($categorySlug, Vacancy::getCategories())) {
                continue;
            }

            $categoryRepository = $this->em->getRepository(Category::class);
            /** @var Category|null $category */
            $category = $categoryRepository->findOneBy(['slug' => $categorySlug]);

            if (!$category) {
                $io->warning(
                    "$categorySlug not found in Category entity. Did you forget to sync? (Run app:sync:categories)"
                );
                continue;
            }

            $io->note("Category: $categorySlug");

            try {
                $spotted_urls = $scraper->spot($url, strtotime(self::INTERVAL));
            } catch (TransportExceptionInterface $e) {
                $io->warning($e->getMessage() . ' Continuing with the next category.');
                continue;
            }

            foreach ($spotted_urls as $spotted_url) {
                try {
                    $scraper->scrape($spotted_url, $category);
                    $count++;
                } catch (TransportExceptionInterface $e) {
                    $io->warning($e->getMessage() . ' Continuing with the next spotted url.');
                    continue;
                }
            }
        }

        return $count;
    }
}
