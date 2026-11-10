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
 * Pay Frequency Controller
 *
 * Manages payroll cadence definitions (Fixed Intervals and Calendar Anchors).
 * Coordinates atomic 1:N anchor synchronization and interval validation.
 * Complies with SSOT v6.7.7 (PHP 7.4 strict, Raw Array JSON, Dependency Checks).
 *
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_PayfrequencyController extends Zend_Controller_Action
{
	/* --------------------------------------------------------------
     * Attributes (Visibility -> Alphabetical)
     * -------------------------------------------------------------- */

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
		$this->_model   = new Default_Model_Payfrequency();
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
			$call    = (string) $request->getParam('call', '');

			if ($call === 'ajaxcall') {
				$this->_helper->layout->disableLayout();
			}

			$refresh       = (string) $request->getParam('refresh', '');
			$dashboardcall = (string) $request->getParam('dashboradcall', '');
			$page          = (int) $request->getParam('page', 1);
			$searchData    = (string) $request->getParam('searchData', '');

			$sort    = (string) $request->getParam('sort', 'ASC');
			$by      = (string) $request->getParam('by', 'name');

			$perPage = ($dashboardcall === 'Yes')
				? (int) $request->getParam('per_page', DASHBOARD_PERPAGE)
				: (int) $request->getParam('per_page', PERPAGE);

			if ($refresh === 'refresh') {
				$sort = 'ASC';
				$by = 'name';
				$page = 1;
				$searchData = '';
			}

			$dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

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

			// Pass anchors for read-only view
			$this->view->anchors = $this->_model->getAnchorsByFrequencyId($id);
		}

		$this->view->controllername = 'payfrequency';
	}

	/* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

	public function addAction()
	{
		if (defined('PAYFREQUENCY_MENU_ID') && sapp_Global::_checkprivileges(PAYFREQUENCY_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'add') !== 'Yes') {
			$this->_redirect('error/privilege');
		}

		$this->view->form = $this->_prepareForm();
		$this->view->existingAnchors = json_encode([]);
		$this->render('save');
	}

	public function editAction()
	{
		if (defined('PAYFREQUENCY_MENU_ID') && sapp_Global::_checkprivileges(PAYFREQUENCY_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'edit') !== 'Yes') {
			$this->_redirect('error/privilege');
		}

		$id = (int) $this->getRequest()->getParam('id', 0);
		$form = $this->_prepareForm($id);

		if (!$form) {
			$this->view->nodata = "nodata";
		} else {
			$this->view->form = $form;
			$this->view->id   = $id;
			$this->view->existingAnchors = json_encode($this->_model->getAnchorsByFrequencyId($id));
		}

		$this->render('save');
	}

	public function saveAction()
	{
		$request = $this->getRequest();
		if (!$request->isPost()) {
			return $this->_redirect('payfrequency/index');
		}

		$id = (int) $request->getParam('id', 0);
		$form = $this->_prepareForm($id);

		if ($form->isValid($request->getPost())) {

			$db = Zend_Db_Table::getDefaultAdapter();
			$db->beginTransaction();

			try {
				$freqType = trim((string) $request->getParam('frequency_type', 'FIXED_INTERVAL'));

				$data = [
					'code'                  => trim((string) $request->getParam('code', '')),
					'name'                  => trim((string) $request->getParam('name', '')),
					'frequency_type'        => $freqType,
					'interval_count'        => null,
					'interval_unit'         => null,
					'weekly_anchor_day'     => null, // Default null
					'monthly_anchor_day'    => null, // Default null
					'global_reference_date' => null,
					'missing_date_policy'   => 'PREVIOUS_VALID_DAY',
					'description'           => trim((string) $request->getParam('description', '')),
					'isactive'              => (int) $request->getParam('isactive', 1)
				];

				$anchorsToSync = [];

				// Validation Branch 1: FIXED_INTERVAL
				if ($freqType === 'FIXED_INTERVAL') {
					$intCount = (int) $request->getParam('interval_count', 0);
					$intUnit  = trim((string) $request->getParam('interval_unit', ''));

					if ($intCount <= 0 || empty($intUnit)) {
						throw new Exception("Fixed Interval frequencies require a positive step count and an interval unit.");
					}

					$data['interval_count']        = $intCount;
					$data['interval_unit']         = $intUnit;
					$data['global_reference_date'] = $request->getParam('global_reference_date');

					// Map the specific qualifier based on unit:
					if ($intUnit === 'WEEK') {
						$weekDay = trim((string) $request->getParam('weekly_anchor_day', ''));
						if (empty($weekDay)) {
							throw new Exception("Please specify which day of the week the cycle begins.");
						}
						$data['weekly_anchor_day'] = $weekDay;
					} elseif ($intUnit === 'MONTH') {
						$monthDay = (int) $request->getParam('monthly_anchor_day', 0);
						if ($monthDay < 1 || $monthDay > 31) {
							throw new Exception("Please specify a valid start day of the month (1-31).");
						}
						$data['monthly_anchor_day'] = $monthDay;
					}
				}
				// Validation Branch 2: CALENDAR_BASED
				elseif ($freqType === 'CALENDAR_BASED') {
					$rawPayload = $request->getParam('calendar_anchors_payload');
					$anchorsArray = !empty($rawPayload) ? json_decode($rawPayload, true) : [];

					if (!is_array($anchorsArray) || count($anchorsArray) < 1) {
						throw new Exception("Calendar Based frequencies require at least one anchor day (1-31).");
					}

					$data['missing_date_policy'] = trim((string) $request->getParam('missing_date_policy', 'PREVIOUS_VALID_DAY'));
					$anchorsToSync = $anchorsArray;
				}

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

				// Synchronize child anchors atomically
				$this->_model->syncAnchors($savedId, $anchorsToSync);

				if (defined('PAYFREQUENCY_MENU_ID')) {
					sapp_Global::logManager(PAYFREQUENCY_MENU_ID, $actionFlag, $this->_loginUserId, $savedId);
				}

				$db->commit();

				$this->_helper->flashMessenger->addMessage(['success' => "Pay Frequency {$saveMode} successfully."]);
				$this->_helper->json(['result' => 'saved', 'nomessage' => 'yes']);
			} catch (\Throwable $e) {
				$db->rollBack();
				error_log("Pay Frequency Save Error: " . $e->getMessage());
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
			if (defined('PAYFREQUENCY_MENU_ID') && sapp_Global::_checkprivileges(PAYFREQUENCY_MENU_ID, $this->_loginuserGroup, $this->_loginuserRole, 'delete') !== 'Yes') {
				return $this->_helper->json(['result' => 'error', 'message' => 'Permission Denied.']);
			}

			try {
				if ($this->_model->checkInUse($id)) {
					throw new Exception("This Pay Frequency is currently assigned to employee compensation plans and cannot be deleted.");
				}

				$this->_model->deleteRecord($id);
				if (defined('PAYFREQUENCY_MENU_ID')) {
					sapp_Global::logManager(PAYFREQUENCY_MENU_ID, 3, $this->_loginUserId, $id);
				}

				return $this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'message' => 'Record deleted successfully.', 'msgtype' => 'success']);
			} catch (\Throwable $e) {
				error_log("Pay Frequency Delete Error: " . $e->getMessage());
				return $this->_helper->json(['result' => 'exception', 'message' => $e->getMessage(), 'msgtype' => 'error']);
			}
		}

		return $this->_helper->json(['result' => 'error', 'message' => 'Invalid request']);
	}

	public function makeactiveinactiveAction()
	{
		try {
			$id     = (int) $this->getRequest()->getParam('id', 0);
			$status = (int) $this->getRequest()->getParam('status', 0);

			$this->_model->toggleStatus($id, $status);
			if (defined('PAYFREQUENCY_MENU_ID')) {
				sapp_Global::logManager(PAYFREQUENCY_MENU_ID, 4, $this->_loginUserId, $id);
			}

			$this->_helper->json(['result' => 'saved', 'nomessage' => 'no', 'msgtype' => 'success', 'message' => 'Status updated successfully.']);
		} catch (\Throwable $e) {
			error_log("Pay Frequency Toggle Error: " . $e->getMessage());
			$this->_helper->json(['result' => 'exception', 'msgtype' => 'error', 'message' => 'System error occurred.']);
		}
	}

	/* =================================================================================
     * GROUP 5: AJAX, INTEGRITY CHECKS & INTERNAL HELPERS
     * ================================================================================= */

	public function addpopupAction()
	{
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

		$prev_cntrl = (string) $this->getRequest()->getParam('prev_cntrl', '');
		$form = $this->_prepareForm();

		$form->setAction(BASE_URL . 'payfrequency/addpopup');
		$form->setAttrib('id', 'popupformid');
		$form->getElement('submit')->setAttrib('onclick', null);

		$msgarray = [];

		if ($this->getRequest()->isPost()) {
			if ($form->isValid($this->getRequest()->getPost())) {
				$db = Zend_Db_Table::getDefaultAdapter();
				$db->beginTransaction();

				try {
					$data = [
						'code'           => trim((string) $this->getRequest()->getParam('code', '')),
						'name'           => trim((string) $this->getRequest()->getParam('name', '')),
						'frequency_type' => trim((string) $this->getRequest()->getParam('frequency_type', 'FIXED_INTERVAL')),
						'description'    => trim((string) $this->getRequest()->getParam('description', '')),
						'isactive'       => (int) $this->getRequest()->getParam('isactive', 1)
					];

					$newId = $this->_model->saveRecord($data);

					if (defined('PAYFREQUENCY_MENU_ID')) {
						sapp_Global::logManager(PAYFREQUENCY_MENU_ID, 1, $this->_loginUserId, $newId);
					}
					$db->commit();

					$activeRecords = $this->_model->getPairs(true);
					$optionsHtml = '';
					foreach ($activeRecords as $valId => $valName) {
						$optionsHtml .= sapp_Global::selectOptionBuilder($valId, $valName, $newId);
					}

					$this->view->optionsHtml   = $optionsHtml;
					$this->view->newInsertedId = $newId;
					$this->view->eventact      = 'added';
					$this->view->popup         = 'close';
				} catch (\Throwable $e) {
					$db->rollBack();
					$msgarray['error'] = 'Database Error: ' . $e->getMessage();
				}
			} else {
				foreach ($form->getMessages() as $field => $errors) {
					$msgarray[$field] = current($errors);
				}
			}
		}

		$this->view->msgarray = $msgarray;
		$this->view->form     = $form;
	}

	private function _prepareForm($id = null, $readonly = false)
	{
		$form = new Default_Form_Payfrequency();

		if ($id) {
			$data = $this->_model->getRecordById($id);
			if (!$data) return null;

			foreach ($data as $key => $val) {
				if ($val !== null) {
					$data[$key] = htmlentities(stripslashes((string)$val), ENT_QUOTES, "UTF-8");
				}
			}

			if (!empty($data['global_reference_date'])) {
				$data['global_reference_date'] = sapp_Global::change_date($data['global_reference_date'], 'view');
			}

			$form->populate($data);
			$form->getElement('submit')->setLabel('Update Frequency');
		}

		if ($readonly) {
			foreach ($form->getElements() as $element) {
				$element->setAttrib('disabled', 'disabled');
			}
			$form->removeElement('submit');
		}

		return $form;
	}
}
