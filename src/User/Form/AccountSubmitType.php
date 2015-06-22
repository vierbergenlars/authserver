<?php

namespace User\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AccountSubmitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('user', 'app_username')
            ->add('submit', 'submit')
        ;
    }

    public function getName()
    {
        return 'usr_account_resend_verification';
    }
}
