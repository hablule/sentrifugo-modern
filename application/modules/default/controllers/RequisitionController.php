<?php

/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

/**
 * Requisition Controller (The Master Campaign)
 *
 * Manages recruitment headcount requests, contextual status tabs, and child seat pipelines.
 * Complies with SSOT v6.7.7 (Master-Detail JSON Pattern, Status Tab Filtering).
 */
class Default_RequisitionController extends Zend_Controller_Action
{
	protected $_loginUserId;
	protected $_loginuserGroup;
	protected $_loginuserRole;
	protected $_model;
	protected $_options;

	/* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

	public function init()
	{
		$this->_model   = new Default_Model_Requisition();
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
				echo Zend_Json::encode(['login' => 'failed']);
				exit;
			} else {
				$this->_redirect('');
			}
		}

		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('save', 'json')
			->addActionContext('delete', 'json')
			->addActionContext('updatestatus', 'json')
			->initContext();
	}

	/* =================================================================================
     * GROUP 2: DATA DISPLAY & RETRIEVAL (CONTEXTUAL SUMMARY TABS)
     * ================================================================================= */

	public function indexAction()
	{
		try {
			$request = $this->getRequest();
			$call    = (string) $request->getParam('call', '');

			if ($call === 'ajaxcall') {
				$this->_helper->layout->disableLayout();
			}

			// Contextual Tab State: 'action_required', 'active_pipelines', or 'concluded'
			$statusFilter = (string) $request->getParam('status_filter', 'action_required');
			$this->view->activeTab = $statusFilter;

			$refresh       = (string) $request->getParam('refresh', '');
			$dashboardcall = (string) $request->getParam('dashboradcall', '');
			$page          = (int) $request->getParam('page', 1);
			$searchData    = (string) $request->getParam('searchData', '');

			$sort    = (string) $request->getParam('sort', 'DESC');
			$by      = (string) $request->getParam('by', 'id');

			$perPage = ($dashboardcall === 'Yes')
				? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
				: (int) $request->getParam('per_page', PERPAGE);

			if ($refresh === 'refresh') {
				$sort = 'DESC';
				$by = 'id';
				$page = 1;
				$searchData = '';
			}

			$this->view->tabCounts = $this->_model->getTabCounts();

			$dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall, $statusFilter);

			$this->view->dataArray = [$dataTmp];
			$this->view->call      = $call;
			$this->view->messages  = $this->_helper->flashMessenger->getMessages();
		} catch (\Throwable $e) {
			if (APPLICATION_ENV === 'development') Zend_Debug::dump($e->getMessage());
		}
	}

	public function viewAction()
	{
		$id = (int) $this->getRequest()->getParam('id', 0);
		$form = $this->_prepareForm($id, true);

		if (!$form) {
			$this->view->nodata = "nodata";
		} else {
			$this->view->form = $form;
			$this->view->id   = $id;

			// Load mapped positions
			$reqPosModel = new Default_Model_Reqposition();
			$this->view->positions = $reqPosModel->getPositionsByRequisitionId($id);

			// Load Immutable Event Log
            $this->view->logs = $reqPosModel->getEventLogByRequisitionId($id);
		}

		$this->view->controllername = 'requisition';
	}

	/* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE (MASTER-DETAIL SYNC)
     * ================================================================================= */

	public function addAction()
	{
		if (defined('REQUISITION_MENU_ID') && sapp_Global::_checkprivileges(REQUISITION_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
			$this->_redirect('error/privilege');
		}

		$this->view->form = $this->_prepareForm();

		// Inject empty child form and position array for Tab 2
		$this->_setupChildFormContext();
		$this->view->existingPositions = json_encode([]);

		$this->render('save');
	}

	public function editAction()
	{
		if (defined('REQUISITION_MENU_ID') && sapp_Global::_checkprivileges(REQUISITION_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
			$this->_redirect('error/privilege');
		}

		$id = (int) $this->getRequest()->getParam('id', 0);
		$form = $this->_prepareForm($id);

		if (!$form) {
			$this->view->nodata = "nodata";
		} else {
			$this->view->form = $form;
			$this->view->id   = $id;

			$this->_setupChildFormContext($id);
			$reqPosModel = new Default_Model_Reqposition();
			$this->view->existingPositions = json_encode($reqPosModel->getPositionsByRequisitionId($id));
		}

		$this->render('save');
	}

	public function saveAction()
	{
		$request = $this->getRequest();
		if (!$request->isPost()) {
			return $this->_redirect('requisition/index');
		}

		$id = (int) $request->getParam('id', 0);
		$form = $this->_prepareForm($id);

		if ($form->isValid($request->getPost())) {

			$db = Zend_Db_Table::getDefaultAdapter();
			$db->beginTransaction();

			try {
				$recruiterId = $request->getParam('assigned_recruiter_id');

				$data = [
					'code'                     => trim((string) $request->getParam('code', '')),
					'requested_by_employee_id' => (int) $request->getParam('requested_by_employee_id'),
					'assigned_recruiter_id'    => !empty($recruiterId) ? (int)$recruiterId : null,
					'requested_fte'            => (float) $request->getParam('requested_fte', 1.00),
					'status'                   => trim((string) $request->getParam('status', 'Draft')),
					'description'              => trim((string) $request->getParam('description', '')),
					'isactive'                 => (int) $request->getParam('isactive', 1)
				];

				$actionFlag = 2;
				$saveMode   = 'updated';

				if (!$id) {
					$actionFlag = 1;
					$saveMode   = 'added';
					$savedId    = $this->_model->saveRecord($data);
				} else {
					$this->_model->saveRecord($data, $id);
					$savedId    = $id;
				}

				// --- SSOT VII.24: SYNCHRONIZE CHILD SEATS PAYLOAD ---
				$positionsPayload = $request->getParam('positions_payload');
				if (!empty($positionsPayload)) {
					$positionsArray = json_decode($positionsPayload, true);
					if (is_array($positionsArray)) {
						$reqPosModel = new Default_Model_Reqposition();
						$reqPosModel->syncPositions($savedId, $positionsArray);
					}
				}

				if (defined('REQUISITION_MENU_ID')) {
					sapp_Global::logManager(REQUISITION_MENU_ID, $actionFlag, $this->_loginUserId, $savedId);
				}

				$db->commit();

				$redirectTab = ($data['status'] === 'Approved') ? 'active_pipelines' : 'action_required';
				$this->_helper->flashMessenger->addMessage(['success' => "Requisition campaign {$saveMode} successfully."]);
				$this->_helper->json(['result' => 'saved', 'nomessage' => 'yes', 'redirect_url' => \BASE_URL . '/requisition/index/status_filter/' . $redirectTab]);
			} catch (\Throwable $e) {
				$db->rollBack();
				error_log("Requisition Save Error: " . $e->getMessage());
				$this->_helper->json(['result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error']);
			}
		} else {
			$msgarray = [];
			foreach ($form->getMessages() as $field => $errors) {
				$msgarray[$field] = current($errors);
			}
			$this->_helper->json($msgarray);
		}
	}

	/* =================================================================================
     * GROUP 4: REMOVAL
     * ================================================================================= */

	public function deleteAction()
	{
		$id = (int) $this->getRequest()->getParam('objid', 0);

		if ($id) {
			if (defined('REQUISITION_MENU_ID') && sapp_Global::_checkprivileges(REQUISITION_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
				return $this->_helper->json(['result' => 'error', 'message' => 'Permission Denied.']);
			}

			try {
				if ($this->_model->checkInUse($id)) {
					throw new Exception("This Requisition has moved past Draft status and cannot be deleted. Mark it as 'Cancelled' instead.");
				}

				$this->_model->deleteRecord($id);
				if (defined('REQUISITION_MENU_ID')) {
					sapp_Global::logManager(REQUISITION_MENU_ID, 3, $this->_loginUserId, $id);
				}

				return $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success']);
			} catch (\Throwable $e) {
				error_log("Requisition Delete Error: " . $e->getMessage());
				return $this->_helper->json(['result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error']);
			}
		}

		return $this->_helper->json(['result' => 'error', 'message' => 'Invalid request']);
	}

	/* =================================================================================
     * GROUP 5: HELPERS & CHILD FORM SETUP
     * ================================================================================= */

	private function _setupChildFormContext($requisitionId = 0)
	{
		// 1. Setup child seat creation form
		$childForm = new Default_Form_Reqposition();

		// 2. Populate positions using prefixed element name: seat_position_id
		$posModel = new Default_Model_Position();
		if ($childForm->getElement('seat_position_id')) {
			$childForm->seat_position_id->addMultiOption('', 'Select Position Seat...');
			$childForm->seat_position_id->addMultiOptions($posModel->getPairs(true));
		}

		$this->view->childForm = $childForm;

		// 3. Setup pipeline status transition form
		$statusForm = new Default_Form_Reqpositionstatus();
		$reasonModel = new Default_Model_Reqstatusreason();

		if ($statusForm->getElement('status_reason_id')) {
			$statusForm->status_reason_id->addMultiOption('', 'Select Transition Reason...');
			$statusForm->status_reason_id->addMultiOptions($reasonModel->getPairs(true));
		}

		if (class_exists('Default_Model_Employee') && $statusForm->getElement('filled_by_employee_id')) {
			$empModel = new Default_Model_Employee();
			$statusForm->filled_by_employee_id->addMultiOption('', 'Select Hired Candidate...');
			$statusForm->filled_by_employee_id->addMultiOptions($empModel->getPairs(true));
		}

		$this->view->statusForm = $statusForm;
	}

	private function _prepareForm($id = null, $readonly = false)
	{
		$form = new Default_Form_Requisition();

		if (class_exists('Default_Model_Employee')) {
			$empModel = new Default_Model_Employee();
			$form->requested_by_employee_id->addMultiOption('', 'Select Hiring Manager...');
			$form->requested_by_employee_id->addMultiOptions($empModel->getPairs(true));

			$form->assigned_recruiter_id->addMultiOption('', 'Select Recruiter...');
			$form->assigned_recruiter_id->addMultiOptions($empModel->getPairs(true));
		}

		if ($id) {
			$data = $this->_model->getRecordById($id);
			if (!$data) return null;

			foreach ($data as $key => $val) {
				if ($val !== null) {
					$data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
				}
			}

			$form->populate($data);
			$form->getElement('submit')->setLabel('Update Requisition');
		}

		if ($readonly) {
			foreach ($form->getElements() as $element) {
				$element->setAttrib('disabled', 'disabled');
			}
			$form->removeElement('submit');
		}

		return $form;
	}

	/**
	 * AJAX Action: Updates a specific seat's pipeline status and logs to the Immutable Event Ledger.
	 */
	public function updatestatusAction()
	{
		$request = $this->getRequest();
		if (!$request->isPost()) {
			return $this->_helper->json(['result' => 'error', 'message' => 'Invalid request method.']);
		}

		$form = new Default_Form_Reqpositionstatus();

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

				$reqPosModel = new Default_Model_Reqposition();

				// 1. Update Seat Status on map_requisition_position
				$reqPosModel->update(
					[
						'status'       => $newStatus,
						'modifiedby'   => $this->_loginUserId,
						'modifieddate' => gmdate("Y-m-d H:i:s")
					],
					['id = ?' => $mapId]
				);

				// 2. Write to Immutable Event Ledger
				$db->insert('log_requisition_position_status', [
					'map_req_pos_id'        => $mapId,
					'status'                => $newStatus,
					'status_reason_id'      => $reasonId,
					'filled_by_employee_id' => !empty($filledEmp) ? (int)$filledEmp : null,
					'notes'                 => !empty($notes) ? $notes : null,
					'createdby'             => $this->_loginUserId,
					'createddate'           => gmdate("Y-m-d H:i:s")
				]);

				// Campaign Auto-Closure Check
				// Find the parent requisition ID
				$mapRow = $reqPosModel->fetchRow(['id = ?' => $mapId]);
				if ($mapRow) {
					$reqId = $mapRow->requisition_id;

					// Count how many seats are still open (not filled or cancelled)
					$openSeatsCount = $db->fetchOne("
                        SELECT COUNT(id) 
                        FROM map_requisition_position 
                        WHERE requisition_id = ? 
                          AND status NOT IN ('Filled', 'Cancelled') 
                          AND isactive != 5
                    ", [$reqId]);

					if ($openSeatsCount == 0) {
						// All seats are accounted for! Auto-close the master requisition.
						$db->update(
							'main_requisition',
							[
								'status' => 'Closed',
								'modifiedby' => $this->_loginUserId,
								'modifieddate' => gmdate("Y-m-d H:i:s")
							],
							['id = ?' => $reqId]
						);
					}
				}

				$db->commit();
				$this->_helper->json(['result' => 'saved', 'message' => 'Pipeline status updated successfully.']);
			} catch (\Throwable $e) {
				$db->rollBack();
				error_log("Status Transition Error: " . $e->getMessage());
				$this->_helper->json(['result' => 'exception', 'message' => $e->getMessage()]);
			}
		} else {
			$this->_helper->json(['result' => 'error', 'message' => 'Please provide a valid status and reason.']);
		}
	}
}
