<?php
namespace App\Event;

use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * Interface for which all events that can be used with the twig functions event_render and event_send must implement
 *
 * @internal
 * @ignore
 */
interface TemplateEventInterface
{

    /**
     * Gets an iterator over the templates that are used
     *
     * @return Iterator<TemplateReferenceInterface>
     */
    public function getTemplates();

    /**
     * Gets the template data that will be passed to a template
     *
     * @param TemplateReferenceInterface $template
     * @return array
     */
    public function getTemplateData(TemplateReferenceInterface $template);
}