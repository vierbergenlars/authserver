<?php
namespace OAuthBundle\Service;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class OAuthScopes
{

    private $roleHierarchy;

    public function __construct(RoleHierarchyInterface $roleHierarchy)
    {
        $this->roleHierarchy = $roleHierarchy;
    }

    /**
     *
     * @var string[]
     */
    private $supportedScopes;

    /**
     *
     * @param string[]|string $scopes
     */
    public function getReachableScopes($scopes)
    {
        if (is_string($scopes)) {
            return implode(' ', $this->getReachableScopes(explode(' ', trim($scopes))));
        }
        $inputScopeRoles = array_map(function ($scope) {
            return new Role('SCOPE_' . strtoupper($scope));
        }, $scopes);
        $outputScopeRoles = $this->roleHierarchy->getReachableRoles($inputScopeRoles);

        $scopeRoles = array_filter($outputScopeRoles, function (Role $scopeRole) {
            return $scopeRole->getRole() && strpos($scopeRole->getRole(), 'SCOPE_') === 0 && $scopeRole->getRole() !== 'SCOPE_*';
        });

        return array_map(function (Role $scopeRole) {
            return strtolower(substr($scopeRole->getRole(), strlen('SCOPE_')));
        }, $scopeRoles);
    }

    public function getSupportedScopes()
    {
        if (!$this->supportedScopes) {
            $this->supportedScopes = $this->getReachableScopes([
                '*'
            ]);
        }
        return $this->supportedScopes;
    }
}