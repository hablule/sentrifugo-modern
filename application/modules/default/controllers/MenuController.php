<?php

/**
 * Menu Management Controller
 * 
 * Manages the application's navigation structure and dashboard groups.
 * Refactored to SSOT v2.4 (Separation of Concerns: No Privilege logic here).
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_MenuController extends Zend_Controller_Action
{
    // SSOT Standard Properties
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
        $this->_model   = new Default_Model_Menu();

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
            if ($this->getRequest()->isXmlHttpRequest()) {
                echo Zend_Json::encode(['login' => 'failed']);
                die();
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('save', 'json')
            ->addActionContext('delete', 'json')
            ->initContext();
    }

    /* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL
     * ================================================================================= */

    public function indexAction()
    {
        try {
            $request = $this->getRequest();
            $call = $request->getParam('call');
            if ($call == 'ajaxcall') {
                $this->_helper->layout->disableLayout();
            }

            $refresh       = $request->getParam('refresh');
            $dashboardcall = $request->getParam('dashboradcall');
            $page          = $request->getParam('page', 1);
            $searchData    = $request->getParam('searchData');

            $sort    = $request->getParam('sort', 'ASC');
            $by      = $request->getParam('by', 'menuName');
            $perPage = ($dashboardcall == 'Yes')
                ? $request->getParam('per_page', DASHBOARD_PERPAGE)
                : $request->getParam('per_page', PERPAGE);

            if ($refresh == 'refresh') {
                $sort = 'DESC';
                $by = 'id';
                $page = 1;
                $searchData = '';
            }

            $dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

            $this->view->dataArray = [$dataTmp];
            $this->view->call      = $call;

            $messages = $this->_helper->flashMessenger->getMessages();            

            if (isset($_SESSION['menu_msg'])) {
                // Determine class (success/error)
                $cls = $_SESSION['menu_cls'] ?? 'success';
                
                // Append to standard messages array format
                $messages[] = [$cls => $_SESSION['menu_msg']];
                
                // Clear the session so it doesn't show again on manual refresh
                unset($_SESSION['menu_msg']);
                unset($_SESSION['menu_cls']);
            }

            // Pass the combined messages array to the view
            $this->view->messages = $messages;

        } catch (Exception $e) {
            if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
        }
    }

    public function viewAction()
    {
        $id = (int)$this->getRequest()->getParam('id');

        $form = $this->_prepareForm($id, true);

        if (!$form) {
            $this->view->nodata = "nodata";
        } else {
            $data = $this->_model->getRecordById($id);
            $this->view->isactive = ($data['isactive'] == 1) ? 'Active' : 'Inactive';            
            $this->view->isdefault = ($data['isdefault'] == 1) ? 'Active' : 'Inactive';
            $this->view->form = $form;
            $this->view->id = $id;
        }

        $this->view->controllername = 'menu';
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
        $this->view->form = $this->_prepareForm($id);
        $this->render('save');
    }

    public function saveAction()
    {
        $form = new Default_Form_Menu();
        $request = $this->getRequest();
        $response = ['result' => 'error', 'message' => 'Invalid data'];

        if ($request->isPost()) {
            // Must populate dropdowns before validation so 'InArray' checks pass
            $this->_populateFormOptions($form);

            if ($form->isValid($request->getPost())) {

                $db = Zend_Db_Table::getDefaultAdapter();
                $db->beginTransaction();

                try {
                    $id = (int)$request->getParam('id');
                    $util = new utlis();

                    // Format URL safely (Ensure leading slash unless it's the # placeholder)
                    $urlRaw = trim($request->getParam('url'));
                    $url = (!empty($urlRaw)) ? (str_starts_with($urlRaw, '/') ? $urlRaw : '/' . $urlRaw) : '/#';

                    $parentId = (int)$request->getParam('parent');
                    $menuOrder = (int)$request->getParam('menuOrder', 1);

                    // 1. Prepare Core Menu Data
                    $menuData = [
                        'menuName'     => trim($request->getParam('menuName')),
                        'description'  => trim($request->getParam('description')), 
                        'url'          => $url,
                        'parent'       => $parentId,
                        'modulename'   => $request->getParam('modulename'),
                        'menugroup_id' => (int)$request->getParam('menugroup_id'),
                        'isactive'     => (int)$request->getParam('isactive'),
                        'isdefault'    => (int)$request->getParam('isdefault'),
                        'helpText'     => trim($request->getParam('helpText')),
                        'toolTip'      => trim($request->getParam('toolTip')),
                        'menuOrder'    => $menuOrder,
                        'segment_flag' => (int)$request->getParam('segment_flag', 2)
                    ];

                    if ($form->iconPath->isUploaded()) {
                        $form->iconPath->receive();
                        $menuData['iconPath'] = basename($form->iconPath->getFileName());
                    }

                    $actionFlag = 2; // Edit
                    if (!$id) {
                        $actionFlag = 1; // Add
                        $menuId = $this->_model->saveRecord($menuData);
                    } else {
                        $this->_model->saveRecord($menuData, $id);
                        $menuId = $id;
                    }

                    // ==============================================================
                    // Deep Materialized & Lexicographical Path Generation
                    // ==============================================================
                    $navIds = ",{$menuId},"; 
                    $paddedOrder = str_pad((int)$menuData['menuOrder'], 5, '0', STR_PAD_LEFT);
                    $orderPath = "{$paddedOrder}";

                    if ($parentId != 0) {
                        $parentRecord = $this->_model->getRecordById($parentId);
                        if ($parentRecord && !empty($parentRecord['nav_ids'])) {
                            // Parent already has a trailing comma (e.g., ",391,397,")
                            $navIds = $parentRecord['nav_ids'] . $menuId . ",";
                            // Append zero-padded order to parent's order path (e.g., "00001" + ".00005")
                            $orderPath = $parentRecord['order_path'] . "." . $paddedOrder;
                        }
                    }

                    // Trigger the single, consolidated recursive update in the Model
                    $this->_model->updateHierarchyPaths($menuId, $navIds, $orderPath);

                    // 3. Logging
                    $menuLogModel = new Default_Model_Menu();
                    $objidArr  = $menuLogModel->getMenuObjID('/menu');
                    $objID     = $objidArr[0]['id'] ?? MAINMENU; 

                    sapp_Global::logManager($objID, $actionFlag, $this->_loginUserId, $menuId);

                    $db->commit();

                    $response = [
                        'result'    => 'saved',
                        'message'   => "Menu " . ($actionFlag == 1 ? "Added" : "Updated") . " successfully."
                    ];

                    $_SESSION['menu_msg'] = $response['message'];
                    $_SESSION['menu_cls'] = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log("Sentrifugo Menu Save Error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
                    if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
                    $response = ['result' => 'exception', 'message' => 'Error saving menu.'];
                }
            } else {
                $response['message'] = $form->getMessages();
            }
        }

        $this->_helper->json($response);
    }

    /* =================================================================================
     * GROUP 4: REMOVAL
     * ================================================================================= */

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('objid');
        if ($id) {
            $this->_model->deleteRecord($id);
            // Log deletion
            sapp_Global::logManager(MAINMENU, 3, $this->_loginUserId, $id);
            $this->_helper->json(['msgtype' => 'success', 'message' => 'Menu deleted successfully.']);
        } else {
            $this->_helper->json(['msgtype' => 'error', 'message' => 'Invalid Request.']);
        }
    }

    /* =================================================================================
     * GROUP 5: PRIVATE HELPERS
     * ================================================================================= */

    /**
     * Prepares the form for Add/Edit/View actions.
     */
    private function _prepareForm($id = null, $readonly = false)
    {
        $form = new Default_Form_Menu();
        $this->_populateFormOptions($form);

        if ($id) {
            $data = $this->_model->getRecordById($id);
            if ($data) {
                // Ensure form element names match DB columns for auto-population
                $form->populate($data);
                $form->getElement('submit')->setLabel('Update');
                $form->setAttrib('action', BASE_URL . 'menu/save/id/' . $id);
                $this->view->currentIcon = $data['iconPath'];
            } else {
                return null;
            }
        } else {
            $form->setAttrib('action', BASE_URL . 'menu/save');
        }

        if ($readonly) {
            foreach ($form->getElements() as $el) {
                $el->setAttrib('disabled', 'disabled');
            }
            $form->removeElement('submit');
        }

        return $form;
    }

    /**
     * Populates dynamic dropdown elements in the form.
     */
    private function _populateFormOptions(Zend_Form $form)
    {
        // 1. Load Application Modules (Physical Directories)
        $path = APPLICATION_PATH . '/modules/';
        $modules = array_filter(glob($path . '*'), 'is_dir');

        $form->modulename->addMultiOption('', 'Select Module...');
        foreach ($modules as $module) {
            $name = basename($module);
            $form->modulename->addMultiOption($name, $name);
        }

        // 2. Load Parent Menus (Self-Referential)
        $form->parent->addMultiOption(0, "No Parent (Top Level)");
        $menus = $this->_model->getPairs(true);
        $form->parent->addMultiOptions($menus);

        // 3. Load Visual Dashboard Menu Groups
        $groupModel = new Default_Model_Menugroup();
        $form->menugroup_id->addMultiOption('', "Select Dashboard Group...");
        $form->menugroup_id->addMultiOptions($groupModel->getPairs(true));
    }
}
