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

namespace Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
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
