<?php

namespace App\Command;

use App\Entity\Vacancy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VacancyClearCommand extends Command
{
    protected static $defaultName = 'app:vacancy:clear';

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, string $name = null)
    {
        $this->em = $em;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Deletes expired Vacancy entities')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $vacancyRepository = $this->em->getRepository(Vacancy::class);

        $expiredVacancies = $vacancyRepository->findExpiredVacancies();

        if (!$expiredVacancies) {
            $io->success("There's no expired vacancies.");
            return 0;
        }

        foreach ($expiredVacancies as $expiredVacancy) {
            $this->em->remove($expiredVacancy);
        }

        $this->em->flush();

        $io->success('Successfully removed ' . count($expiredVacancies) . ' expired vacancies.');

        return 0;
    }
}
