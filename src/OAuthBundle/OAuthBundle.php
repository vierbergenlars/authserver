<?php
namespace OAuthBundle;

use OAuthBundle\DependencyInjection\Compiler\OAuthCompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OAuthBundle extends Bundle
{

    /**
     *
     * {@inheritdoc}
     * @see \Symfony\Component\HttpKernel\Bundle\Bundle::build()
     */
    public function build(\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        $container->addCompilerPass(new OAuthCompilerPass());
    }
}
