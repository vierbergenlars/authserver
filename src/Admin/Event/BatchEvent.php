<?php
namespace Admin\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Creates handlers for batch actions in the admin panel
 */
class BatchEvent extends Event
{

    /**
     *
     * @var string
     */
    private $class;

    /**
     *
     * @var array
     */
    private $actions = [];

    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * Get the classname of the entity for which the batch actions are generated
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Creates/replaces a batch action
     *
     * @param string $name
     *            The unique name of the batch action
     * @param string|[string,string] $label
     *            Label for the batch action. Use a string for a simple label,
     *            or a 2 element array to use option groups
     * @param callable $callback
     *            Callback function to execute the action on a matched object
     * @return $this
     */
    public function setAction($name, $label, callable $callback)
    {
        $this->actions[$name] = [
            $label,
            $callback
        ];
        return $this;
    }

    /**
     *
     * @internal Handles an action on a set of objects
     * @ignore
     * @param string $name
     * @param object[] $enrollments
     */
    public function handleAction($name, array $objects)
    {
        foreach ($objects as $object) {
            $this->actions[$name][1]($object);
        }
    }

    /**
     *
     * @internal Gets the choices for the batch form
     * @ignore
     * @return array
     */
    public function getChoices()
    {
        $choices = [];
        foreach ($this->actions as $name => list ($label, $callback)) {
            if (is_array($label)) {
                $choices[$label[0]][$label[1]] = $name;
            } else {
                $choices[$label] = $name;
            }
        }
        return $choices;
    }
}