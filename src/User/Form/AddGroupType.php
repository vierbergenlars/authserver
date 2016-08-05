<?php
/* Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace User\Form;

use App\Entity\Group;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class AddGroupType extends AbstractType
{
    private $tokenStorage;

    function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(($token = $this->tokenStorage->getToken()) === null)
            throw new \InvalidArgumentException('Authentication token does not exist');
        $user = $token->getUser();
        if($user === null)
            throw new \InvalidArgumentException('There is no authenticated user.');

        $builder
            ->add('group', EntityType::class, array(
                'choice_label'=>'displayName',
                'class' => Group::class,
                'query_builder'=>function (EntityRepository $repo) use($user) {
                    $qb = $repo->createQueryBuilder('g')
                                ->where('g.noUsers = false AND g.userJoinable = true');
                    if($user->getGroups()->count() == 0)
                        return $qb;
                    return $qb->andWhere('g NOT IN(:groups)')
                                ->setParameter('groups', $user->getGroups());
                },
                'required'=>true,
                'multiple'=>false,
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Join group',
                'attr' => array(
                    'class' => 'btn-sm',
                )
            ))
        ;
    }
}
