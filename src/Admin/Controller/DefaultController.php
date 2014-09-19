<?php

namespace Admin\Controller;

use vierbergenlars\Bundle\RadRestBundle\Controller\ControllerServiceController;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Pagination\KnpPaginationTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routing\ClassResourceRoutingTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Serialization\ClassResourceSerializationGroupsTrait;
use Knp\Component\Pager\Paginator;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\DefaultsTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\ListTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\ViewTrait;
use vierbergenlars\Bundle\RadRestBundle\Manager\FrontendManager;
use vierbergenlars\Bundle\RadRestBundle\Controller\RadRestControllerInterface;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\CreateTrait;
use FOS\RestBundle\Controller\Annotations\View as AView;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\EditTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\DeleteTrait;
use vierbergenlars\Bundle\RadRestBundle\View\View;
use vierbergenlars\Bundle\RadRestBundle\Twig\ControllerVariables;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Search\SearchException;

class DefaultController implements RadRestControllerInterface
{
    use ListTrait {
        ListTrait::cgetAction as private _LT_cgetAction;
    }
    use CreateTrait;
    // Note: ViewTrait is below CreateTrait to avoid calling ViewTrait::getAction('new') instead of CreateTrait::newAction()
    use ViewTrait;
    use EditTrait;
    use DeleteTrait;
    use DefaultsTrait;
    use KnpPaginationTrait {
        KnpPaginationTrait::getPagination insteadof DefaultsTrait;
    }

    /**
     * @var FrontendManager
     */
    private $frontendManager;

    /**
     *
     * @var Paginator|null
     */
    private $paginator;

    /**
     * @var string
     */
    private $routePrefix;

    /**
     *
     * @param FrontendManager $frontendManager
     * @param Paginator $paginator
     * @param string $routePrefix
     */
    public function __construct(FrontendManager $frontendManager, Paginator $paginator, $routePrefix)
    {
        $this->frontendManager = $frontendManager;
        $this->paginator = $paginator;
        $this->routePrefix = $routePrefix;
    }

    public function getFrontendManager()
    {
        return $this->frontendManager;
    }

    /**
     * @AView
     * @ApiDoc(resource=true)
     * @Get("", name="s")
     */
    public function cgetAction(Request $request)
    {
        if($request->query->has('q')) {
            try {
                $data = $this->getFrontendManager()->search($request->query->get('q'));
                $view = View::create($this->getPagination($data, $request->query->get('page', 1)));
                $view->getSerializationContext()->setGroups($this->getSerializationGroups('cget'));
                return $this->handleView($view);
            } catch(SearchException $ex) {
                throw new BadRequestHttpException($ex->getMessage(), $ex);
            }
        } else {
            return $this->_LT_cgetAction($request);
        }
    }

    public function getRouteName($action)
    {
        if($action == 'cget') {
            $action = 'gets';
        }
        return $this->routePrefix.'_'.$action;
    }

    public function getSerializationGroups($action)
    {
        switch($action) {
            case 'cget':
                return array($this->routePrefix.'_list', 'list');
            case 'get':
                return array($this->routePrefix.'_object', 'object');
            default:
                return array('_');
        }
    }

    protected function getPaginator()
    {
        return $this->paginator;
    }

    protected function handleView(View $view)
    {
        $view->setExtraData(array(
            'controller' => new ControllerVariables($this),
        ));
        return $view;
    }
}