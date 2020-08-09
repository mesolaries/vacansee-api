<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Swift_Mailer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\WrongEmailVerifyException;

class HomeController extends AbstractController
{
    private $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    /**
     * @Route("/", name="app.home")
     * @param Request      $request
     *
     * @param Swift_Mailer $mailer
     *
     * @return Response
     */
    public function register(Request $request, Swift_Mailer $mailer): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $doctrine = $this->getDoctrine();

            $userRepository = $doctrine->getRepository(User::class);
            $userExists = $userRepository->findOneBy(['email' => $user->getEmail()]);

            if ($userExists) {
                $user = $userExists;
                if ($user->isVerified()) {
                    $message = (new \Swift_Message('Vacansee'))
                        ->setFrom('mnf.emil@gmail.com', 'Vacansee')
                        ->setTo($user->getEmail())
                        ->setBody(
                            $this->renderView(
                                'registration/forgot_apikey_email.html.twig',
                                ['apikey' => $user->getApiKey()]
                            ),
                            'text/html'
                        );

                    $mailer->send($message);

                    $this->addFlash(
                        'user_exists_info',
                        "We've a user with this email. If it's you and you forgot your API Key, please check your email."
                    );
                }
            } else {
                $entityManager = $doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
            }

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('mnf.emil@gmail.com', 'Vacansee API'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            // do anything else you need here, like send an email

            return $this->redirectToRoute('api_doc');
        }

        return $this->render(
            'home/index.html.twig',
            [
                'registrationForm' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/verify/email", name="app_verify_email")
     * @param Request $request
     *
     * @return Response
     */
    public function verifyUserEmail(Request $request): Response
    {
        $email = $request->query->get('email');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $email]);

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            if (!$user) {
                throw new WrongEmailVerifyException();
            }
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('app.home');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app.home');
    }
}
