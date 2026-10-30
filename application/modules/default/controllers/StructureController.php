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

class Default_StructureController extends Zend_Controller_Action
{

	private $options;
	public function preDispatch() {}

	public function init()
	{
		$this->_options = $this->getInvokeArg('bootstrap')->getOptions();
	}

	public function indexAction()
	{
		try {
			//code...
			$structureModel = new Default_Model_Structure();


			$orgData = $structureModel->getOrgData();

			$sBBusinessUnitdata = $structureModel->getbusinessdata();
			$divisionData = $structureModel->getdivisiondata();
			//die(json_encode($divisionData));
			$deptData = $structureModel->getDeptData();
			$sectionData = $structureModel->getsectiondata();
			$projectData = $structureModel->getprojectdata();

			$nobu = 'no';
			$nodiv = 'no';
			$nodep = 'no';
			$nosection = 'no';
			$noproj = 'no';

			foreach ($sBBusinessUnitdata as $rec1) {
				if($rec1['sbunit_id'] !== null)
					$nobu = 'exists';
			}

			foreach ($divisionData as $rec2) {
				if($rec2['division_id'] !== null)
					$nodiv = 'exists';
			}

			foreach ($deptData as $rec3) {
				if ($rec3['department_id'] !== null)
					$nodep = 'exists';
			}

			// die(json_encode($sectionData));
			foreach ($sectionData as $rec4) {
				if ($rec4['section_id'] !== null)
					$nosection = 'exists';
			}
			//die(json_encode($projectData));

			foreach ($projectData as $rec5) {
				if ($rec5['project_id'] !== null)
					$noproj = 'exists';
			}

			
			

			$this->view->orgData = $orgData;
			$this->view->businessdata = $sBBusinessUnitdata;
			$this->view->divisionData = $divisionData;
			$this->view->deptData = $deptData;
			$this->view->sectionData = $sectionData;
			$this->view->projectData = $projectData;

			$this->view->nobu = $nobu;
			$this->view->nodiv = $nodiv;
			$this->view->nodep = $nodep;
			$this->view->nosection = $nosection;
			$this->view->noproj = $noproj;

			$this->view->msg = 'This is organization structure';
		} catch (\Throwable $th) {
			Zend_Debug::dump($th->getMessage());
		}
	}
}
