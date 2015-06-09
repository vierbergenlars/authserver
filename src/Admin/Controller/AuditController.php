<?php

namespace Admin\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\View as AView;
use Knp\Component\Pager\Paginator;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use vierbergenlars\Bundle\RadRestBundle\Controller\RadRestControllerInterface;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\DefaultsTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Pagination\KnpPaginationTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\ListTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\ViewTrait;
use vierbergenlars\Bundle\RadRestBundle\Manager\ResourceManagerInterface;
use vierbergenlars\Bundle\RadRestBundle\Twig\ControllerVariables;
use vierbergenlars\Bundle\RadRestBundle\View\View;

class AuditController implements RadRestControllerInterface
{
    use ListTrait {
        ListTrait::cgetAction as private _LT_cgetAction;
    }
    use ViewTrait;
    use DefaultsTrait;
    use KnpPaginationTrait {
        KnpPaginationTrait::getPagination insteadof DefaultsTrait;
    }

    private $resourceManager;
    private $paginator;

    public function __construct(ResourceManagerInterface $resourceManager,Paginator $paginator)
    {
        $this->resourceManager = $resourceManager;
        $this->paginator = $paginator;
    }

    /**
     * @AView
     * @ApiDoc(resource=true)
     * @Get(name="s")
     */
    public function cgetAction(Request $request)
    {
        return $this->_LT_cgetAction($request);
    }

    public function getResourceManager()
    {
        return $this->resourceManager;
    }

    public function getRouteName($action)
    {
        return 'admin_audit_'.($action=='cget'?'gets':$action);
    }

    protected function handleView(View $view)
    {
        $view->setExtraData(array(
            'controller' => new ControllerVariables($this),
        ));

        return $view;
    }

    /**
     * @return FormTypeInterface
     */
    public function getFormType()
    {
        return null;
    }

    /**
     * @return FormFactoryInterface
     */
    protected function getFormFactory()
    {
        return null;
    }

    /**
     * Returns a list of serializer groups for the given action on this controller
     *
     * @param string $action
     *
     * @return string[] Serialization groups for this action
     */
    public function getSerializationGroups($action)
    {
        return array('_');
    }

    /**
     * Gets the paginator to use for pagination
     * @return Paginator
     */
    protected function getPaginator()
    {
        return $this->paginator;
    }


}