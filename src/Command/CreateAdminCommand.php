<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CreateAdminCommand extends Command
{
    protected static $defaultName = 'app:create:admin';

    private UserPasswordEncoderInterface $encoder;

    private EntityManagerInterface $em;

    public function __construct(UserPasswordEncoderInterface $encoder, EntityManagerInterface $em, string $name = null)
    {
        $this->encoder = $encoder;
        $this->em = $em;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Create an admin user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $helper = $this->getHelper('question');
        $question = new Question('Set an email for admin user: ');

        $email = $helper->ask($input, $output, $question);

        if ($this->em->getRepository(User::class)->findOneBy(['email' => $email])) {
            throw new \Exception('There is already a user with this email.');
        }

        $apikey = Uuid::uuid1();
        $password = $apikey;

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->encoder->encodePassword($user, $password));
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsVerified(true);
        $user->setApiKey($apikey);

        $this->em->persist($user);
        $this->em->flush();

        $io->success('Successfully created a new admin user.');
        $io->note("Your API Key is: $apikey");

        return 0;
    }
}
