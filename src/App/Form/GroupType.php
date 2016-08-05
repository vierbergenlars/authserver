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
use Braincrafted\Bundle\BootstrapBundle\Form\Type\FormStaticControlType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class GroupType extends AbstractType
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
            // Disable editing of the name after the initial submission
            // If the id is set, the form is used for an edit operation
            ->add('name',  $id > 0?(FormStaticControlType::class):(TextType::class))
            ->add('displayName', TextType::class)
            ->add('groups', null, array(
                'label'=>'Member of',
                'query_builder'=>function (EntityRepository $repo) use ($id) {
                    return $repo->createQueryBuilder('g')
                        ->leftJoin('g.memberGroups', 'm')
                        ->where('g.noGroups = false OR m.id = :id')
                        ->andWhere('g.id != :id')
                        ->setParameter('id', $id);
                },
                'choice_label'=>'name',
                'required'=>false,
                'expanded' => true,
            ))
            ->add('exportable', CheckboxType::class, array(
                'required' => false,
                'attr' => array(
                    'align_with_widget' => true,
                    'help_text' => 'This group is visible through OAuth',
                ),
            ))
            ->add('userJoinable', CheckboxType::class, array(
                'required' => false,
                'attr' => array(
                    'align_with_widget' => true,
                    'help_text' => 'A user can join this group from his profile',
                ),
            ))
            ->add('userLeaveable', CheckboxType::class, array(
                'required' => false,
                'attr' => array(
                    'align_with_widget' => true,
                    'help_text' => 'A user can leave this group from his profile',
                ),
            ))

            ->add('noGroups', CheckboxType::class, array(
                'required' => false,
                'label'=>'No groups can be member of this group',
                'attr' => array(
                    'align_with_widget' => true,
                ),
            ))
            ->add('noUsers', CheckboxType::class, array(
                'required' => false,
                'label'=> 'No users can be member of this group',
                'attr' => array(
                    'align_with_widget' => true,
                ),
            ))
            ->add('submit', SubmitType::class)
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Group::class,
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'app_group';
    }
}
