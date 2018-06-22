<?php
namespace Admin\Event;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;

class FilterListEvent extends Event
{

    private $class;

    /**
     *
     * @var FormBuilderInterface
     */
    private $searchFormBuilder;

    /**
     *
     * @var FormInterface|null
     */
    private $searchForm;

    /**
     * Map of ids to filter expressions that are used to filter the resultset
     *
     * @var Expression[]
     */
    private $filters = [];

    public function __construct($class, FormBuilderInterface $searchFormBuilder)
    {
        $this->class = $class;
        $this->searchFormBuilder = $searchFormBuilder;
    }

    /**
     * The type of entity that this event applies to
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Adds a filter to the results
     *
     * @param string $name
     *            Unique id of the filter
     * @param Expression $expression
     *            The expression to use for filtering the results
     * @return static
     */
    public function addFilter($name, Expression $expression)
    {
        $this->filters[$name] = $expression;
        return $this;
    }

    /**
     * Removes a filter from the results
     *
     * @param string $name
     *            Unique id of the filter
     * @return static
     */
    public function removeFilter($name)
    {
        unset($this->filters[$name]);
        return $this;
    }

    /**
     * Gets the builder that is used for the search form
     *
     * @throws \LogicException When the search form has already been built.
     * @return FormBuilderInterface
     */
    public function getSearchFormBuilder()
    {
        if ($this->searchForm !== null) {
            throw new \LogicException('You can not get the form builder after the form has been built.');
        }
        return $this->searchFormBuilder;
    }

    /**
     * Creates and returns the full criteria that will be used to filter the results
     *
     * @internal
     * @ignore
     * @return Criteria
     */
    public function getCriteria()
    {
        $criteria = new Criteria();
        foreach ($this->filters as $filter) {
            $criteria->andWhere($filter);
        }
        return $criteria;
    }

    /**
     * Creates and returns the form that will be used for searching
     *
     * A form will only be created once, creating a form will disable the form builder.
     *
     * @return FormInterface
     */
    public function getSearchForm()
    {
        if (!$this->searchForm) {
            $this->searchForm = $this->searchFormBuilder->getForm();
        }
        return $this->searchForm;
    }
}