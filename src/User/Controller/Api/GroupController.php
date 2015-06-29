<?php

namespace User\Controller\Api;

use App\Entity\Group;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use User\SerializationHelper\Api\JoinableLeaveableGroups;

class GroupController extends BaseController implements ClassResourceInterface
{
    /**
     * @View
     */
    public function cgetAction()
    {
        if(!$this->isGrantedScope('group:join')&&!$this->isGrantedScope('group:leave'))
            throw $this->createAccessDeniedException('OAuth scope group:join or group:leave is required to access this resource.');
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
        $this->denyAccessUnlessGrantedScope('group:join');
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
        $this->denyAccessUnlessGrantedScope('group:leave');
        if($group->getNoUsers()||!$group->isUserLeaveable())
            throw $this->createAccessDeniedException('This group is not user leaveable');
        $user = $this->getUser();
        /* @var $user User */
        $user->getGroups()->removeElement($group);
        $this->getDoctrine()->getRepository('AppBundle:User')
             ->update($user);
    }
}
