<?php

namespace App\Entity;

use App\Doctrine\EntityRepository;
use App\Search\SearchGrammar;
use vierbergenlars\Bundle\RadRestBundle\Doctrine\QueryBuilderPageDescription;
use App\Search\SearchValueException;

class UserRepository extends EntityRepository
{
    public function find($id)
    {
        if (is_array($id)||is_int($id)||is_numeric($id)) {
            return parent::find($id);
        } else {
            return $this->findOneBy(array('guid'=>$id));
        }
    }
    protected $fieldSearchWhitelist = array('username', 'email');

    public function search($terms)
    {
        if (is_string($terms)) {
            $parser = new SearchGrammar();
            $blocks = $parser->parse($terms);
        } elseif (is_array($terms)) {
            $blocks = array();
            foreach ($terms as $name=>$value) {
                if(is_array($value)) {
                    foreach($value as $v) {
                        $blocks[] = array(
                            'name' => $name,
                            'value' => $v,
                        );
                    }
                } else {
                    $blocks[] = array(
                        'name' => $name,
                        'value' => $value,
                    );
                }
            }
        }

        $queryBuilder = $this->createQueryBuilder('u');
        $and = $queryBuilder->expr()->andX();

        foreach ($blocks as $i=>$block) {
            if (!in_array($block['name'], $this->fieldSearchWhitelist)) {
                $this->handleUnknownSearchField($block);
            }

            if ($block['name'] === 'email') {
                $queryBuilder->leftJoin('AppBundle:EmailAddress', 'e', 'WITH', 'e.user = u');
                $and->add($queryBuilder->expr()->like('e.email', '?'.$i));
                $queryBuilder->setParameter($i, str_replace('*', '%', $block['value']));
            } elseif($block['name'] === 'role' && $block['value'] === 'ROLE_USER') {
                $and->add($queryBuilder->expr()->in('u.role', '?'.$i));
                $queryBuilder->setParameter($i, array($block['value'], 'ROLE_AUDIT'));
            } elseif (strpos($block['value'], '*') !== false) {
                $and->add($queryBuilder->expr()->like('u.'.$block['name'], '?'.$i));
                $queryBuilder->setParameter($i, str_replace('*', '%', $block['value']));
            } else {
                $and->add($queryBuilder->expr()->eq('u.'.$block['name'], '?'.$i));
                $queryBuilder->setParameter($i, $block['value']);
            }
        }

        if ($and->count()) {
            $queryBuilder->where($and);
        }

        return new QueryBuilderPageDescription($queryBuilder);
    }

    public function handleUnknownSearchField(array &$block)
    {
        switch ($block['name']) {
            case 'is':
                switch (strtolower($block['value'])) {
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
                        $block['name']  = 'enabled';
                        $block['value'] = true;
                        break;
                    case 'disabled':
                        $block['name']  = 'enabled';
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

    private function updateEmails($object)
    {
        foreach ($object->getEmailAddresses() as $email) {
            $email->setUser($object);
            $this->getEntityManager()->persist($email);
        }
    }

    public function newInstance()
    {
        $user = parent::newInstance();
        /* @var $user \App\Entity\User */
        $emailAddress = new EmailAddress;
        $emailAddress->setPrimary(true);
        $user->addEmailAddress($emailAddress);

        return $user;
    }

    private function postProcess(User $object)
    {
        $this->getEntityManager()->flush($object->getEmailAddresses()->toArray());
    }

    public function create($object)
    {
        $generator = new \Doctrine\ORM\Id\UuidGenerator();
        $uuid = $generator->generate($this->getEntityManager(), $object);
        $object->setGuid($uuid);
        $this->getEntityManager()->beginTransaction();
        $this->updateEmails($object);
        parent::create($object);
        $this->postProcess($object);
        $this->getEntityManager()->commit();
    }

    public function update($object)
    {
        $this->getEntityManager()->beginTransaction();
        $this->updateEmails($object);
        parent::update($object);
        $this->postProcess($object);
        $this->getEntityManager()->commit();
    }

    public function getAllEmptyRequiredProperties(User $user)
    {
        return $this->getEntityManager()
                ->createQueryBuilder()
                ->select('p')
                ->from('AppBundle:UserProperty', 'up')
                ->join('AppBundle:Property', 'p', 'WITH', 'p.required = true AND up.property = p')
                ->where('up.data IS NULL')
                ->andWhere('up.user = :user')
                ->getQuery()
                ->setParameter('user', $user)
                ->execute();
    }
}
