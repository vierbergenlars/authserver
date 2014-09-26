<?php

namespace App\Entity;

use App\Doctrine\EntityRepository;
use vierbergenlars\Bundle\RadRestBundle\Pagination\EmptyPageDescription;
use App\Search\SearchGrammar;
use vierbergenlars\Bundle\RadRestBundle\Doctrine\QueryBuilderPageDescription;
use App\Search\SearchFieldException;
use App\Search\SearchValueException;

class GroupRepository extends EntityRepository
{
    protected $fieldSearchWhitelist = array('name');

    public function handleUnknownSearchField(array &$block)
    {
        switch($block['name']) {
            case 'is':
                switch(strtolower($block['value'])) {
                    case 'exportable':
                        $block['name']  = 'exportable';
                        $block['value'] = '1';
                        break;
                    case 'not exportable':
                    case 'noexportable':
                        $block['name'] = 'exportable';
                        $block['value'] = '0';
                    default:
                        throw new SearchValueException($block['name'], $block['value'], array('exportable', 'not exportable'));
                }
                break;
            default:
                parent::handleUnknownSearchField($block);
        }
    }
}
