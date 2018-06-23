<?php
namespace Admin\EventListener;

use Admin\Event\DisplayListEvent;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;

abstract class AbstractDefaultDisplayListListener
{

    abstract protected function getClass();

    protected function getControllerName()
    {
        return substr(strrchr($this->getClass(), '\\'), 1);
    }

    private function isApplicable(DisplayListEvent $event)
    {
        return $event->getClass() === $this->getClass();
    }

    private function getTemplateReference($template)
    {
        return new TemplateReference('AdminBundle', $this->getControllerName(), 'cget/' . $template, 'html', 'twig');
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, 'add') !== 0) {
            throw new \BadMethodCallException($name . ' is not a valid method');
        }
        if (!$this->isApplicable($arguments[0]))
            return;
        $displayName = substr($name, 3);
        $templateName = lcfirst($displayName);
        $arguments[0]->addColumn($displayName, $this->getTemplateReference($templateName));
    }
}