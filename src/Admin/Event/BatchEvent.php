<?php
namespace Admin\Event;

use Symfony\Component\EventDispatcher\Event;

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
     *
     * @param string $name
     * @param string|[string,string] $label
     * @param callable $callback
     *            Takes an object as parameter
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