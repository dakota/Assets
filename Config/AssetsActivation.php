<?php

/**
 * Assets Activation
 *
 * Activation class for Assets plugin.
 *
 * @author   Rachman Chavik <contact@xintesa.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 */
namespace Assets\Config;

use Cake\ORM\TableRegistry;
use Croogo\Extensions\CroogoPlugin;

class AssetsActivation
{

    /**
     * onActivate will be called if this returns true
     *
     * @param  object $controller Controller
     * @return boolean
     */
    public function beforeActivation()
    {
        if (!Plugin::loaded('Imagine')) {
            Plugin::load('Imagine');
            $plugin = new CroogoPlugin();
            $plugin->addBootstrap('Imagine');
            Plugin::load('Imagine');
            Log::info('Imagine plugin added to bootstrap');
        }

        return true;
    }

    /**
     * Creates the necessary settings
     *
     * @param object $controller Controller
     * @return void
     */
    public function onActivation()
    {
        $CroogoPlugin = new CroogoPlugin();
        $result = $CroogoPlugin->migrate('Assets');
        if ($result) {
            $settings = TableRegistry::get('Croogo/Settings.Setting');
            $settings->write('Assets.installed', true);
        }

        return $result;
    }

    /**
     * onDeactivate will be called if this returns true
     *
     * @param  object $controller Controller
     * @return boolean
     */
    public function beforeDeactivation()
    {
        return true;
    }

    /**
     * onDeactivation
     *
     * @param object $controller Controller
     * @return void
     */
    public function onDeactivation()
    {
        $settings = TableRegistry::get('Croogo/Settings.Setting');
        $settings->deleteKey('Assets.installed');
    }

}
