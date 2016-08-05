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

namespace App\Form\Property;

use App\Entity\OAuth\Client;
use App\Entity\Property\PropertyNamespace;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PropertyNamespaceType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('publicReadable', CheckboxType::class, array(
                'required' => false,
                'attr' => array(
                    'align_with_widget' => true,
                    'help_text' => 'All OAuth applications and the user himself can read properties in this namespace.',
                ),
            ))
            ->add('publicWriteable', CheckboxType::class, array(
                'required' => false,
                'attr' => array(
                    'align_with_widget' => true,
                    'help_text' => 'All OAuth applications and the user himself can write properties in this namespace.',
                ),
            ))
            ->add('readers', null, array(
                'property' => 'name',
                'expanded' => true,
            ))
            ->add('writers', null, array(
                'property' => 'name',
                'expanded' => true,
            ))
        ;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => PropertyNamespace::class,
        ));
    }
}
