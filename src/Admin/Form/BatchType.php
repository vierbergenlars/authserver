<?php

namespace Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;

class BatchType extends AbstractType
{
    private $choices;

    public function __construct($actions)
    {
        $this->choices = new SimpleChoiceList($actions);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subjects', 'collection', array(
                'type' => 'checkbox',
                'allow_add' => true,
                'required' => false,
            ))
            ->add('action', 'choice', array(
                'placeholder' => 'Select batch action',
                'choice_list' => $this->choices,
                'constraints' => new Choice(array('choices'=>$this->choices->getValues())),
            ))
            ->add('submit', 'submit')
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'admin_batch';
    }
}
