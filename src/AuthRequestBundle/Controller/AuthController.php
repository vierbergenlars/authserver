<?php

namespace AuthRequestBundle\Controller;

use App\Entity\Group;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AuthController extends Controller
{

    /**
     * @View
     */
    public function basicAction(Request $request)
    {
        if(!$this->has('auth_request.expressionlanguage'))
            throw $this->createNotFoundException('auth_request bundle is not enabled.');
        $user = $this->getUser();

        $cache = $this->get('cache.app');
        /* @var $appCache \Psr\Cache\CacheItemPoolInterface */

        $cacheKey = 'u_'.$user->getId().'-q_'.base64_encode($request->getQueryString());

        $cacheItem = $cache->getItem('auth_request_basic_'.$cacheKey);

        if($cacheItem->isHit()) {
            if($cacheItem->get()) {
                throw $this->createAccessDeniedException($cacheItem->get());
            } else {
                return new Response();
            }
        }
        try {

            if(!$user)
                throw $this->createAccessDeniedException();
            /* @var $user \App\Entity\User */

            if($request->query->has('groups')) {
                $requiredGroupNames = (array)$request->query->get('groups');
                $userGroups = $user->getGroupsRecursive();
                $exportableGroups = array_filter($userGroups, function(Group $group) {
                    return $group->isExportable();
                });
                $userGroupNames = array_map(function(Group $group) {
                    return $group->getName();
                }, $exportableGroups);
                $missingGroups = array_diff($requiredGroupNames, $userGroupNames);
                if(count($missingGroups) > 0) {
                    throw $this->createAccessDeniedException('User is not member of required groups: '.implode(', ', $missingGroups));
                }
            }

            if($request->query->has('eval')) {
                $elResult = false;
                try {
                    $elResult = $this->get('auth_request.expressionlanguage')
                        ->evaluate($request->query->get('eval'), ['user' => $user]);
                } catch(SyntaxError $ex) {
                    throw new BadRequestHttpException('Expression syntax error: ' . $ex->getMessage(), $ex);
                } catch(\Exception $ex) {
                    throw $this->createAccessDeniedException('Expression failed: ' . $ex->getMessage(), $ex);
                } catch(\Error $ex) {
                    throw $this->createAccessDeniedException('Expression failed: ' . $ex->getMessage(), new \RuntimeException($ex->getMessage(), $ex->getCode(), $ex));
                }

                if(!$elResult)
                    throw $this->createAccessDeniedException('User does not match expression: '. $request->query->get('eval'));
            }
        $cacheItem->set(null);
        } catch(AccessDeniedException $e) {
            $cacheItem->set($e->getMessage());
            throw $e;
        } finally {
            $cacheItem->expiresAfter(60*15); // Cache 15 minutes
            $cache->save($cacheItem);
        }

        return new Response();
    }
}
