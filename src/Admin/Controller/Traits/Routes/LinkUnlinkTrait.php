<?php

namespace Admin\Controller\Traits\Routes;

use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\AbstractBaseTrait;
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
        foreach ($request->attributes->get('links') as $type => $links) {
            if (is_string($links)) {
                throw new NotFoundHttpException(sprintf('Subresource for "%s" not found', $links));
            }
            foreach ($links as $link) {
                $this->handleLink($type, $subject, $link);
            }
        }

        $this->getResourceManager()->update($subject);
    }

    /**
     * @ApiDoc
     */
    public function unlinkAction(Request $request, $id)
    {
        $subject = $this->_LinkUnlinkTrait__preCheck($request, $id);
        foreach ($request->attributes->get('links') as $type => $links) {
            if (is_string($links)) {
                throw new NotFoundHttpException(sprintf('Subresource for "%s" not found', $links));
            }
            foreach ($links as $link) {
                $this->handleUnlink($type, $subject, $link);
            }
        }

        $this->getResourceManager()->update($subject);
    }

    private function _LinkUnlinkTrait__preCheck(Request $request, $id)
    {
        if (!$request->attributes->has('links')) {
            throw new BadRequestHttpException('Missing Link header');
        }

        return $this->getResourceManager()->find($id);
    }
}
