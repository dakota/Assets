<?php

$this->extend('/Common/admin_edit');

$this->Html
    ->addCrumb(__d('croogo', 'Attachments'),
        ['action' => 'index'])
    ->addCrumb(__d('croogo', 'Upload'));

if ($this->layout === 'admin_popup'):
    $this->append('title', ' ');
endif;

$this->append('form-start', $this->Form->create($attachment, [
    'type' => 'file',
]));

$model = isset($this->request->query['model']) ? $this->request->query['model'] : null;
$foreignKey = isset($this->request->query['foreign_key']) ? $this->request->query['foreign_key'] : null;

$this->append('tab-heading');
echo $this->Croogo->adminTab(__d('croogo', 'Upload'), '#attachment-upload');
$this->end();

$this->append('tab-content');

echo $this->Html->tabStart('attachment-upload');

if (isset($model) && isset($foreignKey)):
    $assetUsage = 'asset.asset_usages.0.';
    echo $this->Form->input($assetUsage . 'model', [
        'type' => 'hidden',
        'value' => $model,
    ]);
    echo $this->Form->input($assetUsage . 'foreign_key', [
        'type' => 'hidden',
        'value' => $foreignKey,
    ]);
endif;

echo $this->Form->input('asset.file', ['label' => __d('croogo', 'Upload'), 'type' => 'file']);

if (isset($model) && isset($foreignKey)):
    echo $this->Form->input($assetUsage . 'featured_image', [
        'type' => 'checkbox',
        'label' => 'Featured Image',
    ]);
endif;

echo $this->Form->input('asset.adapter', [
    'type' => 'select',
    'default' => 'LocalAttachment',
    'options' => \Assets\Lib\StorageManager::configured(),
]);
echo $this->Form->input('excerpt', [
    'label' => __d('croogo', 'Caption'),
]);
echo $this->Form->input('title');
echo $this->Form->input('status', [
    'type' => 'hidden',
    'value' => true,
]);
echo $this->Form->input('asset.model', [
    'type' => 'hidden',
    'value' => 'Assets.Attachments',
]);

echo $this->Html->tabEnd();
$this->end();

$this->append('panels');
$redirect = ['action' => 'index'];
if ($this->request->session()->check('Wysiwyg.redirect')) {
    $redirect = $this->request->session()->read('Wysiwyg.redirect');
}
if (isset($this->request->query['model'])) {
    $redirect = array_merge(['action' => 'browse'], ['?' => $this->request->query]);
}
echo $this->Html->beginBox(__d('croogo', 'Publishing'));
echo $this->Form->button(__d('croogo', 'Upload'));
echo $this->Form->end();
echo $this->Html->link(__d('croogo', 'Cancel'), $redirect, [
        'button' => 'danger',
    ]);
echo $this->Html->endBox();
$this->end();

$this->append('form-end', $this->Form->end());

$script = "\$('[data-toggle=tab]:first').tab('show');";
$this->Js->buffer($script);
