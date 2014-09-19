<?php

namespace App\Entity;

use App\Doctrine\EntityRepository;
use vierbergenlars\Bundle\RadRestBundle\Pagination\EmptyPageDescription;
use App\Search\SearchGrammar;
use vierbergenlars\Bundle\RadRestBundle\Doctrine\QueryBuilderPageDescription;
use App\Search\SearchFieldException;

class UserRepository extends EntityRepository
{
    protected $fieldSearchWhitelist = array('username', 'email');

    public function handleUnknownSearchField(array &$block)
    {
        switch($block['name']) {
            case 'is':
                $block['name'] = 'roles';
                $block['type'] = '~';
                $block['value'] = 'ROLE_'.strtoupper($block['value']);
                break;
            default:
                parent::handleUnknownSearchField($block);
        }
    }
}
