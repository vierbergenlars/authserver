<?php

namespace Admin\Controller;

use Admin\Form\BatchType;
use App\Search\SearchException;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NoRoute;
use FOS\RestBundle\Controller\Annotations\View as AView;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Paginator;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use vierbergenlars\Bundle\RadRestBundle\Controller\ControllerServiceController;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\DefaultsTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Pagination\KnpPaginationTrait;
use vierbergenlars\Bundle\RadRestBundle\Controller\Traits\Routes\ListTrait;
use vierbergenlars\Bundle\RadRestBundle\Manager\ResourceManagerInterface;
use vierbergenlars\Bundle\RadRestBundle\Pagination\PageDescriptionInterface;
use vierbergenlars\Bundle\RadRestBundle\Twig\ControllerVariables;
use vierbergenlars\Bundle\RadRestBundle\View\View;

class DefaultController extends ControllerServiceController
{
    use KnpPaginationTrait {
        KnpPaginationTrait::getPagination insteadof DefaultsTrait;
    }

    use DefaultsTrait {
        DefaultsTrait::processForm as private _DT_processForm;
    }

    /**
     * @NoRoute
     */
    public function patchAction(Request $request, $id)
    {
    }

    protected function processForm(FormInterface $form, Request $request)
    {
        if($form->getConfig()->getMethod() === 'DELETE' && !$form->has('_token')) {
            // Allow an API client to execute a DELETE without adding a request body
            if ($form->getData() !== null) {
                $request->request->add(array(
                    $form->getConfig()->getName() => array('submit' => null)
                ));
            } else {
                throw new NotFoundHttpException;
            }
        }
        return $this->_DT_processForm($form, $request);
    }

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
        if ($request->query->has('q')) {
            try {
                $data = $this->getResourceManager()->search($request->query->get('q'));
            } catch (SearchException $ex) {
                throw new BadRequestHttpException($ex->getMessage(), $ex);
            }
        } else {
            $data = $this->getResourceManager()->getPageDescription();
        }
        $pagination = $this->getPagination(
            $data,
            $request->query->get('page', 1),
            $request->query->get('per_page', 10));
        $view = View::create($pagination);
        $view->getSerializationContext()->setGroups($this->getSerializationGroups('cget'));

        return $this->handleView($view);

    }

    /**
     * @Post
     */
    public function batchAction(Request $request)
    {
        $form = $this->getFormFactory()->create(new BatchType($this->getBatchActions()));
        $form->handleRequest($request);
        if ($form->isValid()) {
            $subjects = $form->get('subjects')->getData();
            $this->handleBatch($form->get('action')->getData(), $subjects);
        }

        return $this->redirectTo('cget', $request->query->get('_params', array()));
    }

    protected function handleBatch($action, $subjects)
    {
        if ($action === 'DELETE') {
            foreach ($subjects as $id => $checked) {
                if ($checked) {
                    $this->getResourceManager()->delete($this->getResourceManager()->find($id));
                }
            }
        } elseif (substr($action, 0, 6) === 'PATCH_') {
            list($_, $property, $value) = explode('_', $action);
            foreach ($subjects as $id => $checked) {
                if ($checked) {
                    $group = $this->getResourceManager()->find($id);
                    $method = 'set'.ucfirst($property);
                    $group->$method(json_decode($value));
                    $this->getResourceManager()->update($group);
                }
            }
        }
    }

    protected function getBatchActions()
    {
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
        switch ($action) {
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

    protected function getPagination(PageDescriptionInterface $pageDescription, $page, $size = 10)
    {
        $page = (int)$page;
        $size = (int)$size;
        if($page <= 0)
            throw new BadRequestHttpException('The page parameter should be a positive number.');
        if($size <= 0)
            throw new BadRequestHttpException('The per_page parameter should be a positive number.');
        if($size > 1000)
            throw new BadRequestHttpException('The per_page parameter should not exceed 1000.');
        return $this->getPaginator()->paginate($pageDescription, $page, $size);
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
