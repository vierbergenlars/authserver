<?php

namespace User\Form;

use App\Form\Type\UserPropertyType;
use Symfony\Component\Form\FormBuilderInterface;

class EditUserPropertyType extends UserPropertyType {
    public function __construct() {
        parent::__construct(false);
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
        $builder->setMethod('PUT')
                ->add('submit', 'submit', array(
                    'attr' => array(
                        'class' => 'btn-sm',
                    ),
                ))
                ->add('close', 'button', array(
                    'attr' => array(
                        'class' => 'btn-sm btn-link',
                        'data-toggle' => 'collapse',
                    ),
                ));
    }
    
    public function getName() {
        return 'usr_edit_user_property';
    }    
}
