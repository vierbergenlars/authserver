<?php

namespace App\Form\Type;

use App\Form\DataTransformer\HashToNoPasswordTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Password type that allows disabling of passwords
 */
class NoPasswordType extends AbstractType
{
    private $entryRequired;
    public function __construct($entryRequired) {
        $this->entryRequired = $entryRequired;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = array(
                'keep' => 'Keep password',
                'clear' => 'Clear password',
                'overwrite' => 'Set new password',
            );
        if($this->entryRequired)
            unset($choices['keep']);
        $builder->add('setPassword', 'choice', array(
            'choices' => $choices,
            'label'=>false,
            'expanded' => true,
        ));
        $builder->add('password', 'app_password', array(
            'label'=> false,
        ));
        $builder->addModelTransformer(new HashToNoPasswordTransformer());
    }
    
    public function getParent()
    {
        return 'form';
    }

    public function getName()
    {
        return 'app_nopassword';
    }
}
