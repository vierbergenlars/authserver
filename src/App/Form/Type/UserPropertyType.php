<?php

namespace App\Form\Type;

use App\Entity\UserProperty;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserPropertyType extends AbstractType
{
    private $forceEditable = false;
    
    public function __construct($forceEditable) {
        $this->forceEditable = $forceEditable;
    }
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->addEventSubscriber(new UserPropertyListener($this->forceEditable));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\UserProperty'
        ));
    }

    public function getName() {
        return 'user_property';
    }    
}

