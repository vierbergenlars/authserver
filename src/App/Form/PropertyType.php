<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PropertyType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('userEditable', null, array(
                'required' => false,
                'attr' => array(
                    'align_with_widget' => true,
                ),

            ))
            ->add('required', null, array(
                'required' => false,
                'attr' => array(
                    'align_with_widget' => true,
                ),

            ))
            ->add('submit', 'submit')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Property'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'app_property';
    }
}
