<?php

namespace Admin\Controller;

use Admin\Form\BatchType;
use App\Search\SearchException;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NoRoute;
use FOS\RestBundle\Controller\Annotations\View as AView;
use JMS\Serializer\SerializationContext;
use Knp\Component\Pager\Paginator;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use vierbergenlars\Bundle\RadRestBundle\Controller\ControllerServiceController;
use vierbergenlars\Bundle\RadRestBundle\Controller\RadRestControllerInterface;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\DefaultsTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Pagination\KnpPaginationTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\CreateTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\DeleteTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\EditTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\ListTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\PatchTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\ViewTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routing\ClassResourceRoutingTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Serialization\ClassResourceSerializationGroupsTrait;
use vierbergenlars\Bundle\RadRestBundle\Manager\FrontendManager;
use vierbergenlars\Bundle\RadRestBundle\Manager\ResourceManagerInterface;
use vierbergenlars\Bundle\RadRestBundle\Twig\ControllerVariables;
use vierbergenlars\Bundle\RadRestBundle\View\View;

class DefaultController extends ControllerServiceController
{
    use ListTrait {
        ListTrait::cgetAction as private _LT_cgetAction;
    }
    use KnpPaginationTrait {
        KnpPaginationTrait::getPagination insteadof DefaultsTrait;
    }

    use DefaultsTrait;

    /**
     *
     * @var Paginator|null
     */
    private $paginator;

    /**
     * @var string
     */
    private $routePrefix;

    public function __construct(ResourceManagerInterface $resourceManager, FormTypeInterface $formType, FormFactoryInterface $formFactory, Paginator $paginator, $routePrefix)
    {
        parent::__construct($resourceManager, $formType, $formFactory);
        $this->paginator = $paginator;
        $this->routePrefix = $routePrefix;
    }

    /**
     * @AView
     * @ApiDoc(resource=true)
     * @Get(name="s")
     */
    public function cgetAction(Request $request)
    {
        if($request->query->has('q')) {
            try {
                $data = $this->getResourceManager()->search($request->query->get('q'));
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
    
    /**
     * @Post
     */
    public function batchAction(Request $request)
    {
        $form = $this->getFormFactory()->create(new BatchType($this->getBatchActions()));
        $form->handleRequest($request);
        if($form->isValid()) {
            $subjects = $form->get('subjects')->getData();
            $this->handleBatch($form->get('action')->getData(), $subjects);
        }
        return $this->redirectTo('cget', $request->query->get('_params', array()));
    }

    protected function handleBatch($action, $subjects) {
        if($action === 'DELETE') {
            foreach($subjects as $id => $checked) {
                if($checked) {
                    $this->getResourceManager()->delete($this->getResourceManager()->find($id));
                }
            }
        } else if(substr($action, 0, 6) === 'PATCH_') {
            list($patch, $property, $value) = explode('_', $action);
            foreach($subjects as $id => $checked) {
                if($checked) {
                    $group = $this->getResourceManager()->find($id);
                    $method = 'set'.ucfirst($property);
                    $group->$method($value === 'false'?false:$value);
                    $this->getResourceManager()->update($group);
                }
            }
        }
    }
    
    protected function getBatchActions() {
        return array(
            'DELETE' => 'Delete',
        );
    }
    
    /**
     * @AView
     * @ApiDoc(resource=true)
     * @Get
     */
    public function getAction($id)
    {
        return parent::getAction($id);
    }

    /**
     * @AView
     * @NoRoute
     */
    public function dashboardAction()
    {
        return $this->handleView(View::create($this->getResourceManager()->getPageDescription()->getSlice(0, 15)));
    }

    public function getRouteName($action)
    {
        return $this->routePrefix.'_'.($action=='cget'?'gets':$action);
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
            'batch_form' => $this->getFormFactory()->create(new BatchType($this->getBatchActions()))->createView(),
        ));
        $view->getSerializationContext()->enableMaxDepthChecks();
        return $view;
    }
}
