<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Form\FormBuilderInterface;
use App\Form\DataTransformer\HashToPasswordTransformer;

/**
 * Password type that hashes the password
 */
class PasswordType extends AbstractType
{
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new HashToPasswordTransformer($this->encoderFactory));
    }

    public function getParent()
    {
        return 'password';
    }

    public function getName()
    {
        return 'app_password';
    }
}
