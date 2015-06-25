<?php

namespace App\Form\Type;

use App\Entity\Property;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserPropertyListener implements EventSubscriberInterface
{
    private $forceEditable = false;

    public function __construct($forceEditable)
    {
        $this->forceEditable = $forceEditable;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }

    public function preSetData(FormEvent $ev)
    {
        if(!$ev->getData())
            return;
        $property = $ev->getData()->getProperty();
        /* @var $property Property */
        $options = array(
            'label' => $property->getDisplayName(),
            'required' => $property->isRequired()&&!$this->forceEditable,
            'empty_data' => null,
        );
        if ($property->isRequired()&&!$this->forceEditable) {
            $options['constraints'][] = new NotBlank;
        }
        $options['constraints'][] = new Regex($property->getValidationRegex());
        $ev->getForm()->add('data', $this->forceEditable||$property->isUserEditable()?'text':'bs_static', $options);
    }
}
