<?php

namespace Admin\Controller\Traits\Routes;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\AbstractBaseTrait;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;

trait LinkUnlinkTrait
{
    use AbstractBaseTrait;

    abstract protected function handleLink($type, $subject, $link);
    abstract protected function handleUnlink($type, $link);

    /**
     * @ApiDoc
     */
    public function linkAction(Request $request, $id)
    {
        $subject = $this->_LinkUnlinkTrait__preCheck($request, $id);
        foreach($request->attributes->get('links') as $type => $links) {
            if(is_string($links)) {
                throw new NotFoundHttpException(sprintf('Subresource for "%s" not found', $links));
            }
            foreach($links as $link) {
                $this->handleLink($type, $subject, $link);
            }
        }

        $this->_LinkUnlinkTrait__save($subject);
    }

    /**
     * @ApiDoc
     */
    public function unlinkAction(Request $request, $id)
    {
        $subject = $this->_LinkUnlinkTrait__preCheck($request, $id);
        foreach($request->attributes->get('links') as $type => $links) {
            if(is_string($links)) {
                throw new NotFoundHttpException(sprintf('Subresource for "%s" not found', $links));
            }
            foreach($links as $link) {
                $this->handleUnlink($type, $subject, $link);
            }
        }

        $this->_LinkUnlinkTrait__save($subject);
    }

    private function _LinkUnlinkTrait__preCheck(Request $request, $id)
    {
        if(!$request->attributes->has('links')) {
            throw new BadRequestHttpException('Missing Link header');
        }

        $subject = $this->getFrontendManager()->getResource($id);
        if(!$this->getFrontendManager()->getAuthorizationChecker()->mayEdit($subject)) {
            throw new AccessDeniedException();
        }
        return $subject;
    }

    private function _LinkUnlinkTrait__save($obj)
    {
        // XXX: Fix ugly hack
        $repoRefl = new \ReflectionProperty(get_class($this->getFrontendManager()), "resourceManager");
        $repoRefl->setAccessible(true);
        $repo = $repoRefl->getValue($this->getFrontendManager());
        $repo->update($obj);
    }
}
