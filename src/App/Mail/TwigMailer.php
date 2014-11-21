<?php

namespace App\Mail;

class TwigMailer
{
    private $twig;
    private $mailer;
    private $sender;

    public function __construct(\Twig_Environment $twig, \Swift_Mailer $mailer, $sender)
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->sender = $sender;
    }

    public function sendMessage($template, array $data, $recipient)
    {
        $template = $this->twig->loadTemplate($template);

        $subject = $template->renderBlock('subject', $data);
        $bodyHtml = $template->renderBlock('body_html', $data);
        $bodyText = $template->renderBlock('body_text', $data);

        $mail = \Swift_Message::newInstance($subject)
            ->setSender($this->sender)
            ->setFrom($this->sender)
            ->setBody($bodyText, 'text/plain')
            ->addPart($bodyHtml, 'text/html')
            ->setTo($recipient);

        $this->mailer->send($mail);
    }
}