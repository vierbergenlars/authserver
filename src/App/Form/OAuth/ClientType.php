<?php
/*
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015 Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace App\Form\OAuth;

use App\Entity\Group;
use App\Entity\GroupRepository;
use App\Entity\OAuth\Client;
use Braincrafted\Bundle\BootstrapBundle\Form\Type\BootstrapCollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use OAuth2\OAuth2;
use OAuthBundle\Service\OAuthScopes;
use OAuth2\Server;
use OAuth2\GrantType\GrantTypeInterface;

class ClientType extends AbstractType
{

    /**
     *
     * @var OAuthScopes
     */
    private $scopes;

    /**
     *
     * @var Server
     */
    private $server;

    public function __construct(OAuthScopes $scopes, Server $server)
    {
        $this->scopes = $scopes;
        $this->server = $server;
    }

    /**
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $allScopes = $this->scopes->getSupportedScopes();
        $scopes = array_combine($allScopes, $allScopes);
        $allGrantTypes = array_map(function (GrantTypeInterface $grantType) {
            return $grantType->getQueryStringIdentifier();
        }, $this->server->getGrantTypes()) + [
            'implicit'
        ];
        $grantTypes = array_combine($allGrantTypes, $allGrantTypes);

        $builder->add('name', TextType::class)
            ->add('redirectUris', BootstrapCollectionType::class, array(
            'entry_type' => TextType::class,
            'allow_add' => true,
            'allow_delete' => true
        ))
            ->add('preApproved', CheckboxType::class, array(
            'required' => false,
            'attr' => array(
                'align_with_widget' => true
            )
        ))
            ->add('preApprovedScopes', ChoiceType::class, array(
            'choices' => $scopes,
            'multiple' => true,
            'expanded' => true
        ))
            ->add('groupRestriction', EntityType::class, array(
            'class' => Group::class,
            'query_builder' => function (GroupRepository $repository) {
                return $repository->createQueryBuilder('g')
                    ->where('g.exportable = true');
            },
            'choice_label' => function (Group $group) {
                return sprintf('%s (%s)', $group->getDisplayName(), $group->getName());
            },
            'required' => false
        ))
            ->add('maxScopes', ChoiceType::class, array(
            'choices' => $scopes,
            'multiple' => true,
            'expanded' => true
        ))
            ->add('allowedGrantTypes', ChoiceType::class, array(
            'choices' => $grantTypes,
            'multiple' => true,
            'expanded' => true
        ))
            ->add('submit', SubmitType::class);
    }

    /**
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Client::class
        ));
    }
}
