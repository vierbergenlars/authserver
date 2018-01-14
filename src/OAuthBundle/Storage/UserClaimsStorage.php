<?php
namespace OAuthBundle\Storage;

use Doctrine\ORM\EntityManagerInterface;
use OAuth2\OpenID\Storage\UserClaimsInterface;

class UserClaimsStorage implements UserClaimsInterface
{

    /**
     *
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getUserClaims($user_id, $scope)
    {
        $user = $this->em->getRepository('AppBundle:User')->findOneBy([
            'guid' => $user_id
        ]);
        if (!$user) {
            return false;
        }

        $claims = explode(' ', $scope);
        /* @var $user \App\Entity\User */

        return [
            'sub' => $user->getGuid(),
            'name' => in_array('profile', $claims) ? $user->getDisplayName() : null,
            'preferred_username' => in_array('profile', $claims) ? $user->getUsername() : null,
            'email' => in_array('email', $claims) && $user->getPrimaryEmailAddress() ? $user->getPrimaryEmailAddress()->getEmail() : null,
            'email_verified' => in_array('email', $claims) && $user->getPrimaryEmailAddress() ? $user->getPrimaryEmailAddress()->isVerified() : null
        ];
    }
}
