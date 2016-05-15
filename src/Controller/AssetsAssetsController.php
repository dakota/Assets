<?php

namespace Assets\Controller;

class AssetsAssetsController extends AssetsAppController
{

    public $uses = [
        'Assets.AssetsAsset',
    ];

    public function admin_delete($id = null)
    {
        if ($id) {
            $result = $this->AssetsAsset->delete($id);
        } else {
            throw new NotFoundException('Invalid Id');
        }
        if ($result) {
            $this->Session->setFlash('Asset has been deleted', 'flash', ['class' => 'success']);
        } else {
            $this->Session->setFlash('Unable to delete Asset', 'flash', ['class' => 'error']);
            $this->log($this->AssetsAsset->validationErrors);
        }

        return $this->redirect($this->referer());
    }

}
