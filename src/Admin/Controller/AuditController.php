<?php

namespace Admin\Controller;

use App\Entity\EmailAddress;
use App\Entity\UserProperty;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\View as AView;
use Gedmo\Loggable\Entity\LogEntry;
use Knp\Component\Pager\Paginator;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    private $om;

    public function __construct(ResourceManagerInterface $resourceManager,Paginator $paginator, ObjectManager $om)
    {
        $this->resourceManager = $resourceManager;
        $this->paginator = $paginator;
        $this->om = $om;
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

    /**
     * @AView
     * @Get(path="/{id}/target/{property}/{propertyId}", defaults={"property"=null, "propertyId"=null})
     */
    public function targetAction($id, $property = null, $propertyId = null)
    {
        $logEntry = $this->getAction($id)->getData();

        /* @var $logEntry LogEntry */
        $class = $logEntry->getObjectClass();
        $id = $logEntry->getObjectId();

        if($property !== null) {
            switch($class) {
                case 'App\Entity\User':
                case 'App\Entity\Group':
                    if($property === 'groups') {
                        if($propertyId !== null) {
                            $class='App\Entity\Group';
                            $id = $propertyId;
                            break;
                        }
                    }
                    throw new NotFoundHttpException;
                    break;
                case 'App\Entity\UserProperty':
                    if($property !== 'user' && $property !== 'property')
                        throw new NotFoundHttpException;
                    $data = $logEntry->getData();
                    $class = 'App\Entity\\'.ucfirst($property);
                    $id = $data[$property]['id'];
                    break;
                default:
                    throw new NotFoundHttpException;
            }
        }

        $repo = $this->om->getRepository($class);
        $object = $repo->find(array('id' => $id));
        if(!$object)
            throw new NotFoundHttpException;


        if($object instanceof UserProperty) {
            $object = $object->getUser();
            $class  = 'App\Entity\User';
        } elseif($object instanceof EmailAddress) {
            $object = $object->getUser();
            $class = 'App\Entity\User';
        }

        $parts = explode('\\',$class);

        $part1 = array_shift($parts);
        switch($part1) {
            case 'App':
            case 'Admin':
                break;
            default:
                throw new NotFoundHttpException;
        }
        if(array_shift($parts) !== 'Entity')
            throw new NotFoundHttpException;

        if($parts == array('Property'))
            $parts[0] = 'UserProperty';

        $route = 'admin_'.strtolower(implode('_', $parts)).'_get';

        return View::createRouteRedirect($route, array('id'=>$object->getId()));

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