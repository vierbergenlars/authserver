<?php

/*
 * This file is part of the FOSOAuthServerBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OAuthBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use FOS\OAuthServerBundle\Util\LegacyFormHelper;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 *
 * @author Chris Jones <leeked@gmail.com>
 */
class AuthorizeFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $hiddenType = HiddenType::class;

        $builder->add('client_id', $hiddenType);
        $builder->add('response_type', $hiddenType);
        $builder->add('redirect_uri', $hiddenType);
        $builder->add('state', $hiddenType);
        $builder->add('scope', $hiddenType);
        $builder->add('nonce', $hiddenType);
    }
}
