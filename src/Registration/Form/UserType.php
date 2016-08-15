<?php
/**
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) $today.date  Lars Vierbergen
 *
 * his program is free software: you can redistribute it and/or modify
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Registration\Form;

use App\Entity\User;
use Registration\Form\Constraint\EmailSelfRegistration;
use Registration\Form\DataTransformer\PrimaryEmailAddressToStringTransformer;
use Registration\RegistrationHandler\RegistrationRules;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    /**
     * @var \Registration\RegistrationHandler\RegistrationRules
     */
    private $registrationRules;

    /**
     * UserType constructor.
     *
     * @param RegistrationRules $registrationRules
     */
    public function __construct(RegistrationRules $registrationRules)
    {

        $this->registrationRules = $registrationRules;
    }
    
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class)
            ->add('displayName', TextType::class)
            ->add('password', RepeatedType::class, array(
                'type' => PasswordType::class,
                'constraints' => array(
                    new NotBlank(),
                    new Length(array('min'=>8)),
                ),
                'first_options' => array(
                    'label' => 'Password',
                ),
                'second_options' => array(
                    'label' => 'Repeat password',
                ),
                'invalid_message' => 'Your repeated password does not match',
            ))
            ->add('emailAddresses', EmailType::class, array(
                'constraints' => array(
                    new EmailSelfRegistration(['registrationRules'=>$this->registrationRules]),
                ),
            ))
            ->add('submit', SubmitType::class)
        ;
        $builder->get('emailAddresses')->addModelTransformer(new PrimaryEmailAddressToStringTransformer());
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
        return 'registration_user';
    }

}
