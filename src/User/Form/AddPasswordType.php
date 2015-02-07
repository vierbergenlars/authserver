<?php

namespace User\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('password', 'repeated', array(
                'type' => 'password',
                'constraints' => array(
                    new NotBlank(),
                    new Length(array('min'=>8)),
                ),
                'first_options' => array(
                    'label' => 'New password',
                ),
                'second_options' => array(
                    'label' => 'Repeat new password',
                ),
                'invalid_message' => 'Your repeated password does not match',
            ))
            ->add('submit', 'submit')
        ;
    }

    public function getName()
    {
        return 'usr_passwd_change';
    }
}