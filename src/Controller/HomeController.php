<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\Email\SendEmail;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class HomeController extends AbstractController
{
    private SendEmail $email;

    private UserPasswordEncoderInterface $encoder;

    public function __construct(SendEmail $email, UserPasswordEncoderInterface $encoder)
    {
        $this->email = $email;
        $this->encoder = $encoder;
    }

    /**
     * @Route("/", name="app.home")
     * @param Request $request
     *
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function home(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $doctrine = $this->getDoctrine();

            $userRepository = $doctrine->getRepository(User::class);

            $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);

            // Send user's API key to his email address if user already exists
            if ($existingUser) {
                $this->email->sendForgotApikeyMessage(
                    $existingUser->getEmail(),
                    ['apikey' => $existingUser->getApiKey()]
                );

                $this->addFlash(
                    'info',
                    "We've a user with this email. If it's you and you forgot your API key, please check your email."
                );

                return $this->redirectToRoute('app.home');
            }

            // Create a new user
            $user->setApiKey(Uuid::uuid1());
            $user->setPassword($this->encoder->encodePassword($user, $user->getApiKey()));
            $user->setRoles(['ROLE_ALLOWED']);

            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // Send new user his new API key
            $this->email->sendApikeyMessage($user->getEmail(), ['apikey' => $user->getApiKey()]);

            $this->addFlash(
                'success',
                "We've sent your API key to the email you've specified."
            );

            return $this->redirectToRoute('app.home');
        }

        return $this->render(
            'home/index.html.twig',
            [
                'registrationForm' => $form->createView(),
            ]
        );
    }
}
