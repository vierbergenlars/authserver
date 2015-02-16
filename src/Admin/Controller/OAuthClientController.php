<?php

namespace Admin\Controller;

class OAuthClientController extends DefaultController
{
    protected function getBatchActions()
    {
        $actions = parent::getBatchActions();
        $actions['Pre approved']['PATCH_preApproved_true'] = 'Enable';
        $actions['Pre approved']['PATCH_preApproved_false'] = 'Disable';

        return $actions;
    }
}
