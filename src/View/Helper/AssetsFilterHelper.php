<?php

namespace Assets\View\Helper;

use Cake\View\View;
use Croogo\Core\View\Helper\CroogoAppHelper;

class AssetsFilterHelper extends CroogoAppHelper
{

    public $helpers = [
        'Html',
        'Nodes.Nodes',
    ];

    public function __construct(View $view, $settings = [])
    {
        parent::__construct($view);
        $this->_setupEvents();
    }

    protected function _setupEvents()
    {
        $events = [
            'Helper.Layout.beforeFilter' => [
                'callable' => 'filter',
                'passParams' => true,
            ],
        ];
        $eventManager = $this->_View->eventManager();
        foreach ($events as $name => $config) {
            $eventManager->on($name, [$this, 'filter'], $config);
        }
    }

    public function afterSetNode()
    {
        $body = $this->Nodes->field('body');
        $body = $this->filter($body, [
            'model' => 'Node',
            'id' => $this->Nodes->field('id'),
        ]);
        $this->Nodes->field('body', $body);
    }

    public function filter(&$content, $options = [])
    {
        $conditions = [];
        $identifier = '';
        if (isset($options['model']) && isset($options['id'])) {
            $conditions = [
                'AssetsAssetUsage.model' => $options['model'],
                'AssetsAssetUsage.foreign_key' => $options['id'],
            ];
            $identifier = $options['model'] . '.' . $options['id'];
        }

        preg_match_all('/\[(image):[ ]*([A-Za-z0-9_\-]*)(.*?)\]/i', $content, $tagMatches);
        $Asset = ClassRegistry::init('Assets.AssetsAssetUsage');

        for ($i = 0, $ii = count($tagMatches[1]); $i < $ii; $i++) {
            $assets = $this->parseString('image|i', $tagMatches[0][$i]);
            $assetId = $tagMatches[2][$i];
            $conditions['AssetsAssetUsage.id'] = $assetId;
            $asset = $Asset->find('first', [
                'recursive' => -1,
                'contain' => ['AssetsAsset'],
                'conditions' => $conditions,
            ]);
            if (empty($asset['AssetsAsset'])) {
                $this->log(sprintf('%s - Asset not found for %s', $identifier, $tagMatches[0][$i]));
                $regex = '/' . preg_quote($tagMatches[0][$i]) . '/';
                $content = preg_replace($regex, '', $content);
                continue;
            }

            $options = isset($assets[$assetId]) ? $assets[$assetId] : [];
            $img = $this->Html->image($asset['AssetsAsset']['path'], $options);
            $regex = '/' . preg_quote($tagMatches[0][$i]) . '/';
            $content = preg_replace($regex, $img, $content);
        }

        return $content;
    }

    /**
     * Parses bb-code like string.
     *
     * Example: string containing [menu:main option1="value"] will return an array like
     *
     * Array
     * (
     *     [main] => Array
     *         (
     *             [option1] => value
     *         )
     * )
     *
     * @param string $exp
     * @param string $text
     * @param array $options
     * @return array
     */
    public function parseString($exp, $text, $options = [])
    {
        $_options = [
            'convertOptionsToArray' => false,
        ];
        $options = array_merge($_options, $options);

        $output = [];
        preg_match_all('/\[(' . $exp . '):([A-Za-z0-9_\-]*)(.*?)\]/i', $text, $tagMatches);
        for ($i = 0, $ii = count($tagMatches[1]); $i < $ii; $i++) {
            $regex = '/(\S+)=[\'"]?((?:.(?![\'"]?\s+(?:\S+)=|[>\'"]))+.)[\'"]?/i';
            preg_match_all($regex, $tagMatches[3][$i], $attributes);
            $alias = $tagMatches[2][$i];
            $aliasOptions = [];
            for ($j = 0, $jj = count($attributes[0]); $j < $jj; $j++) {
                $aliasOptions[$attributes[1][$j]] = $attributes[2][$j];
            }
            if ($options['convertOptionsToArray']) {
                foreach ($aliasOptions as $optionKey => $optionValue) {
                    if (!is_array($optionValue) && strpos($optionValue, ':') !== false) {
                        $aliasOptions[$optionKey] = $this->stringToArray($optionValue);
                    }
                }
            }
            $output[$alias] = $aliasOptions;
        }

        return $output;
    }

    /**
     * Converts formatted string to array
     *
     * A string formatted like 'Node.type:blog;' will be converted to
     * array('Node.type' => 'blog');
     *
     * @param string $string in this format: Node.type:blog;Node.user_id:1;
     * @return array
     */
    public function stringToArray($string)
    {
        $string = explode(';', $string);
        $stringArr = [];
        foreach ($string as $stringElement) {
            if ($stringElement != null) {
                $stringElementE = explode(':', $stringElement);
                if (isset($stringElementE['1'])) {
                    $stringArr[$stringElementE['0']] = $stringElementE['1'];
                } else {
                    $stringArr[] = $stringElement;
                }
            }
        }

        return $stringArr;
    }

}
