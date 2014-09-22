<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

class UserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('password')
            ->add('email')
            ->add('role', 'choice', array(
                'choices' => array(
                    'ROLE_USER' => 'User',
                    'ROLE_ADMIN' => 'Admin',
                    'ROLE_SUPER_ADMIN' => 'Super admin',
                ),
                'multiple'=>false,
                'expanded' => true,
            ))
            ->add('groups', null, array(
                'property'=>'name',
                'query_builder'=>function(EntityRepository $repo) {
                    return $repo->createQueryBuilder('g')
                    ->where('g.noUsers = false');
                },
                'required'=>false,
                'expanded'=>true,
            ))
            ->add('enabled', 'checkbox', array(
                'required' => false,
                'attr' => array(
                    'align_with_widget' => true,
                ),
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
            'data_class' => 'App\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'app_user';
    }
}
