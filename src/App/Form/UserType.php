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

use App\Entity\Group;
use App\Entity\User;
use App\Form\Type\PasswordType;
use Braincrafted\Bundle\BootstrapBundle\Form\Type\BootstrapCollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(isset($options['data']))
            $id = $options['data']->getId()?:0;
        else
            $id = 0;

        $builder
            ->add('username', TextType::class)
            ->add('displayName', TextType::class)
            ->add('password', PasswordType::class, array(
                'required'=>false,
            ))
            ->add('passwordEnabled', ChoiceType::class, array(
                'label' => 'Password authentication',
                'choices' => array(
                    'Disabled' => 0,
                    'Enabled' => 1,
                    'Allow user to set initial password' => 2,
                ),
                'expanded' => true
            ))
            ->add('emailAddresses', BootstrapCollectionType::class, array(
                'entry_type' => EmailAddressType::class,
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete'=>true
            ))
            ->add('role', ChoiceType::class, array(
                'choices' => array(
                    'User' => 'ROLE_USER',
                    'Audit' => 'ROLE_AUDIT',
                    'Admin' => 'ROLE_ADMIN',
                    'Super admin' => 'ROLE_SUPER_ADMIN',
                ),
                'multiple'=>false,
                'expanded' => true,
            ))
            ->add('groups', null, array(
                'choice_label'=>'name',
                'query_builder'=>function (EntityRepository $repo) use ($id) {
                    return $repo->createQueryBuilder('g')
                        ->leftJoin('g.members', 'm')
                        ->where('g.noUsers = false OR m.id = :id')
                        ->setParameter('id', $id);
                },
                'required'=>false,
                'expanded'=>true,
            ))
            ->add('enabled', CheckboxType::class, array(
                'required' => false,
                'attr' => array(
                    'align_with_widget' => true,
                ),
            ))
            ->add('submit', SubmitType::class);
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => User::class,
        ));
    }

    /**
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'app_user';
    }

}
