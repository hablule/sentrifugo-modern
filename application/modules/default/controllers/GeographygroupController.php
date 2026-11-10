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
 * Geography Group Controller
 * 
 * Manages the CRUD, Grid, and On-the-Fly Popup logic for Geographical Groups.
 * Strictly adheres to SSOT v6.1: PHP 7.4/8.0+ safe, transactional, 
 * strictly formatted JSON responses, and decoupled form logic.
 * 
 * @category   Application
 * @package    Default
 * @subpackage Controllers
 * @author     Hablul Soft Solution < /hablul.in>
 * @license    http://gnu.org GNU General Public License, version 3.0 (GPL-3.0)
 * @version    Sentrifugo v3.2
 */
class Default_GeographygroupController extends Zend_Controller_Action
{
	/** @var int Logged in user ID */
	protected $_loginUserId;

	/** @var int Logged in user Role ID */
	protected $_loginuserRole;

	/** @var int Logged in user Group ID */
	protected $_loginuserGroup;

	/** @var Default_Model_Geographygroup Instance of the Geography Group Model */
	protected $_model;

	/** @var array Application configuration options */
	protected $_options;

    /* =================================================================================
     * GROUP 1: INITIALIZATION & LIFECYCLE
     * ================================================================================= */

	/**
	 * Initialize Controller, Model, and Auth properties.
	 * 
	 * @return void
	 */
	public function init()
	{
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();
		$this->_model   = new Default_Model_Geographygroup();

		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$user = $auth->getStorage()->read();

			$this->_loginUserId    = $user->id;
			$this->_loginuserRole  = $user->emprole;
			$this->_loginuserGroup = $user->group_id;

			// SSOT V: Explicitly assign context to view for UI-level ACL checks
			$this->view->loginuserRole  = $this->_loginuserRole;
			$this->view->loginuserGroup = $this->_loginuserGroup;
		}

		require_once APPLICATION_PATH . '/modules/default/library/utilClasses/Util.php';
	}

	/**
	 * Pre-dispatch routines for Session validation and AJAX context switching.
	 * 
	 * @return void
	 */
	public function preDispatch()
	{
		$session = sapp_Global::_readSession();
		if (!isset($session)) {
			if ($this->getRequest()->isXmlHttpRequest()) {
				die(Zend_Json::encode(['login' => 'failed']));
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

	/**
	 * Index Action (Grid View)
	 * 
	 * @return void
	 */
	public function indexAction()
	{
		try {
			$request = $this->getRequest();
			$call    = $request->getParam('call');
			if ($call == 'ajaxcall') {
				$this->_helper->layout->disableLayout();
			}

			$refresh       = $request->getParam('refresh');
			$dashboardcall = $request->getParam('dashboradcall');
			$page          = $request->getParam('page', 1);

			// SSOT VIII: Strict string casting to prevent PHP 8 rtrim() Fatal TypeError
			$searchData    = (string)$request->getParam('searchData', '');

			// SSOT V: Default sort is ASC by the primary descriptive column
			$sort    = $request->getParam('sort', 'ASC');
			$by      = $request->getParam('by', 'name');
			$perPage = ($dashboardcall == 'Yes')
				? $request->getParam('per_page', DASHBOARD_PERPAGE)
				: $request->getParam('per_page', PERPAGE);

			if ($refresh == 'refresh') {
				$sort = 'ASC';
				$by   = 'name';
				$page = 1;
				$searchData = '';
			}

			$dataTmp = $this->_model->getGrid($sort, $by, $perPage, $page, rtrim($searchData, ','), $call, $dashboardcall);

			$this->view->dataArray = [$dataTmp];
			$this->view->call      = $call;
			$this->view->messages  = $this->_helper->flashMessenger->getMessages();
		} catch (\Throwable $e) { // SSOT VIII: Catch \Throwable
			error_log("Geography Group Index Error: " . $e->getMessage());
			if (APPLICATION_ENV == 'development') Zend_Debug::dump($e->getMessage());
		}
	}

	/**
	 * View Action (Read-Only Detail)
	 * 
	 * @return void
	 */
	public function viewAction()
	{
		$id = (int)$this->getRequest()->getParam('id');
		$this->view->form = $this->_prepareForm($id, true);
		$this->view->id = $id;
		$this->view->controllername = 'geographygroup';
	}

    /* =================================================================================
     * GROUP 3: MODIFICATION & PERSISTENCE
     * ================================================================================= */

	/**
	 * Add Action (Renders Save View)
	 * 
	 * @return void
	 */
	public function addAction()
	{
		$this->view->form = $this->_prepareForm();
		$this->render('save');
	}

	/**
	 * Edit Action (Renders Save View)
	 * 
	 * @return void
	 */
	public function editAction()
	{
		$id = (int)$this->getRequest()->getParam('id');
		$this->view->form = $this->_prepareForm($id, false);
		$this->render('save');
	}

	/**
	 * Save Action (AJAX POST Processor)
	 * 
	 * @return void Outputs strict JSON contract expected by Sentrifugo hrms.js
	 */
	public function saveAction()
	{
		$form = new Default_Form_Geographygroup();
		$request = $this->getRequest();

		if ($request->isPost()) {
			if ($form->isValid($request->getPost())) {
				$id = (int)$request->getParam('id');

				$db = Zend_Db_Table::getDefaultAdapter();
				$db->beginTransaction(); // SSOT V: Transactions Mandatory

				try {
					$data = [
						'name'        => trim($request->getParam('name')),
						'code'        => trim($request->getParam('code')),
						'description' => trim($request->getParam('description')),
						'isactive'    => (int)$request->getParam('isactive')
					];

					$actionFlag = ($id > 0) ? 2 : 1; // 2=Edit, 1=Add
					$savedId = $this->_model->saveRecord($data, $id);
					$recordId = ($id > 0) ? $id : $savedId;

					// SSOT I: Dynamic Menu ID logging (103 = Geo Groups)
					$menuId = defined('GEOGRAPHYGROUP') ? GEOGRAPHYGROUP : 103;
					sapp_Global::logManager($menuId, $actionFlag, $this->_loginUserId, $recordId);

					$db->commit();

					// Set Flash message for the NEXT page load
					$msg = ($actionFlag == 1) ? 'Added' : 'Updated';
					$this->_helper->flashMessenger->addMessage(['success' => "Geography Group $msg successfully."]);

					// SSOT V: Strict JSON Response for Global Redirect Helper
					$this->_helper->json([
						'result'    => 'saved',
						'nomessage' => 'yes'
					]);
				} catch (\Throwable $e) {
					$db->rollBack();
					error_log("Geography Group Save Error: " . $e->getMessage());
					// Legacy Exception Contract
					$this->_helper->json(['result' => 'exception', 'message' => 'System error occurred.']);
				}
			} else {
				// SSOT V: Strict JSON Response for Global Validation Helper
				$this->_helper->json($form->getMessages());
			}
		}
	}

	/**
	 * Phase 4 Feature: On-The-Fly Popup Addition
	 * Disables layout, handles standard POST (no AJAX), and returns 
	 * JavaScript options string to the parent window DOM upon success.
	 * 
	 * @return void
	 */
	public function addpopupAction()
	{
		// SSOT VII: Switch to popup layout shell
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");

		$form = new Default_Form_Geographygroup();
		$form->setAction(BASE_URL . 'geographygroup/addpopup');

		// Strip the global AJAX submit handler; popup requires standard POST to trigger JS callback
		$form->getElement('submit')->setAttrib('onclick', null);

		$request = $this->getRequest();
		$msgarray = [];

		// Initialize popup state to prevent view rendering notices
		$this->view->popup = '';

		if ($request->isPost()) {
			if ($form->isValid($request->getPost())) {
				$db = Zend_Db_Table::getDefaultAdapter();
				$db->beginTransaction();

				try {
					$data = [
						'name'        => trim($request->getParam('name')),
						'code'        => trim($request->getParam('code')),
						'description' => trim($request->getParam('description')),
						'isactive'    => (int)$request->getParam('isactive', 1)
					];

					$newId = $this->_model->saveRecord($data);

					$menuId = defined('GEOGRAPHYGROUP') ? GEOGRAPHYGROUP : 103;
					sapp_Global::logManager($menuId, 1, $this->_loginUserId, $newId);

					$db->commit();

					// Generate new dropdown options string for the parent window
					$activeRecords = $this->_model->getPairs(true);
					$optionsHtml = '';
					foreach ($activeRecords as $valId => $valName) {
						$optionsHtml .= sapp_Global::selectOptionBuilder($valId, $valName);
					}

					$this->view->optionsHtml = $optionsHtml;
					$this->view->eventact    = 'added';
					$this->view->newInsertedId = $newId;
					$this->view->popup       = 'close'; // Triggers View success state

				} catch (\Throwable $dbError) {
					$db->rollBack();
					$msgarray['error'] = 'Database Error: ' . $dbError->getMessage();
				}
			} else {
				foreach ($form->getMessages() as $field => $errors) {
					$msgarray[$field] = current($errors);
				}
			}
		}

		$this->view->controllername = 'geographygroup';
		$this->view->msgarray       = $msgarray;
		$this->view->form           = $form;
	}

    /* =================================================================================
     * GROUP 4: REMOVAL & STATUS
     * ================================================================================= */

	/**
	 * Delete Action (AJAX POST Processor)
	 * 
	 * @return void Outputs strict JSON contract
	 */
	public function deleteAction()
	{
		$id = (int)$this->getRequest()->getParam('objid');
		if ($id) {
			try {
				// Dependency check occurs inside the Model per SSOT IV
				$this->_model->deleteRecord($id);

				$menuId = defined('GEOGRAPHYGROUP') ? GEOGRAPHYGROUP : 103;
				sapp_Global::logManager($menuId, 3, $this->_loginUserId, $id);

				$this->_helper->json(['msgtype' => 'success', 'message' => 'Geography Group deleted successfully.']);
			} catch (\Throwable $e) {
				// Catch the model exception (e.g., Record is in use)
				$this->_helper->json(['msgtype' => 'error', 'message' => $e->getMessage()]);
			}
		} else {
			$this->_helper->json(['msgtype' => 'error', 'message' => 'Invalid Request.']);
		}
	}

	/**
	 * Make Active/Inactive Action (AJAX POST Processor)
	 * 
	 * @return void Outputs strict JSON contract
	 */
	public function makeactiveinactiveAction()
	{
		try {
			$id = (int)$this->getRequest()->getParam('id');
			$status = (int)$this->getRequest()->getParam('status');

			$this->_model->toggleStatus($id, $status);

			$menuId = defined('GEOGRAPHYGROUP') ? GEOGRAPHYGROUP : 103;
			sapp_Global::logManager($menuId, 4, $this->_loginUserId, $id);

			$this->_helper->json(['result' => 'yes']);
		} catch (\Throwable $e) {
			$this->_helper->json(['result' => 'no']);
		}
	}

    /* =================================================================================
     * GROUP 5: AJAX & INTERNAL HELPERS
     * ================================================================================= */

	/**
	 * Form Preparation Helper
	 * 
	 * Instantiates the form, populates it if editing, and locks fields if viewing.
	 * SSOT V: Does NOT manipulate the onclick attribute.
	 * 
	 * @param int|null $id       Optional ID for populating data.
	 * @param bool     $readonly If true, disables all elements.
	 * @return Default_Form_Geographygroup
	 */
	private function _prepareForm($id = null, $readonly = false)
	{
		$form = new Default_Form_Geographygroup();

		if ($id) {
			$data = $this->_model->getRecordById($id);
			if ($data) {
				$form->populate($data);
				$form->getElement('submit')->setLabel('Update');
			}
		}

		if ($readonly) {
			foreach ($form->getElements() as $el) {
				$el->setAttrib('disabled', 'disabled');
			}
			$form->removeElement('submit');
		}

		return $form;
	}
}
