<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class HashToNoPasswordTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        if($value === null) {
            return ['setPassword' => 'overwrite'];
        } else if($value === '!') {
            return ['setPassword'=>'clear'];
        } else {
            return ['setPassword'=>'keep', 'password'=>$value];
        }
    }

    public function reverseTransform($value)
    {
        switch($value['setPassword']) {
            case 'clear':
                return '!';
            case 'keep':
                return null;
            case 'overwrite':
                return $value['password'];
        }
    }
}