<?php

namespace User\Form;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AddGroupType extends AbstractType
{
    private $user;

    function __construct(User $user)
    {
        $this->user = $user;
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->user;
        $builder
            ->add('group', 'entity', array(
                'property'=>'displayName',
                'class' => 'App\Entity\Group',
                'query_builder'=>function (EntityRepository $repo) use($user) {
                    $qb = $repo->createQueryBuilder('g')
                                ->where('g.noUsers = false AND g.userJoinable = true');
                    if($user->getGroups()->count() == 0)
                        return $qb;
                    return $qb->andWhere('g NOT IN(:groups)')
                                ->setParameter('groups', $user->getGroups());
                },
                'required'=>true,
                'multiple'=>false,
            ))
            ->add('submit', 'submit', array(
                'label' => 'Join group',
                'attr' => array(
                    'class' => 'btn-sm',
                )
            ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'usr_group_add';
    }
}
