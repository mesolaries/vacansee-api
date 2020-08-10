<?php

namespace App\Service\Email;


use Swift_Mailer;
use Swift_Message;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class SendEmail
{
    private Swift_Mailer $mailer;

    private Environment $twig;

    public function __construct(Swift_Mailer $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * @param string $to     Email destination address
     *
     * @param array  $params Parameters to pass to the twig renderer
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendApikeyMessage(string $to, array $params)
    {
        $message = (new Swift_Message('Vacansee - API key'))
            ->setFrom('mnf.emil@gmail.com', 'Vacansee')
            ->setTo($to)
            ->setBody(
                $this->twig->render('registration/apikey_email.html.twig', $params),
                'text/html'
            );

        $this->mailer->send($message);
    }

    /**
     * @param string $to     Email destination address
     *
     * @param array  $params Parameters to pass to the twig renderer
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendForgotApikeyMessage(string $to, array $params)
    {
        $message = (new Swift_Message('Vacansee - Forgot your API key?'))
            ->setFrom('mnf.emil@gmail.com', 'Vacansee')
            ->setTo($to)
            ->setBody(
                $this->twig->render('registration/forgot_apikey_email.html.twig', $params),
                'text/html'
            );

        $this->mailer->send($message);
    }
}