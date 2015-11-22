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

namespace App\Form\OAuth;

use App\Entity\GroupRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                'choice_label' => 'name',
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
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
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
