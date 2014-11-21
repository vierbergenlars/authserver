<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserToUsernameTransformer implements DataTransformerInterface
{
    /**
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    private $repo;

    /**
     *
     * @param \Doctrine\ORM\EntityRepository $repo
     */
    public function __construct(EntityRepository $repo) {
        $this->repo = $repo;
    }

    public function transform($user)
    {
        if(null === $user) {
            return '';
        }

        if(is_string($user)) {
            return $user;
        }

        return $user->getUsername();
    }

    public function reverseTransform($value)
    {
        if(!$value)
            return null;
        $user = $this->repo->findOneByUsername($value);
        if(null === $user) {
            throw new TransformationFailedException('User does not exist '.$value);
        }
        return $user;
    }
}