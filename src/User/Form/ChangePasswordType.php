<?php

namespace User\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currentPassword', 'password', array(
                'mapped' => false,
                'constraints' => new UserPassword(),
            ))
            ->add('password', 'app_password', array(
                'constraints' => array(
                    new NotBlank(),
                ),
                'label' => 'New password',
            ))
            ->add('submit', 'submit')
        ;
    }

    public function getName()
    {
        return 'usr_passwd_change';
    }
}