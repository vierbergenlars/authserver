<?php

namespace App\Form\OAuth;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ClientType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('allowedGrantTypes', 'choice', array(
                'choices' => array(
                    'authorization_code' => 'authorization_code',
                ),
                'multiple'=>true,
                'expanded' => true,
            ))
            ->add('redirectUris', 'collection', array(
                'type' => 'text',
                'allow_add' => true,
                'allow_delete'=>true
            ))
            ->add('preApproved', 'checkbox', array(
                'required' => false,
            ))
            ->add('submit', 'submit')
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\OAuth\Client'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'app_oauth_client';
    }
}
