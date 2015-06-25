<?php

namespace User\Controller\Api;

use App\Entity\Group;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Tests\LazyArrayCollection;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use User\SerializationHelper\Api\JoinableLeaveableGroups;
use FOS\RestBundle\Controller\Annotations\Get;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations\Patch;

class GroupController extends Controller implements ClassResourceInterface
{
    /**
     * @View
     */
    public function cgetAction()
    {
        if(!$this->isGranted('ROLE_GROUP:JOIN')&&!$this->isGranted('ROLE_GROUP:LEAVE'))
            throw $this->createAccessDeniedException();
        $user = $this->getUser();
        /* @var $user User */
        $leaveable = $user->getGroups()->filter(function(Group $g) {
            return $g->isUserLeaveable();
        });

        $repo = $this->getDoctrine()->getRepository('AppBundle:Group');
        /* @var $repo EntityRepository */
        $joinableQb = $repo->createQueryBuilder('g')
            ->where('g.userJoinable = true')
            ->andWhere('g.noUsers = false');
        if($user->getGroups()->count())
            $joinableQb->andWhere('g NOT IN(:groups)')
                ->setParameter('groups', $user->getGroups());
        $joinable = $joinableQb->getQuery()
            ->execute();

        return \FOS\RestBundle\View\View::create(new JoinableLeaveableGroups($joinable, $leaveable))
            ->setSerializationContext(SerializationContext::create()->setGroups(array('oauth_api')));
    }

    /**
     * @View
     * @Patch("groups/join/{id}")
     */
    public function joinAction(Group $group)
    {
        $this->denyAccessUnlessGranted('ROLE_GROUP:JOIN');
        if($group->getNoUsers()||!$group->isUserJoinable())
            throw $this->createAccessDeniedException('This group does not accept users, or is not user joinable');
        $user = $this->getUser();
        /* @var $user User */
        if($user->getGroups()->contains($group))
            return;
        $user->getGroups()->add($group);
        $this->getDoctrine()->getRepository('AppBundle:User')
            ->update($user);
    }

    /**
     * @View
     * @Patch("groups/leave/{id}")
     */
    public function leaveAction(Group $group)
    {
        $this->denyAccessUnlessGranted('ROLE_GROUP:LEAVE');
        if($group->getNoUsers()||!$group->isUserLeaveable())
            throw $this->createAccessDeniedException('This group is not user leaveable');
        $user = $this->getUser();
        /* @var $user User */
        $user->getGroups()->removeElement($group);
        $this->getDoctrine()->getRepository('AppBundle:User')
             ->update($user);
    }
}
