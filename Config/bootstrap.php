<?php

use Cake\Core\Configure;
use Croogo\Core\Croogo;
use Assets\Lib\StorageManager;

if (!Configure::read('Assets.installed')) {
    return;
}

Configure::write('Wysiwyg.attachmentBrowseUrl', [
    'prefix' => 'admin',
    'plugin' => 'Assets',
    'controller' => 'Attachments',
    'action' => 'browse',
]);

Configure::write('Wysiwyg.uploadsPath', '');

Croogo::mergeConfig('Wysiwyg.actions', [
    'AssetsAttachments/admin_browse',
]);

StorageManager::config('LocalAttachment', [
    'description' => 'Local Attachment',
    'adapterOptions' => [WWW_ROOT . 'assets', true],
    'adapterClass' => '\Gaufrette\Adapter\Local',
    'class' => '\Gaufrette\Filesystem',
]);
StorageManager::config('LegacyLocalAttachment', [
    'description' => 'Local Attachment (Legacy)',
    'adapterOptions' => [WWW_ROOT . 'uploads', true],
    'adapterClass' => '\Gaufrette\Adapter\Local',
    'class' => '\Gaufrette\Filesystem',
]);

// TODO: make this configurable via backend
$actions = [
    'Croogo/Nodes.Admin/Nodes/edit',
    'Blocks/admin_edit',
    'Types/admin_edit',
];
$tabTitle = __d('assets', 'Assets');
foreach ($actions as $action):
    list($controller,) = explode('/', $action);
    Croogo::hookAdminTab($action, $tabTitle, 'Assets.admin/asset_list');
    Croogo::hookHelper($controller, 'Assets.AssetsAdmin');
endforeach;

// TODO: make this configurable via backend
$models = ['Block', 'Node', 'Type'];
foreach ($models as $model) {
    Croogo::hookBehavior($model, 'Assets.LinkedAssets', ['priority' => 9]);
}

Croogo::hookHelper('*', 'Assets.AssetsFilter');
