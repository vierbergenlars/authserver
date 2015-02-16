<?php

namespace Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ApiKeyType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('scopes', 'choice', array(
                'choices' => array(
                    'r_profile' => 'Profile::read',
                    'r_profile_email' => 'Profile::read::email',
                    'w_profile' => 'Profile::write',
                    'w_profile_email' => 'Profile::write::email',
                    'w_profile_cred' => 'Profile::write::password',
                    'w_profile_groups' => 'Profile::write::groups',
                    'w_profile_enabled' => 'Profile::write::lock',
                    'w_profile_enabled_admin'=>'Profile::write::lock::admins',
                    'w_profile_username' => 'Profile::write::username',
                    'w_profile_admin' => 'Profile::write::admin',
                    'r_group' => 'Group::read',
                    'w_group' => 'Group::write',
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
