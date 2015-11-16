<?php
/* Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(isset($options['data']))
            $id = $options['data']->getMigrateId()?:0;
        else
            $id = 0;

        $builder
            ->add('username',  'text')
            ->add('displayName')
            ->add('password', 'app_password', array(
                'required'=>false,
            ))
            ->add('passwordEnabled', 'choice', array(
                'label' => 'Password authentication',
                'choices' => array(
                    0 => 'Disabled',
                    1 => 'Enabled',
                    2 => 'Allow user to set initial password',
                ),
                'expanded' => true
            ))
            ->add('emailAddresses', 'bootstrap_collection', array(
                'type' => new EmailAddressType(),
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete'=>true
            ))
            ->add('role', 'choice', array(
                'choices' => array(
                    'ROLE_USER' => 'User',
                    'ROLE_AUDIT' => 'Audit',
                    'ROLE_ADMIN' => 'Admin',
                    'ROLE_SUPER_ADMIN' => 'Super admin',
                ),
                'multiple'=>false,
                'expanded' => true,
            ))
            ->add('groups', null, array(
                'property'=>'name',
                'query_builder'=>function (EntityRepository $repo) use ($id) {
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
            ->add('submit', 'submit');
        if($this->subscriber)
            $builder->addEventSubscriber($this->subscriber)
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
