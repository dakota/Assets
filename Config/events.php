<?php

use Cake\Core\Configure;

$handlers = [];
if (Configure::read('Assets.installed')) {
    $handlers = [
        'Assets.AssetsEventHandler',
        'Assets.LegacyLocalAttachmentStorageHandler',
        'Assets.LocalAttachmentStorageHandler',
    ];
}

return [
    'EventHandlers' => $handlers,
];
