<?php

/**
 * Attachments Controller
 *
 * This file will take care of file uploads (with rich text editor integration).
 *
 * @category Assets.Controller
 * @package  Assets.Controller
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @author   Rachman Chavik <contact@xintesa.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
namespace Assets\Controller\Admin;

use Cake\Event\Event;

class AttachmentsController extends AssetsAppController
{

    /**
     * Models used by the Controller
     *
     * @var array
     * @access public
     */
    public $uses = ['Assets.AssetsAttachment'];

    /**
     * Helpers used by the Controller
     *
     * @var array
     * @access public
     */
    public $helpers = ['Croogo/FileManager.FileManager', 'Assets.AssetsImage'];

    public $presetVars = true;

    public function initialize()
    {
        $this->loadComponent('Search.Prg');
        parent::initialize();
    }

    /**
     * Before executing controller actions
     *
     * @return void
     * @access public
     */
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $noCsrfCheck = ['add', 'resize'];
        if (in_array($this->action, $noCsrfCheck)) {
            $this->Security->csrfCheck = false;
        }
        if ($this->action == 'resize') {
            $this->Security->validatePost = false;
        }
    }

    /**
     * Admin add
     *
     * @return void
     * @access public
     */
    public function add()
    {
        $this->set('title_for_layout', __d('croogo', 'Add Attachment'));

        if (isset($this->request->params['named']['editor'])) {
            $this->layout = 'popup';
        }

        if ($this->request->is('post') || !empty($this->request->data)) {

            if (empty($this->data['AssetsAttachment'])) {
                $this->AssetsAttachment->invalidate('file',
                    __d('croogo', 'Upload failed. Please ensure size does not exceed the server limit.'));

                return;
            }

            $this->AssetsAttachment->create();
            $saved = $this->AssetsAttachment->saveAll($this->request->data, ['deep' => true]);

            if ($saved) {
                $this->Session->setFlash(__d('croogo', 'The Attachment has been saved'), 'flash',
                    ['class' => 'success']);
                $url = [];
                if (isset($this->request->data['AssetsAsset']['AssetsAssetUsage'][0])) {
                    $usage = $this->request->data['AssetsAsset']['AssetsAssetUsage'][0];
                    if (!empty($usage['model']) && !empty($usage['foreign_key'])) {
                        $url['?']['model'] = $usage['model'];
                        $url['?']['foreign_key'] = $usage['foreign_key'];
                    }
                }
                if (isset($this->request->params['named']['editor'])) {
                    $url = array_merge($url, ['action' => 'browse']);
                } else {
                    $url = array_merge($url, ['action' => 'index']);
                }

                return $this->redirect($url);
            } else {
                $this->Session->setFlash(__d('croogo', 'The Attachment could not be saved. Please, try again.'),
                    'flash', ['class' => 'error']);
            }
        }
    }

    /**
     * Admin edit
     *
     * @param int $id
     * @return void
     * @access public
     */
    public function edit($id = null)
    {
        $this->set('title_for_layout', __d('croogo', 'Edit Attachment'));

        if (isset($this->request->params['named']['editor'])) {
            $this->layout = 'popup';
        }

        $redirect = ['action' => 'index'];
        if (!empty($this->request->query)) {
            $redirect = array_merge($redirect, ['action' => 'browse', '?' => $this->request->query]);
        }

        if (!$id && empty($this->request->data)) {
            $this->Session->setFlash(__d('croogo', 'Invalid Attachment'), 'flash', ['class' => 'error']);

            return $this->redirect($redirect);
        }
        if (!empty($this->request->data)) {
            if ($this->AssetsAttachment->save($this->request->data)) {
                $this->Session->setFlash(__d('croogo', 'The Attachment has been saved'), 'flash',
                    ['class' => 'success']);

                return $this->redirect($redirect);
            } else {
                $this->Session->setFlash(__d('croogo', 'The Attachment could not be saved. Please, try again.'),
                    'flash', ['class' => 'error']);
            }
        }
        if (empty($this->request->data)) {
            $this->request->data = $this->AssetsAttachment->read(null, $id);
        }
    }

    /**
     * Admin delete
     *
     * @param int $id
     * @return void
     * @access public
     */
    public function delete($id = null)
    {
        if (!$id) {
            $this->Session->setFlash(__d('croogo', 'Invalid id for Attachment'), 'flash', ['class' => 'error']);

            return $this->redirect(['action' => 'index']);
        }

        $redirect = ['action' => 'index'];
        if (!empty($this->request->query)) {
            $redirect = array_merge($redirect, ['action' => 'browse', '?' => $this->request->query]);
        }

        $this->AssetsAttachment->begin();
        if ($this->AssetsAttachment->delete($id)) {
            $this->AssetsAttachment->commit();
            $this->Session->setFlash(__d('croogo', 'Attachment deleted'), 'flash', ['class' => 'success']);

            return $this->redirect($redirect);
        } else {
            $this->Session->setFlash(__d('croogo', 'Invalid id for Attachment'), 'flash', ['class' => 'error']);

            return $this->redirect($redirect);
        }
    }

    /**
     * Admin browse
     *
     * @return void
     * @access public
     */
    public function browse()
    {
        $this->layout = 'popup';
        $this->index();
    }

    public function lists()
    {
        $this->paginate = [
            'modelAttachments',
            'model' => $this->request->query['model'],
            'foreign_key' => $this->request->query['foreign_key'],
        ];
        if ($this->request->is('ajax')) {
            $this->layout = 'ajax';
            $this->paginate['limit'] = 100;
        }
        $attachments = $this->paginate();
        $this->set(compact('attachments'));
    }

    public function resize($id = null)
    {
        if (empty($id)) {
            throw new NotFoundException('Missing Asset Id to resize');
        }

        $result = false;
        if (!empty($this->request->data)) {
            $width = $this->request->data['width'];
            try {
                $result = $this->AssetsAttachment->createResized($id, $width, null);
            } catch (Exception $e) {
                $result = $e->getMessage();
            }
        }

        $this->set(compact('result'));
        $this->set('_serialize', 'result');
    }

}
