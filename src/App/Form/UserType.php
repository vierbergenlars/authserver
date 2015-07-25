<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserType extends AbstractType
{
    private $subscriber;
    public function __construct(EventSubscriberInterface $subscriber)
    {
        $this->subscriber = $subscriber;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(isset($options['data']))
            $id = $options['data']->getMigrateId()?:0;
        else
            $id = 0;

        $builder
            ->add('username', 'text', array(
                // Disable editing of the name after the initial submission
                // If the id is set, the form is used for an edit operation
                'disabled' => $id > 0
            ))
            ->add('displayName')
            ->add('password', 'app_password', array(
                'required'=>false,
            ))
            ->add('emailAddresses', 'bootstrap_collection', array(
                'type' => new EmailAddressType(),
                'allow_add' => true,
                'allow_delete'=>true
            ))
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
                'query_builder'=>function(EntityRepository $repo) use($id) {
                    return $repo->createQueryBuilder('g')
                        ->leftJoin('g.members', 'm')
                        ->where('g.noUsers = false OR m.id = :id')
                        ->setParameter('id', $id);
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
            ->addEventSubscriber($this->subscriber)
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
