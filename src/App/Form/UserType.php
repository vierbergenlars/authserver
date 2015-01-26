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
        $id = $options['data']->getId()?:0;

        $builder
            // Disable editing of the name after the initial submission
            // If the id is set, the form is used for an edit operation
            ->add('username',  $id > 0?'bs_static':'text')
            ->add('displayName')
            ->add('password', new Type\NoPasswordType($id == 0), array(
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
