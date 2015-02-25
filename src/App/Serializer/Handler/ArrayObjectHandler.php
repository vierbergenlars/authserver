<?php

namespace App\Serializer\Handler;

use ArrayObject;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;

class ArrayObjectHandler implements SubscribingHandlerInterface
{
    /**
     * @return array
     */
    public static function getSubscribingMethods()
    {
        $methods = array();
        $formats = array('json', 'xml', 'yml');
        $types   = array(
            'ArrayObject'
        );

        foreach ($types as $type) {
            foreach ($formats as $format) {
                $methods[] = array(
                    'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                    'type'      => $type,
                    'format'    => $format,
                    'method'    => 'serializeArrayObject',
                );
            }
        }

        return $methods;
    }

    /**
     * @param VisitorInterface $visitor
     * @param ArrayObject $data
     * @param array $type
     * @param Context $context
     * @return mixed
     */
    public function serializeArrayObject(VisitorInterface $visitor, ArrayObject $data, array $type, Context $context)
    {
        $type['name'] = 'array';
        return $visitor->visitArray($data->getArrayCopy(), $type, $context);
    }
}
