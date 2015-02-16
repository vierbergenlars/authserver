<?php

namespace Admin\Controller;

class UserPropertyController extends DefaultController
{
    protected function getBatchActions()
    {
        $actions = parent::getBatchActions();
        $actions['Editable by user']['PATCH_userEditable_true'] = 'Enable';
        $actions['Editable by user']['PATCH_userEditable_false'] = 'Disable';
        $actions['Required']['PATCH_required_true'] = 'Enable';
        $actions['Required']['PATCH_required_false'] = 'Disable';

        return $actions;
    }
}
