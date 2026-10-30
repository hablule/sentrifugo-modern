<?php

/**
 * Requisition Position Controller
 *
 * Handles pipeline status transitions (Targeted -> Filled -> Cancelled) and writes to the Event Ledger.
 * Complies with SSOT v6.7.7.
 */
class Default_ReqpositionController extends Zend_Controller_Action
{
    protected $_loginUserId;
    protected $_loginuserGroup;
    protected $_loginuserRole;
    protected $_model;
    protected $_options;

    public function init()
    {
        $this->_model   = new Default_Model_Reqposition();
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $user = $auth->getStorage()->read();
            $this->_loginUserId    = (int)$user->id;
            $this->_loginuserRole  = (int)$user->emprole;
            $this->_loginuserGroup = (int)$user->group_id;

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
                echo Zend_Json::encode(array('login' => 'failed'));
                exit;
            } else {
                $this->_redirect('');
            }
        }

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('updatestatus', 'json')->initContext();
    }

    /**
     * AJAX Action: Updates a specific seat's pipeline status and logs the event to log_requisition_position_status.
     */
    public function updatestatusAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->_helper->json(array('result' => 'error', 'message' => 'Invalid request method.'));
        }

        $form = new Default_Form_Reqpositionstatus();

        // Populate reasons
        $reasonModel = new Default_Model_Reqstatusreason();
        $form->status_reason_id->addMultiOptions($reasonModel->getPairs(true));

        if (class_exists('Default_Model_Employee')) {
            $empModel = new Default_Model_Employee();
            $form->filled_by_employee_id->addMultiOptions($empModel->getPairs(true));
        }

        if ($form->isValid($request->getPost())) {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try {
                $mapId     = (int) $request->getParam('map_req_pos_id');
                $newStatus = trim((string) $request->getParam('status'));
                $reasonId  = (int) $request->getParam('status_reason_id');
                $filledEmp = $request->getParam('filled_by_employee_id');
                $notes     = trim((string) $request->getParam('notes', ''));

                // Update Seat Status on map_requisition_position
                $this->_model->update(
                    array(
                        'status'       => $newStatus,
                        'modifiedby'   => $this->_loginUserId,
                        'modifieddate' => gmdate("Y-m-d H:i:s")
                    ),
                    array('id = ?' => $mapId)
                );

                // Write to Immutable Event Ledger
                $db->insert('log_requisition_position_status', array(
                    'map_req_pos_id'        => $mapId,
                    'status'                => $newStatus,
                    'status_reason_id'      => $reasonId,
                    'filled_by_employee_id' => !empty($filledEmp) ? (int)$filledEmp : null,
                    'notes'                 => !empty($notes) ? $notes : null,
                    'createdby'             => $this->_loginUserId,
                    'createddate'           => gmdate("Y-m-d H:i:s")
                ));

                $db->commit();
                $this->_helper->json(array('result' => 'saved', 'message' => 'Pipeline status updated successfully.'));
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log("Status Transition Error: " . $e->getMessage());
                $this->_helper->json(array('result' => 'exception', 'message' => $e->getMessage()));
            }
        } else {
            $this->_helper->json(array('result' => 'error', 'message' => 'Please provide a valid status and reason.'));
        }
    }
}
