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
                        $block['value'] = 'ROLE_*ADMIN'; // ROLE_ADMIN and ROLE_SUPER_ADMIN
                        break;
                    case 'superadmin':
                    case 'super_admin':
                    case 'su':
                        $block['name']  = 'role';
                        $block['value'] = 'ROLE_SUPER_ADMIN';
                        break;
                    case 'user':
                        $block['name']  = 'role';
                        $block['value'] = 'ROLE_USER';
                        break;
                    case 'enabled':
                        $block['name']  = 'isActive';
                        $block['value'] = true;
                        break;
                    case 'disabled':
                        $block['name']  = 'isActive';
                        $block['value'] = false;
                        break;
                    default:
                        throw new SearchValueException($block['name'], $block['value'], array('admin', 'superadmin', 'super_admin', 'su', 'enabled', 'disabled'));
                }
                break;
            case 'name':
                $block['name'] = 'displayName';
                break;
            default:
                parent::handleUnknownSearchField($block);
        }
    }

    private function updateEmails($object) {
        foreach($object->getEmailAddresses() as $email) {
            $email->setUser($object);
            $this->getEntityManager()->persist($email);
        }
    }
    public function create($object) {
        $this->updateEmails($object);
        parent::create($object);
        $this->getEntityManager()->flush($object->getEmailAddresses()->toArray());
    }

    public function update($object) {
        $this->updateEmails($object);
        parent::update($object);
        $this->getEntityManager()->flush($object->getEmailAddresses()->toArray());
    }
}
