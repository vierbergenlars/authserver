<?php

namespace User\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

class EditEmailAddressType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('PUT')
            ->add('setPrimary', 'submit', array(
                'label'=>'Set as primary address',
                'attr' => array(
                    'class' => 'btn-link',
                )
            ))
            ->add('sendConfirmation', 'submit', array(
                'label' => 'Resend confirmation',
                'attr' => array(
                    'class' => 'btn-link',
                )
            ))
            ->add('remove', 'submit', array(
                'label' => 'Remove',
                'attr' => array(
                    'class' => 'btn-link',
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
        return 'usr_edit_email_address';
    }
}
