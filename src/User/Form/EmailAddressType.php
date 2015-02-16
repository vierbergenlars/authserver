<?php

namespace User\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EmailAddressType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('POST')
            ->add('email', 'text', array(
                'attr' => array(
                    'class' => 'input-sm',
                )
            ))
            ->add('submit', 'submit', array(
                'label' => 'Add email address',
                'attr' => array(
                    'class' => 'btn-sm',
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
            'data_class' => 'App\Entity\EmailAddress',
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'usr_new_email_address';
    }
}
