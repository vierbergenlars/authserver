<?php
namespace OAuthBundle\Storage;

use OAuth2\Storage\ScopeInterface;
use OAuthBundle\Service\OAuthScopes;

class ScopeStorage implements ScopeInterface
{

    /**
     *
     * @var OAuthScopes
     */
    private $scopes;

    public function __construct(OAuthScopes $scopes)
    {
        $this->scopes = $scopes;
    }

    public function scopeExists($scope)
    {
        $scope = explode(' ', trim($scope));

        return (count(array_diff($scope, $this->scopes->getSupportedScopes())) == 0);
    }

    public function getDefaultScope($client_id = null)
    {
        return "";
    }
}