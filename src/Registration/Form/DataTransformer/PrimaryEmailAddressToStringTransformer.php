<?php

namespace Registration\Form\DataTransformer;

use App\Entity\EmailAddress;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PrimaryEmailAddressToStringTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        /* @var \Doctrine\Common\Collections\Collection|\App\Entity\EmailAddress[] $value */
        $primaryAddress = $value->filter(function(EmailAddress $emailAddress) {
            return $emailAddress->isPrimary();
        })->first();
        /* @var EmailAddress $primaryAddress */
        if(!$primaryAddress)
            return '';
        return $primaryAddress->getEmail();
    }

    public function reverseTransform($value)
    {
        $emailAddress = new EmailAddress();
        $emailAddress->setPrimary(true);
        $emailAddress->setEmail($value);
        $emailAddress->setVerified(false);
        return new ArrayCollection([$emailAddress]);
    }
}
