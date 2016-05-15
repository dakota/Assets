<?php

namespace Assets\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Table;

class AssetsTable extends Table
{
    public $validate = [
        'file' => 'checkFileUpload',
    ];

    public function initialize(array $config)
    {
        $this->table('assets');
        $this->hasMany('AssetUsages', [
            'className' => 'Assets.AssetUsages',
            'dependent' => true,
        ]);
        $this->belongsTo('Attachments', [
            'className' => 'Assets.Attachments',
            'foreignKey' => 'foreign_key',
            'conditions' => [
                'Assets.model' => 'Assets.Attachments',
            ]
        ]);

        $this->addBehavior('CounterCache', [
            'Attachments' => [
                'asset_count' => [
                    'conditions' => [
                        'Assets.model' => 'Assets.Attachments'
                    ]
                ]
            ]
        ]);
        $this->addBehavior('Croogo/Core.Trackable');
        $this->addBehavior('Timestamp');
        parent::initialize($config);
    }

    public function beforeSave(Event $event, Entity $entity)
    {
        $event = Croogo::dispatchEvent('FileStorage.beforeSave', $this, [
            'entity' => $entity,
            'adapter' => $entity->get('adapter'),
        ]);
        if ($event->isStopped()) {
            return false;
        }

        return true;
    }

    public function beforeDelete(Event $event, Entity $entity, \ArrayObject $options)
    {
        if (!parent::beforeDelete($event, $entity, $options)) {
            return false;
        }
        $event = Croogo::dispatchEvent('FileStorage.beforeDelete', $this, [
            'entity' => $entity,
            'adapter' => $entity->get('adapter'),
        ]);
        if ($event->isStopped()) {
            return false;
        }

        return true;
    }

    public function checkFileUpload($data)
    {
        switch ($data['error']) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                break;
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
                break;
            case UPLOAD_ERR_OK:
                return true;
                break;
        }
    }

}
