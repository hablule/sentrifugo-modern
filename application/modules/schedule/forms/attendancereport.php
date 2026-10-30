<?php 

class Schedule_Form_AttendanceReport extends Zend_Form{
    public function init(){
        $this->setMethod('POST');
        $this->setAttrib('id','formid');
        $this->setAttrib('action',BASE_URL.'attendancereport/printreport');
        $this->setAttrib('name','frm_attendancereport');

        $id = new Zend_Form_Element_Hidden('id');
        $search_by = new Zend_Form_Element_Multiselect('search_by');
        $search_by->setLabel('Search by');
        $search_by->addMultiOptions(array('Day'=>'Day','Branch'=>'Branch','sbunit'=>'Businessunit','Division'=>'Division','Department'=>'Department','Section'=>'Section','Project'=>'Project',
                                            'Team'=>'Team'));
        $schedule = new Zend_Form_Element_Multiselect('schedule');
        $schedule->addMultiOptions(array('empty'=>'Select...'));
       
        $report_option = new Zend_Form_Element_Multiselect('report_option');
        $report_option->addMultiOptions(array('All'=>'All','Attendance'=>'Attendance','Absent'=>'Absent','Late'=>'Late','Leave'=>'Leave'));
        $report_option->setLabel("Report Option");
       
        $day= new Zend_Form_Element_Select('day');
        $day->addMultiOptions(array('empty'=>'Select...','Monday'=>'Monday','Tuesday'=>'Tuesday','Wednesday'=>'Wednesday','Thursday'=>'Thursday','Friday'=>'Friday','Saturday'=>'Saturday','Sunday'=>'Sunday'));
        $day->setLabel('Day');
        $day->addMultiOptions(array('empty'=>'Select...'));
        $dpt= new Zend_Form_Element_Select('dpt');
        $dpt->setLabel('Departement');
        $dpt->addMultiOptions(array('empty'=>'Select...'));
        $sbunit= new Zend_Form_Element_Select('sbunit');
        $sbunit->setLabel('Business Unit');
        $sbunit->addMultiOptions(array('empty'=>'Select...'));
        $division= new Zend_Form_Element_Select('division');
        $division->setLabel('Division');
        $division->addMultiOptions(array('empty'=>'Select...'));

        $section= new Zend_Form_Element_Select('section');
        $section->setLabel('Section');
        $section->addMultiOptions(array('empty'=>'Select...'));

        $project= new Zend_Form_Element_Select('project');
        $project->setLabel('Project');
        $project->addMultiOptions(array('empty'=>'Select...'));
        $team= new Zend_Form_Element_Select('team');
        $team->setLabel('Team');
        $team->addMultiOptions(array('empty'=>'Select...'));

        $branch = new Zend_Form_Element_Select('branch');
        $branch->setLabel('Branch');
        $query = new Zend_Form_Element_Text('query');
        $query->setLabel('Name');
        $submit = new Zend_Form_Element_Submit('submit_1');
        $submit->setLabel('Export to excel');
      //  $submit->setAttrib('img','devserver/Sentrifugo_3.2/public/media/images/exel.gif');
        $start_date = new Zend_Form_Element_Text('start_date');
       

        $start_date->setAttrib('readonly','readonly');
        $end_date = new Zend_Form_Element_Text('end_date');
        $end_date->setAttrib('readonly','readonly');
       


        $this->addElements(array($id,$search_by,$report_option,$query,$start_date,$end_date,$schedule,$dpt,$day,$branch,$submit,$sbunit,$division,$section,$project,$team));
        $this->setElementDecorators(array('ViewHelper'));

    }
}
?>