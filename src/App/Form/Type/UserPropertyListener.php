<?php

namespace App\Form\Type;

class UserPropertyListener implements \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    private $forceEditable = false;
    
    public function __construct($forceEditable) {
        $this->forceEditable = $forceEditable;
    }

    public static function getSubscribedEvents() {
        return array(
            \Symfony\Component\Form\FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }
    
    public function preSetData(\Symfony\Component\Form\FormEvent $ev) {
        $property = $ev->getData()->getProperty();
        /* @var $property \App\Entity\Property */
        $options = array(
            'label' => $property->getName(),
            'required' => $property->isRequired(),
            'empty_data' => null,
        );
        if($property->isRequired()) {
            $options['constraints'] = new \Symfony\Component\Validator\Constraints\NotNull;
        }
        $ev->getForm()->add('data', $this->forceEditable||$property->isUserEditable()?'text':'bs_static', $options);
    }
}
