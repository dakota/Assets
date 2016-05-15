<?php

/**
 * AssetsAttachment Model
 *
 */
namespace Assets\Model\Table;

use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Query;
use Cake\ORM\Table;

class AttachmentsTable extends Table
{
    public function initialize(array $config)
    {
        $this->table('attachments');

        $this->hasOne('Assets', [
            'className' => 'Assets.Assets',
            'dependent' => true,
            'foreignKey' => 'foreign_key',
            'conditions' => [
                'Assets.parent_asset_id' => null,
                'Assets.model' => 'Assets.Attachments',
            ],
        ]);

        $this->addBehavior('Croogo/Core.Trackable');
        $this->addBehavior('Search.Search');
        $this->addBehavior('Burzum/Imagine.Imagine');
        $this->addBehavior('Timestamp');
        parent::initialize($config);
    }

    public $filterArgs = [
        'filter' => ['type' => 'query', 'method' => 'filterAttachments'],
        'filename' => ['type' => 'like', 'field' => 'AssetsAsset.filename'],
        'type' => ['type' => 'value', 'field' => 'AssetsAssetUsage.type'],
    ];

    public function filterAttachments($data = [])
    {
        $conditions = [];
        if (!empty($data['filter'])) {
            $filter = '%' . $data['filter'] . '%s';
            $conditions = [
                'OR' => [
                    $this->escapeField('title') . ' LIKE' => $filter,
                    $this->escapeField('excerpt') . ' LIKE' => $filter,
                    $this->escapeField('body') . ' LIKE' => $filter,
                ],
            ];
        }

        return $conditions;
    }

    public function beforeSave(Event $event, Entity $entity)
    {
        if (isset($entity->asset->file['name'])) {
            $file = $entity->asset->file;
            if (empty($entity['title'])) {
                $entity['title'] = $file['name'];
            }
            if (empty($entity['slug'])) {
                $entity['slug'] = $file['name'];
            }
            if (empty($entity['hash'])) {
                $entity['hash'] = sha1_file($file['tmp_name']);
            }
        }

        return true;
    }

    /**
     * Perform the actual import based on $task
     *
     * @param $task array Array of tasks
     */
    public function runTask($task)
    {
        $imports = $errors = 0;
        foreach ($task['copy'] as $i => $source) {
            if (!$source) {
                continue;
            }
            $task['data'][$i]['AssetsAsset']['model'] = $this->alias;
            $task['data'][$i]['AssetsAsset']['adapter'] = 'LegacyLocalAttachment';
            $task['data'][$i]['AssetsAsset']['path'] = $source['from'];
            $result = $this->saveAll($task['data'][$i], ['atomic' => true]);
            if ($result) {
                $imports++;
            } else {
                $errors++;
            }
        }

        return compact('imports', 'errors');
    }

    /**
     * Import files into the assets repository
     *
     * @param $dir array|string Path to import
     * @param $regex string Regex to filter files to import
     * @param $options array
     * @throws InvalidArgumentException
     */
    public function importTask($dirs = [], $regex = '.*', $options = [])
    {
        $options = Hash::merge([
            'recursive' => false,
        ], $options);
        foreach ($dirs as $dir) {
            if (substr($dir, -1) === '/') {
                $dir = substr($dir, 0, strlen($dir) - 1);
            }
            if (!is_dir($dir)) {
                throw new InvalidArgumentException(__('{0} is not a directory', $dir));
            }
            $folder = new Folder($dir, false, false);
            if ($options['recursive']) {
                $files = $folder->findRecursive($regex, false);
            } else {
                $files = $folder->find($regex, false);
                $files = array_map(function ($v) use ($dir) {
                    return APP . $dir . '/' . $v;
                }, $files);
            }

            return $this->_createImportTask($files, $options);
        }
    }

    /**
     * Create Import task
     */
    protected function _createImportTask($files, $options)
    {
        $data = [];
        $copy = [];
        $error = [];
        foreach ($files as $file) {
            $asset = $this->createFromFile($file);
            if (is_array($asset)) {
                $data[] = $asset;
                $copy[] = ['from' => $asset['AssetsAttachment']['import_path']];
                $error[] = null;
            } else {
                $data[] = null;
                $copy[] = null;
                $error[] = $asset;
            }
        }

        return compact('data', 'copy', 'error');
    }

    /**
     * Create an AssetsAttachment data from $file
     *
     * @param $file string Path to file
     * @return array|string Array of data or error message
     * @throws InvalidArgumentException
     */
    public function createFromFile($file)
    {
        if (!file_exists($file)) {
            throw new InvalidArgumentException(__('{0} cannot be found', $file));
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fp = fopen($file, 'r');
        $stat = fstat($fp);
        fclose($fp);
        $hash = sha1_file($file);
        $duplicate = isset($hash) ? $this->find('duplicate', ['hash' => $hash]) : false;
        if ($duplicate) {
            $firstDupe = $duplicate[0]['AssetsAttachment']['id'];

            return sprintf('%s is duplicate to asset: %s', str_replace(APP, '', $file), $firstDupe);
        }
        $path = str_replace(rtrim(WWW_ROOT, '/'), '', $file);
        $asset = $this->create([
            'path' => $path,
            'import_path' => $path,
            'title' => basename($file),
            'slug' => basename($file),
            'mime_type' => $finfo->file($file),
            'hash' => $hash,
            'status' => true,
            'created' => date('Y-m-d H:i:s', $stat[9]),
            'updated' => date('Y-m-d H:i:s', time()),
        ]);

        return $asset;
    }

    /**
     * Create a video thumbnail
     *
     * @param integer $id Attachment Id
     * @param integer $w New Width
     * @param integer $h New Height
     * @param array $options Options array
     */
    public function createVideoThumbnail($id, $w, $h, $options = [])
    {
        if (!class_exists('FFmpegMovie')) {
            throw new RunTimeException('FFmpegMovie class not found');
        }
        $this->recursive = -1;
        $this->contain(['AssetsAsset']);
        $attachment = $this->findById($id);
        $asset =& $attachment['AssetsAsset'];
        $path = rtrim(WWW_ROOT, '/') . $asset['path'];

        $info = pathinfo($asset['path']);
        $ind = sprintf('.resized-%dx%d.', $w, $h);

        $uploadsDir = str_replace('/' . $options['uploadsDir'] . '/', '', dirname($asset['path'])) . '/';
        $filename = $info['filename'] . $ind . 'jpg';
        $writePath = WWW_ROOT . 'galleries' . DS . $uploadsDir . $filename;

        $ffmpeg = new FFmpegMovie($path, null, 'avconv');
        $frame = $ffmpeg->getFrame(null, 200, 150);
        imagejpeg($frame->toGDImage(), $writePath, 100);

        $fp = fopen($writePath, 'r');
        $stat = fstat($fp);
        fclose($fp);

        $adapter = $asset['adapter'];

        $data = $this->AssetsAsset->create([
            'filename' => $filename,
            'path' => dirname($asset['path']) . '/' . $filename,
            'model' => $asset['model'],
            'extension' => $asset['extension'],
            'parent_asset_id' => $asset['id'],
            'foreign_key' => $asset['foreign_key'],
            'adapter' => $adapter,
            'mime_type' => $asset['mime_type'],
            'width' => $newWidth,
            'height' => $newHeight,
            'filesize' => $stat[7],
        ]);

        $asset = $this->AssetsAsset->save($data);

        return $asset;
    }

    /**
     * Copy an existing attachment and resize with width: $w and height: $h
     *
     * @param integer $id Attachment Id
     * @param integer $w New Width
     * @param integer $h New Height
     * @param array $options Options array
     */
    public function createResized($id, $w, $h, $options = [])
    {
        $options = Hash::merge([
            'uploadsDir' => 'assets',
        ], $options);
        $imagine = $this->imagineObject();
        $this->recursive = -1;
        $this->contain(['AssetsAsset']);
        $attachment = $this->findById($id);
        $asset =& $attachment['AssetsAsset'];
        $path = rtrim(WWW_ROOT, '/') . $asset['path'];

        $image = $imagine->open($path);
        $size = $image->getSize();
        $width = $size->getWidth();
        $height = $size->getHeight();

        if (empty($h) && !empty($w)) {
            $scale = $w / $width;
            $newSize = $size->scale($scale);
        } elseif (empty($w) && !empty($h)) {
            $scale = $h / $height;
            $newSize = $size->scale($scale);
        } else {
            $scaleWidth = $w / $width;
            $scaleHeight = $h / $height;
            $scale = $scaleWidth > $scaleHeight ? $scaleWidth : $scaleHeight;
            $newSize = $size->scale($scale);
        }

        $newWidth = $newSize->getWidth();
        $newHeight = $newSize->getHeight();

        $image->resize($newSize);

        $tmpName = tempnam('/tmp', 'qq');
        $image->save($tmpName, ['format' => $asset['extension']]);

        $fp = fopen($tmpName, 'r');
        $stat = fstat($fp);
        fclose($fp);

        $raw = file_get_contents($tmpName);
        unlink($tmpName);

        $info = pathinfo($asset['path']);
        $ind = sprintf('.resized-%dx%d.', $newWidth, $newHeight);

        $uploadsDir = str_replace('/' . $options['uploadsDir'] . '/', '', dirname($asset['path'])) . '/';
        $filename = $info['filename'] . $ind . $info['extension'];
        $writePath = $uploadsDir . $filename;

        $adapter = $asset['adapter'];
        $filesystem = StorageManager::adapter($adapter);
        $filesystem->write($writePath, $raw);

        $data = $this->AssetsAsset->create([
            'filename' => $filename,
            'path' => dirname($asset['path']) . '/' . $filename,
            'model' => $asset['model'],
            'extension' => $asset['extension'],
            'parent_asset_id' => $asset['id'],
            'foreign_key' => $asset['foreign_key'],
            'adapter' => $adapter,
            'mime_type' => $asset['mime_type'],
            'width' => $newWidth,
            'height' => $newHeight,
            'filesize' => $stat[7],
        ]);

        $asset = $this->AssetsAsset->save($data);

        return $asset;
    }

    /**
     * Find duplicates based on hash
     */
    protected function _findDuplicate($state, $query, $results = [])
    {
        if ($state == 'before') {
            if (empty($query['hash'])) {
                return [];
            }
            $hash = $query['hash'];
            $query = Hash::merge($query, [
                'type' => 'first',
                'recursive' => -1,
                'conditions' => [
                    $this->escapeField('hash') => $hash,
                ],
            ]);
            unset($query['hash']);

            return $query;
        } else {
            return $results;
        }
    }

    public function findModelAttachments(Query $query, $options)
    {
        $model = $foreignKey = null;
        if (isset($options['model'])) {
            $model = $options['model'];
            unset($options['model']);
        }
        if (isset($options['foreign_key'])) {
            $foreignKey = $options['foreign_key'];
            unset($options['foreign_key']);
        }
        $this->associations()
            ->remove('Assets');
        $this->addAssociations([
            'hasOne' => [
                'Assets' => [
                    'className' => 'Assets.Assets',
                    'foreignKey' => false,
                    'conditions' => [
                        'Assets.model' => $this->registryAlias(),
                        'Assets.foreign_key' => new IdentifierExpression($this->aliasField('id')),
                    ],
                ],
                'AssetUsages' => [
                    'className' => 'Assets.AssetUsages',
                    'foreignKey' => false,
                    'conditions' => [
                        'Assets.id' => new IdentifierExpression('asset_id'),
                    ],
                ],
            ],
        ]);
        $query->contain(['Assets', 'AssetUsages']);
        $query->where([
            'AssetUsages.model' => $model,
            'AssetUsages.foreign_key' => $foreignKey,
        ]);

        return $query;
    }

    protected function _findVersions($state, $query = [], $results = [])
    {
        if ($state === 'after') {
            return $results;
        }
        $assetId = $model = $foreignKey = null;
        if (isset($query['asset_id'])) {
            $assetId = $query['asset_id'];
            unset($query['asset_id']);
        }
        if (isset($query['model'])) {
            $model = $query['model'];
            unset($query['model']);
        }
        if (isset($query['foreign_key'])) {
            $foreignKey = $query['foreign_key'];
            unset($query['foreign_key']);
        }
        if (isset($query['all'])) {
            $all = $query['all'];
            unset($query['all']);
        }
        $this->unbindModel(['hasOne' => ['AssetsAsset']]);
        $this->bindModel([
            'hasOne' => [
                'AssetsAsset' => [
                    'className' => 'Assets.AssetsAsset',
                    'foreignKey' => false,
                    'conditions' => [
                        'AssetsAsset.model = \'AssetsAttachment\'',
                        'AssetsAsset.foreign_key = AssetsAttachment.id',
                    ],
                ],
            ],
        ]);
        $contain = isset($query['contain']) ? $query['contain'] : [];
        $query['contain'] = Hash::merge($contain, [
            'AssetsAsset' => ['AssetsAssetUsage'],
        ]);
        if ($assetId && !isset($all)) {
            $query['conditions'] = Hash::merge($query['conditions'], [
                'OR' => [
                    'AssetsAsset.id' => $assetId,
                    'AssetsAsset.parent_asset_id' => $assetId,
                ],
            ]);
        }

        return $query;
    }

}
