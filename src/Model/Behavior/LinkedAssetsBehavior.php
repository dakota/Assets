<?php

namespace Assets\Model\Behavior;

class LinkedAssetsBehavior extends ModelBehavior
{

    public function setup(Model $model, $config = [])
    {
        $config = Hash::merge([
            'key' => 'LinkedAsset',
        ], $config);
        $this->settings[$model->alias] = $config;

        $model->bindModel([
            'hasMany' => [
                'AssetsAssetUsage' => [
                    'className' => 'Assets.AssetsAssetUsage',
                    'foreignKey' => 'foreign_key',
                    'dependent' => true,
                    'conditions' => [
                        'model' => $model->alias,
                    ],
                ],
            ],
        ], false);
    }

    public function beforeFind(Model $model, $query)
    {
        if (!isset($query['contain'])) {
            $contain = [];
            $relationCheck = ['belongsTo', 'hasMany', 'hasOne', 'hasAndBelongsToMany'];
            foreach ($relationCheck as $relation) {
                if ($model->{$relation}) {
                    $contain = Hash::merge($contain, array_keys($model->{$relation}));
                }
            }
            if ($model->recursive >= 0 || $query['recursive'] >= 0) {
                $query = Hash::merge(['contain' => $contain], $query);
            }
        }
        if (isset($query['contain'])) {
            if (!isset($query['contain']['AssetsAssetUsage'])) {
                $query['contain']['AssetsAssetUsage'] = 'AssetsAsset';
            }
        }

        return $query;
    }

    public function afterFind(Model $model, $results, $primary = true)
    {
        if (!$primary) {
            return $results;
        }
        $key = 'LinkedAssets';

        if (isset($model->AssetsAsset)) {
            $Asset = $model->AssetsAsset;
        } else {
            $Asset = ClassRegistry::init('Assets.AssetsAsset');
        }
        foreach ($results as &$result) {
            $result[$key] = [];
            if (empty($result['AssetsAssetUsage'])) {
                unset($result['AssetsAssetUsage']);
                continue;
            }
            foreach ($result['AssetsAssetUsage'] as &$asset) {
                if (empty($asset['AssetsAsset'])) {
                    continue;
                }
                if (empty($asset['type'])) {
                    $result[$key]['DefaultAsset'][] = $asset['AssetsAsset'];
                } elseif ($asset['type'] === 'FeaturedImage') {
                    $result[$key][$asset['type']] = $asset['AssetsAsset'];

                    $seedId = isset($asset['AssetsAsset']['parent_asset_id']) ?
                        $asset['AssetsAsset']['parent_asset_id'] : $asset['AssetsAsset']['id'];
                    $relatedAssets = $Asset->find('all', [
                        'recursive' => -1,
                        'order' => 'width DESC',
                        'conditions' => [
                            'AssetsAsset.parent_asset_id' => $seedId,
                        ],
                    ]);
                    foreach ($relatedAssets as $related) {
                        $result[$key]['FeaturedImage']['Versions'][] = $related['AssetsAsset'];
                    }
                } else {
                    $result[$key][$asset['type']][] = $asset['AssetsAsset'];
                }
            }
            unset($result['AssetsAssetUsage']);
        }

        return $results;
    }

    /**
     * Import $path as $model's asset and automatically registers its usage record
     *
     * This method is intended for importing an existing file in the local
     * filesystem into Assets plugin with automatic usage record with the calling
     * model.
     *
     * Eg:
     *
     *   $Book = ClassRegistry::init('Book');
     *   $Book->Behaviors->load('Assets.LinkedAssets');
     *   $Book->importAsset('LocalAttachment', '/path/to/file');
     *
     * @param string $adapter Adapter name
     * @param string $path Path to file, relative from WWW_ROOT
     * @return bool
     */
    public function importAsset(Model $model, $adapter, $path, $options = [])
    {
        $options = Hash::merge([
            'usage' => [],
        ], $options);
        $Attachment = ClassRegistry::init('Assets.AssetsAttachment');
        $attachment = $Attachment->createFromFile(WWW_ROOT . $path);

        if (!is_array($attachment)) {
            $this->log($attachment);

            return false;
        }

        $originalPath = WWW_ROOT . $path;
        $fp = fopen($originalPath, 'r');
        $stat = fstat($fp);
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $attachment['AssetsAsset'] = [
            'model' => $Attachment->alias,
            'adapter' => $adapter,
            'file' => [
                'name' => basename($originalPath),
                'tmp_name' => $originalPath,
                'type' => $finfo->file($originalPath),
                'size' => $stat['size'],
                'error' => UPLOAD_ERR_OK,
            ],
        ];
        $attachment = $Attachment->saveAll($attachment);

        $Attachment->AssetsAsset->recursive = -1;
        $asset = $Attachment->AssetsAsset->find('first', [
            'conditions' => [
                'model' => $Attachment->alias,
                'foreign_key' => $Attachment->id,
            ],
        ]);

        $Usage = $Attachment->AssetsAsset->AssetsAssetUsage;

        $usage = Hash::merge($options['usage'], [
            'asset_id' => $asset['AssetsAsset']['id'],
            'model' => $model->alias,
            'foreign_key' => $model->id,
        ]);
        $usage = $Usage->create($usage);

        $usage = $Usage->save($usage);
        if ($usage) {
            return true;
        }

        return false;
    }

}
