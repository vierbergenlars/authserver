<?php

namespace Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ApiKeyType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('scopes', 'choice', array(
                'choices' => array(
                    'r_profile' => 'Read-only user profile (no email)',
                    'r_profile_email' => 'Read user profile email',
                    'w_profile' => 'Write user profile',
                    'w_profile_email' => 'Write user profile email',
                    'w_profile_cred' => 'Write user profile password',
                    'w_profile_admin' => 'Write user profile (make users admin)',
                    'r_group' => 'Read-only group',
                    'w_group' => 'Write group',
                ),
                'expanded' => true,
                'multiple' => true,
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
            'data_class' => 'Admin\Entity\ApiKey'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'admin_apikey';
    }
}
