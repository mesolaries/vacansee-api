<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Vacancy;
use App\Service\Scraper\AbstractScraperService;
use App\Service\Scraper\ScraperChainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SyncCategoriesCommand extends Command
{
    protected static $defaultName = 'app:sync:categories';

    private ScraperChainService $chain;

    private EntityManagerInterface $em;

    public function __construct(ScraperChainService $chain, EntityManagerInterface $em, string $name = null)
    {
        $this->chain = $chain;
        $this->em = $em;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Sync (insert/update) Category entity with allowed vacancy categories');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $definedCategories = $this->getDefinedCategories();
        $allowedCategories = Vacancy::getCategories();

        $synced = 0;

        foreach ($definedCategories as $definedCategory) {
            if (array_key_exists($definedCategory, $allowedCategories)) {
                $repository = $this->em->getRepository(Category::class);
                $category = $repository->findOneBy(['slug' => $definedCategory]) ?? new Category();

                if ($allowedCategories[$definedCategory] === $category->getName()) {
                    continue;
                }

                ++$synced;

                $category->setSlug($definedCategory);
                $category->setName($allowedCategories[$definedCategory]);
                $this->em->persist($category);
            }
        }

        $this->em->flush();

        $io->success('Successfully synced all categories.');
        $io->note("Synced categories: $synced");

        return 0;
    }

    private function getDefinedCategories()
    {
        $categories = [];

        $scrapers = $this->chain->getScrapers();

        /** @var AbstractScraperService $scraper */
        foreach ($scrapers as $scraper) {
            $categories_urls = $scraper->getCategoriesUrls();
            foreach ($categories_urls as $slug => $value) {
                $categories[] = $slug;
            }
        }

        return array_unique($categories);
    }
}
