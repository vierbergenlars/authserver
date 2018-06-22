<?php
namespace Admin\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Admin\AdminEvents;
use Admin\Event\FilterListEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class DefaultFilterListListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            AdminEvents::FILTER_LIST => 'handleFilterList'
        ];
    }

    /**
     *
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function handleFilterList(FilterListEvent $event)
    {
        $event->getSearchFormBuilder()->add('search', SubmitType::class);
        $form = $event->getSearchForm();
        $form->handleRequest($this->requestStack->getCurrentRequest());
        if (!$form->isValid()) {
            $event->stopPropagation();
        }
    }
}