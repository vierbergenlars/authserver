<?php

namespace App\Entity;

use App\Doctrine\EntityRepository;
use vierbergenlars\Bundle\RadRestBundle\Pagination\EmptyPageDescription;
use App\Search\SearchGrammar;
use vierbergenlars\Bundle\RadRestBundle\Doctrine\QueryBuilderPageDescription;
use App\Search\SearchFieldException;
use App\Search\SearchValueException;

class UserRepository extends EntityRepository
{
    protected $fieldSearchWhitelist = array('username', 'email');

    public function handleUnknownSearchField(array &$block)
    {
        switch($block['name']) {
            case 'is':
                switch(strtolower($block['value'])) {
                    case 'admin':
                        $block['name']  = 'role';
                        $block['type']  = '~';
                        $block['value'] = 'ROLE_%ADMIN'; // ROLE_ADMIN and ROLE_SUPER_ADMIN
                        break;
                    case 'superadmin':
                    case 'super_admin':
                    case 'su':
                        $block['name']  = 'role';
                        $block['type']  = ':';
                        $block['value'] = 'ROLE_SUPER_ADMIN';
                        break;
                    case 'user':
                        $block['name']  = 'role';
                        $block['type']  = ':';
                        $block['value'] = 'ROLE_USER';
                        break;
                    case 'enabled':
                        $block['name']  = 'isActive';
                        $block['type']  = ':';
                        $block['value'] = true;
                        break;
                    case 'disabled':
                        $block['name']  = 'isActive';
                        $block['type']  = ':';
                        $block['value'] = false;
                        break;
                    default:
                        throw new SearchValueException($block['name'], $block['value'], array('admin', 'superadmin', 'super_admin', 'su', 'enabled', 'disabled'));
                }
                break;
            default:
                parent::handleUnknownSearchField($block);
        }
    }
}
