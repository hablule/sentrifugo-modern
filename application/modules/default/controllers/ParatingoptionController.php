<?php

/**
 * PA Rating Option Controller
 * Strictly adheres to SSOT v6.2.
 */
class Default_ParatingoptionController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserRole;
    protected $_loginuserGroup;
    protected $_model;
    protected $_options;

    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        $this->_model   = new Default_Model_Paratingoption();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = $user->id;
            $this->_loginuserRole  = $user->emprole;
            $this->_loginuserGroup = $user->group_id;

            $this->view->loginuserRole  = $this->_loginuserRole;
            $this->view->loginuserGroup = $this->_loginuserGroup;
        }

        require_once APPLICATION_PATH . '/modules/default/library/utilClasses/Util.php';
    }

    public function preDispatch()
    {
        $session = sapp_Global::_readSession();
        if (!isset($session)) {
            if ($this->getRequest()->isXmlHttpRequest()) die(Zend_Json::encode(array('login' => 'failed')));
            else $this->_redirect('');
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')
            ->addActionContext('delete', 'json')
            ->addActionContext('makeactiveinactive', 'json')
            ->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function indexAction()
    {
        try {
            $request = $this->getRequest();
            $call    = $request->getParam('call');
            if ($call == 'ajaxcall') $this->_helper->layout->disableLayout();

            $searchData = (string)$request->getParam('searchData', '');
            $sort       = $request->getParam('sort', 'ASC');
            $by         = $request->getParam('by', 'parent_scale');
            $page       = $request->getParam('page', 1);
            $perPage    = $request->getParam('per_page', PERPAGE);

            if ($request->getParam('refresh') == 'refresh') {
                $sort = 'ASC';
                $by = 'parent_scale';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, null);
            $this->view->dataArray = array($dataTmp);
            $this->view->call      = $call;
            $this->view->messages  = $this->_helper->flashMessenger->getMessages();
        } catch (\Throwable $e) {
            error_log("PaRatingOption Index Error: " . $e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, true);
        $this->view->id = $id;
        $this->view->controllername = 'paratingoption';
    }

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

    public function addAction()
    {
        $this->view->form = $this->_prepareForm();
        $this->render('save');
    }

    public function editAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $this->view->form = $this->_prepareForm($id, false);
        $this->render('save');
    }

    public function saveAction()
    {
        $form = new Default_Form_Paratingoption();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $parentModel = new Default_Model_Parating();
            $form->rating_id->addMultiOptions($parentModel->getPairs(true));

            if ($form->isValid($request->getPost())) {
                $id = (int)$request->getParam('id');

                // Custom Duplicate Check (Composite: rating_id + value)
                $duplicate = $this->_model->checkDuplicate('value', trim($request->getParam('value')), (int)$request->getParam('rating_id'), $id);
                if ($duplicate) {
                    $this->_helper->json(array('value' => 'This numeric value already exists for the selected parent scale.'));
                    return;
                }

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $data = array(
                        'rating_id'   => (int)$request->getParam('rating_id'),
                        'value'       => (float)$request->getParam('value'),
                        'name'        => trim($request->getParam('name')),
                        'icon_class'  => trim($request->getParam('icon_class')),
                        'description' => trim($request->getParam('description')),
                        'isactive'    => (int)$request->getParam('isactive', 1)
                    );

                    $actionFlag = ($id > 0) ? 2 : 1;
                    $savedId = $this->_model->saveRecord($data, $id);
                    $recordId = ($id > 0) ? $id : $savedId;

                    sapp_Global::logManager(APPRAISAL_SETTINGS, $actionFlag, $this->_loginUserId, $recordId);
                    $db->commit();

                    $msg = ($actionFlag == 1) ? 'Added' : 'Updated';
                    $this->_helper->flashMessenger->addMessage(array('success' => "Rating Option $msg successfully."));
                    $this->_helper->json(array('result' => 'saved', 'nomessage' => 'yes'));
                } catch (\Throwable $e) {
                    $db->rollBack();
                    error_log("PaRatingOption Save Error: " . $e->getMessage());
                    $this->_helper->json(array('result' => 'exception', 'message' => 'System error occurred.'));
                }
            } else {
                $this->_helper->json($form->getMessages());
            }
        }
    }

    // addpopupAction is omitted, as options are always created in context of their parent.

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            try {
                $this->_model->deleteRecord($id);
                sapp_Global::logManager(APPRAISAL_SETTINGS, 3, $this->_loginUserId, $id);
                $this->_helper->json(array('msgtype' => 'success', 'message' => 'Option deleted successfully.'));
            } catch (\Throwable $e) {
                $this->_helper->json(array('msgtype' => 'error', 'message' => $e->getMessage()));
            }
        } else {
            $this->_helper->json(array('msgtype' => 'error', 'message' => 'Invalid Request.'));
        }
    }

    public function makeactiveinactiveAction()
    {
        try {
            $id = (int)$this->getRequest()->getParam('id');
            $status = (int)$this->getRequest()->getParam('status');
            $this->_model->toggleStatus($id, $status);
            sapp_Global::logManager(APPRAISAL_SETTINGS, 4, $this->_loginUserId, $id);
            $this->_helper->json(array('result' => 'yes'));
        } catch (\Throwable $e) {
            $this->_helper->json(array('result' => 'no'));
        }
    }

    /* =================================================================================
     * GROUP 5: INTERNAL HELPERS
     * ================================================================================= */

    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Paratingoption();
        $parentModel = new Default_Model_Parating();
        $form->rating_id->addMultiOption('', 'Select Parent Scale')->addMultiOptions($parentModel->getPairs(true));

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');
            }
        }

        if ($readonly) {
            foreach ($form->getElements() as $el) $el->setAttrib('disabled', 'disabled');
            $form->removeElement('submit');
        }

        return $form;
    }
}
