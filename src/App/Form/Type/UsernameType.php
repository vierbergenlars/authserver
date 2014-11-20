<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use App\Form\DataTransformer\UserToUsernameTransformer;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UsernameType extends AbstractType
{
    /**
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    private $repo;

    /**
     *
     * @param \Doctrine\ORM\EntityRepository $repo
     */
    public function __construct(EntityRepository $repo) {
        $this->repo = $repo;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new UserToUsernameTransformer($this->repo);
        $builder->addModelTransformer($transformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'invalid_message' => 'This user does not exist',
        ));
    }

    public function getParent()
    {
        return 'text';
    }

    public function getName()
    {
        return 'app_username';
    }

}