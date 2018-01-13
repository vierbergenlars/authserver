<?php
namespace OAuthBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use OAuth2\Storage\Memory;
use OAuthBundle\Storage\AuthorizationCodeStorage;
use OAuthBundle\Storage\ClientStorage;
use OAuthBundle\Storage\PublicKeyStorage;
use OAuthBundle\Storage\RefreshTokenStorage;
use OAuthBundle\Storage\UserClaimsStorage;
use Symfony\Component\DependencyInjection\Reference;
use OAuthBundle\Storage\AccessTokenStorage;

class OAuthCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $container->setParameter("oauth2.storage.authorization_code.class", AuthorizationCodeStorage::class);
        $container->setParameter("oauth2.storage.client_credentials.class", ClientStorage::class);
        $container->setParameter("oauth2.storage.access_token.class", AccessTokenStorage::class);
        $container->setParameter("oauth2.storage.refresh_token.class", RefreshTokenStorage::class);

        $container->setParameter('oauth2.server.config', [
            'issuer' => $container->getParameter('oauth_issuer'),
            'use_openid_connect' => true
        ]);

        $container->setAlias('oauth2.user_provider', 'app.user_provider');

        $container->getDefinition("oauth2.storage.public_key")
            ->setClass(PublicKeyStorage::class)
            ->setArguments([
            $container->getParameter('oauth_public_key_file'),
            $container->getParameter('oauth_private_key_file'),
            $container->getParameter('oauth_signature_algorithm')
        ]);

        $container->getDefinition("oauth2.storage.user_claims")
            ->setClass(UserClaimsStorage::class)
            ->setArguments([
            new Reference("doctrine.orm.entity_manager")
        ]);
        $container->getDefinition("oauth2.storage.scope")
            ->setClass(Memory::class)
            ->setArguments([
            [
                'default_scope' => 'profile:guid',
                'supported_scopes' => [
                    'openid',
                    'profile',
                    'email',
                    'profile:guid',
                    'profile:username',
                    'profile:realname',
                    'profile:groups',
                    'profile:email',
                    'group:join',
                    'group:leave',
                    'property:read',
                    'property:write'
                ]
            ]
        ]);
    }
}