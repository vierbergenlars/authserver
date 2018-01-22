<?php
namespace App\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * UserRepository
 */
class UserRepository extends EntityRepository implements UserLoaderInterface
{

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username
     *            The username
     *
     * @return UserInterface
     *
     * @see UsernameNotFoundException
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        if (preg_match('/^[0-9A-Fa-f]{8}-([0-9A-Fa-f]{4}-){3}[0-9A-Fa-f]{12}$/<', $username)) {
            $user = $this->findOneBy([
                'guid' => $username
            ]);
            if ($user) {
                return $user;
            } else {
                throw new UsernameNotFoundException();
            }
        }
        $user = $this->findOneBy([
            'username' => $username
        ]);
        if ($user)
            return $user;
        $email = $this->_em->getRepository('AppBundle:EmailAddress')->findOneBy([
            'email' => $username
        ]);
        if ($email)
            return $email->getUser();
        throw new UsernameNotFoundException();
    }
}
