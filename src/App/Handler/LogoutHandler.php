<?php

namespace App\Handler;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutHandler implements LogoutHandlerInterface {
    
    /**
     * @var EntityManager
     */
    private $manager;
    
    
    function __construct(EntityManager $manager) {
        $this->manager = $manager;
    }

    public function logout(Request $request, Response $response, TokenInterface $token) {
        $user = $token->getUser();
        $this->manager->beginTransaction();
        $this->manager->getRepository('AppBundle:OAuth\\RefreshToken')
                ->createQueryBuilder('t')
                ->delete()
                ->where('t.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->execute();
        $this->manager->getRepository('AppBundle:OAuth\\AccessToken')
                ->createQueryBuilder('t')
                ->delete()
                ->where('t.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->execute();
        $this->manager->commit();
    }    
}
