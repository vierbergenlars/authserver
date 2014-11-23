<?php

namespace App\Mail;

/**
 * Sends emails with swiftmailer based on a twig template
 */
class TwigMailer
{
    private $twig;
    private $mailer;
    private $sender;

    /**
     * Create a new twig mailer
     * @param \Twig_Environment $twig The twig environment
     * @param \Swift_Mailer $mailer The swift mailer
     * @param string $sender The emailaddress the email originates from
     */
    public function __construct(\Twig_Environment $twig, \Swift_Mailer $mailer, $sender)
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->sender = $sender;
    }

    /**
     * Gets a template to use by string or template interface
     * @param string|\Twig_TemplateInterface $template
     * @return \Twig_TemplateInterface
     */
    public function getTemplate($template)
    {
        if(!$template instanceof \Twig_TemplateInterface) {
            $template = $this->twig->loadTemplate($template);
        }
        return $template;
    }

    /**
     * Sends a message to a recipient based on a template rendered with data
     *
     * The message subject and body in html and text will be rendered from blocks named subject, body_html and body_text.
     * @param string|\Twig_TemplateInterface $template
     * @param array $data
     * @param string $recipient
     * @return boolean
     */
    public function sendMessage($template, array $data, $recipient)
    {
        $template = $this->getTemplate($template);

        $subject = $template->renderBlock('subject', $data);
        $bodyHtml = $template->renderBlock('body_html', $data);
        $bodyText = $template->renderBlock('body_text', $data);

        $mail = \Swift_Message::newInstance($subject)
            ->setSender($this->sender)
            ->setFrom($this->sender)
            ->setBody($bodyText, 'text/plain')
            ->addPart($bodyHtml, 'text/html')
            ->setTo($recipient);
        return $this->mailer->send($mail) == 1;
    }
}