<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class HashToPasswordTransformer implements DataTransformerInterface
{
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function transform($value)
    {
        return '';
    }

    public function reverseTransform($value)
    {
        if(!$value)

            return null;

        return $this->encoderFactory->getEncoder('App\Entity\User')
            ->encodePassword($value, null);
    }
}
