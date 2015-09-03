<?php

namespace App\Form\OAuth;

use App\Entity\GroupRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ClientType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $scopes = array(
            'profile:username' => 'profile:username',
            'profile:realname' => 'profile:realname',
            'profile:groups'   => 'profile:groups',
            'group:join'       => 'group:join',
            'group:leave'      => 'group:leave',
        );
        $builder
            ->add('name')
            ->add('redirectUris', 'bootstrap_collection', array(
                'type' => 'text',
                'allow_add' => true,
                'allow_delete'=>true
            ))
            ->add('preApproved', 'checkbox', array(
                'required' => false,
                'attr' => array(
                    'align_with_widget' => true,
                ),
            ))
            ->add('preApprovedScopes', 'choice', array(
                'choices' => $scopes,
                'multiple' => true,
                'expanded' => true,
            ))
            ->add('groupRestriction', 'entity', array(
                'class' => 'AppBundle:Group',
                'query_builder' => function(GroupRepository $repository) {
                    return $repository->createQueryBuilder('g')->where('g.exportable = true');
                },
                'property' => 'name',
                'required' => false,
            ))
            ->add('maxScopes', 'choice', array(
                'choices' => $scopes,
                'multiple' => true,
                'expanded' => true,
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
