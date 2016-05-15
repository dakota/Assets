<?php
/**
 * AssetsEventHandler
 *
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 */
namespace Assets\Event;

use Cake\Event\EventListenerInterface;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Croogo\Core\Croogo;
use Croogo\Core\Nav;

class AssetsEventHandler implements EventListenerInterface
{

    /**
     * implementedEvents
     */
    public function implementedEvents()
    {
        return [
            'Controller.AssetsAttachment.newAttachment' => [
                'callable' => 'onNewAttachment',
            ],
            'Croogo.setupAdminData' => [
                'callable' => 'onSetupAdminData',
            ],
            'Controller.Links.setupLinkChooser' => [
                'callable' => 'onSetupLinkChooser',
            ],
        ];
    }

    /**
     * Registers usage when new attachment is created and attached to a resource
     */
    public function onNewAttachment($event)
    {
        $controller = $event->subject;
        $request = $controller->request;
        $attachment = $event->data['attachment'];

        if (empty($request->data['AssetsAsset']['AssetsAssetUsage'])) {
            Log::error('No asset usage record to register');

            return;
        }

        $usage = $request->data['AssetsAsset']['AssetsAssetUsage'][0];
        $Usage = TableRegistry::get('Assets.AssetUsages');
        $data = $Usage->create([
            'asset_id' => $attachment['AssetsAsset']['id'],
            'model' => $usage['model'],
            'foreign_key' => $usage['foreign_key'],
            'featured_image' => $usage['featured_image'],
        ]);
        $result = $Usage->save($data);
        if (!$result) {
            Log::error('Asset Usage registration failed');
            Log::error(print_r($Usage->validationErrors, true));
        }
        $event->result = $result;
    }

    public function onSetupLinkChooser($event)
    {
        $linkChoosers = [];
        $linkChoosers['Images'] = [
            'title' => 'Asset Image Attachments',
            'description' => 'Assets Attachments with image mime type',
            'url' => [
                'prefix' => 'admin',
                'plugin' => 'Assets',
                'controller' => 'Attachments',
                '?' => [
                    'chooser_type' => 'image',
                    'chooser' => 1,
                    'keepThis' => true,
                    'TB_iframe' => true,
                    'height' => '400',
                    'width' => '600',
                ],
            ],
        ];
        $linkChoosers['Files'] = [
            'title' => 'Asset Files Attachments',
            'description' => 'Assets Attachments with other mime types, ie. pdf, xls, doc, etc.',
            'url' => [
                'prefix' => 'admin',
                'plugin' => 'Assets',
                'controller' => 'Attachments',
                '?' => [
                    'chooser_type' => 'file',
                    'chooser' => 1,
                    'keepThis' => true,
                    'TB_iframe' => true,
                    'height' => '400',
                    'width' => '600',
                ],
            ],
        ];

        Croogo::mergeConfig('Menus.linkChoosers', $linkChoosers);
    }

    /**
     * Setup admin data
     */
    public function onSetupAdminData($event)
    {
        Nav::add('media.children.attachments', [
            'title' => __d('croogo', 'Attachments'),
            'url' => [
                'prefix' => 'admin',
                'plugin' => 'Assets',
                'controller' => 'Attachments',
                'action' => 'index',
            ],
        ]);
    }

}
