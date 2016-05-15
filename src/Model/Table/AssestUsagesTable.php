<?php

/**
 * AssetsAssetUsage Model
 *
 */
namespace Assets\Model\Table;

use Cake\ORM\Table;

class AssestUsagesTable extends Table
{
    public function initialize(array $config)
    {
        $this->table('asset_usages');

        $this->belongsTo('Assets', [
            'className' => 'Assets.Assets',
            'dependent' => true,
            'foreignKey' => 'asset_id',
        ]);

        $this->addBehavior('Croogo.Trackable');
        $this->addBehavior('Timestamp');
        parent::initialize($config);
    }

    public function beforeSave($options = [])
    {
        if (!empty($this->data['AssetsAssetUsage']['featured_image'])) {
            $this->data['AssetsAssetUsage']['type'] = 'FeaturedImage';
            unset($this->data['AssetsAssetUsage']['featured_image']);
        }

        return true;
    }

}
