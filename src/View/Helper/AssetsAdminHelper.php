<?php

namespace Assets\View\Helper;

use Croogo\Core\View\Helper\CroogoAppHelper;

class AssetsAdminHelper extends CroogoAppHelper
{

    public $helpers = [
        'Html',
    ];

    public function beforeRender($viewFile)
    {
        if (empty($this->request->params['admin'])) {
            return;
        }

        if ($this->_View->theme === 'AdminExtras') {
            $this->Html->css([
                'bootstrap-editable',
            ], [
                'inline' => false,
            ]);
            $this->Html->script([
                'bootstrap-editable.min',
            ], [
                'block' => 'scriptBottom',
            ]);
        }
    }

}
