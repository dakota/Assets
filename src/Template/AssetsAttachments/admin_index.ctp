<?php

$this->Html->script('Assets.admin', ['inline' => false]);

$this->extend('/Common/admin_index');

$this->Html->addCrumb('', '/admin', ['icon' => 'home'])
    ->addCrumb(__d('croogo', 'Attachments'), '/' . $this->request->url);

if (!empty($this->request->query)) {
    $query = $this->request->query;
} else {
    $query = [];
}

$this->append('actions');

echo $this->Croogo->adminAction(__d('croogo', 'New {0}', __d('croogo', 'Attachment')),
    array_merge(['?' => $query], ['action' => 'add']), ['button' => 'success']);

$this->end();

$detailUrl = [
    'plugin' => 'assets',
    'controller' => 'assets_attachments',
    'action' => 'browse',
    '?' => [
        'manage' => true,
        'model' => 'AssetsAttachment',
        'foreign_key' => null,
        'asset_id' => null,
    ],
];

$this->append('table-heading');
$tableHeaders = $this->Html->tableHeaders([
    $this->Paginator->sort('id', __d('croogo', 'Id')),
    '&nbsp;',
    $this->Paginator->sort('title', __d('croogo', 'Title')),
    __d('croogo', 'Versions'),
    __d('croogo', 'Actions'),
]);

echo $this->Html->tag('thead', $tableHeaders);
$this->end();

$this->append('table-body');
$rows = [];
foreach ($attachments as $attachment) {
    $actions = [];

    $mimeType = explode('/', $attachment['AssetsAsset']['mime_type']);
    $mimeType = $mimeType['0'];
    if ($mimeType == 'image') {
        $detailUrl['?']['foreign_key'] = $attachment['AssetsAttachment']['id'];
        $detailUrl['?']['asset_id'] = $attachment['AssetsAsset']['id'];
        $assetCount = $attachment['AssetsAttachment']['asset_count'] . '&nbsp;';
        $actions[] = $this->Croogo->adminRowAction('', $detailUrl, [
            'icon' => 'suitcase',
            'data-toggle' => 'browse',
            'tooltip' => __d('assets', 'View other sizes'),
        ]);

        $actions[] = $this->Croogo->adminRowActions($attachment['AssetsAttachment']['id']);
        $resizeUrl = array_merge([
            'action' => 'resize',
            $attachment['AssetsAttachment']['id'],
            'ext' => 'json',
        ], ['?' => $query]);
    }

    $actions[] = $this->Croogo->adminRowAction('', $resizeUrl, [
        'icon' => 'resize-small',
        'tooltip' => __d('croogo', 'Resize this item'),
        'data-toggle' => 'resize-asset',
    ]);
    $editUrl = array_merge(['action' => 'edit', $attachment['AssetsAttachment']['id']], ['?' => $query]);
    $actions[] = $this->Croogo->adminRowAction('', $editUrl,
        ['icon' => $_icons['update'], 'tooltip' => __d('croogo', 'Edit this item')]);
    $deleteUrl = ['action' => 'delete', $attachment['AssetsAttachment']['id']];
    $deleteUrl = array_merge(['?' => $query], $deleteUrl);
    $actions[] = $this->Croogo->adminRowAction('', $deleteUrl,
        ['icon' => $_icons['delete'], 'tooltip' => __d('croogo', 'Remove this item')], __d('croogo', 'Are you sure?'));

    $path = $attachment['AssetsAsset']['path'];
    if ($mimeType == 'image') {

        $imgUrl = $this->AssetsImage->resize($path, 100, 200, ['adapter' => $attachment['AssetsAsset']['adapter']],
            ['alt' => $attachment['AssetsAttachment']['title']]);
        $thumbnail = $this->Html->link($imgUrl, $path,
            ['escape' => false, 'class' => 'thickbox', 'title' => $attachment['AssetsAttachment']['title']]);
    } else {
        $thumbnail = $this->Html->image('/croogo/img/icons/page_white.png', ['alt' => $mimeType]) .
            ' ' .
            $mimeType .
            ' (' .
            $this->Assets->filename2ext($attachment['AssetsAttachment']['path']) .
            ')';
    }

    $actions = $this->Html->div('item-actions', implode(' ', $actions));

    $rows[] = [
        $attachment['AssetsAttachment']['id'],
        $thumbnail,
        $this->Html->div(null, $attachment['AssetsAttachment']['title']) .
        '&nbsp;' .
        $this->Html->link($this->Html->url($path, true), $path, [
                'target' => '_blank',
            ]),
        $assetCount,
        $actions,
    ];
}

echo $this->Html->tableCells($rows);
$this->end();
