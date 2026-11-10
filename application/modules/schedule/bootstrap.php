<?php




class Schedule_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected function _initAppAutoload()
    {
    }

    public function _initRoute()
    {

        $router = Zend_Controller_Front::getInstance()->getRouter();

        /*  $route = new Zend_Controller_Router_Route('timetable', array(
            'module' => 'schedule',
            'controller' => 'timetable',
            'action' => 'index'
        )); */

        /* $timetableaddroute = new Zend_Controller_Router_Route('timetable/add', array(
            'module' => 'schedule',
            'controller' => 'timetable',
            'action' => 'addtime'
        )); */



        /* $timetableeditroute = new Zend_Controller_Router_Route('timetable/edit/id/:id', array(
            'module' => 'schedule',
            'controller' => 'timetable',
            'action' => 'edit'
        )); */
        /* $timetableviewroute = new Zend_Controller_Router_Route('timetable/view/id/:id', array(
            'module' => 'schedule',
            'controller' => 'timetable',
            'action' => 'view'
        )); */
        $timetableviewallroute = new Zend_Controller_Router_Route('timetable/viewall', [
            'module' => 'schedule',
            'controller' => 'timetable',
            'action' => 'viewall'
        ]);
        $timetabledeleteroute = new Zend_Controller_Router_Route('timetable/delete', [
            'module' => 'schedule',
            'controller' => 'timetable',
            'action' => 'delete'
        ]);
        $scheduleroute = new Zend_Controller_Router_Route('timeschedule', [
            'module' => 'schedule',
            'controller' => 'timeschedule',
            'action' => 'index'
        ]);
        $scheduleaddroute = new Zend_Controller_Router_Route('timeschedule/add', [
            'module' => 'schedule',
            'controller' => 'timeschedule',
            'action' => 'add'
        ]);
        $scheduleviewroute = new Zend_Controller_Router_Route('timeschedule/view/id/:id', [
            'module' => 'schedule',
            'controller' => 'timeschedule',
            'action' => 'view'
        ]);
        $scheduleeditroute = new Zend_Controller_Router_Route('timeschedule/edit/id/:id', [
            'module' => 'schedule',
            'controller' => 'timeschedule',
            'action' => 'edit'
        ]);
        $scheduledeleteroute = new Zend_Controller_Router_Route('timeschedule/delete', [
            'module' => 'schedule',
            'controller' => 'timeschedule',
            'action' => 'delete'
        ]);

        $cronjobroute = new Zend_Controller_Router_Route('attendancecronjob', [
            'module' => 'schedule',
            'controller' => 'cronjob',
            'action' => 'index'
        ]);
        $schedulecyclepopupid = new Zend_Controller_Router_Route('schedulecycle/addpopup/id/:id', [
            'module' => 'schedule',
            'controller' => 'schedulecycle',
            'action' => 'addpopup'
        ]);
        $schedulecyclepopup = new Zend_Controller_Router_Route('schedulecycle/addpopup', [
            'module' => 'schedule',
            'controller' => 'schedulecycle',
            'action' => 'addpopup'
        ]);
        $overtimecategoryindex = new Zend_Controller_Router_Route('cycleshift/addpopup', [
            'module' => 'schedule',
            'controller' => 'cycleshift',
            'action' => 'addpopup'
        ]);
        $cycleshiftgetdata = new Zend_Controller_Router_Route('schedulecycle/getcycledata', [
            'module' => 'schedule',
            'controller' => 'schedulecycle',
            'action' => 'getcycledata'
        ]);

        $checkschedule = new Zend_Controller_Router_Route('schedulecycle/dayofexists', [
            'module' => 'schedule',
            'controller' => 'schedulecycle',
            'action' => 'dayofexists'
        ]);

        $cycleshiftPopup = new Zend_Controller_Router_Route('cycleshift/editpopup', [
            'module' => 'schedule',
            'controller' => 'cycleshift',
            'action' => 'editpopup'
        ]);
        $cyclepopup = new Zend_Controller_Router_Route('schedulecycle/editpopup', [
            'module' => 'schedule',
            'controller' => 'schedulecycle',
            'action' => 'editpopup'
        ]);
        $overtimecategoryedit = new Zend_Controller_Router_Route('overtimecategory/edit/id/:id', [
            'module' => 'schedule',
            'controller' => 'overtimecategory',
            'action' => 'edit'
        ]);
        $overtimecategorydelete = new Zend_Controller_Router_Route('overtimecategory/delete', [
            'module' => 'schedule',
            'controller' => 'overtimecategory',
            'action' => 'delete'
        ]);
        $takeattendroute = new Zend_Controller_Router_Route('takeattend', [
            'module' => 'schedule',
            'controller' => 'cronjob',
            'action' => 'takeattend'
        ]);

        $logreportindexroute = new Zend_Controller_Router_Route('logreport', [
            'module' => 'schedule',
            'controller' => 'logreport',
            'action' => 'index'
        ]);

        $attendancerepindexroute = new Zend_Controller_Router_Route('attendancereport', [
            'module' => 'schedule',
            'controller' => 'attendancereport',
            'action' => 'index'
        ]);
        $attendancereportchecktimeroute = new Zend_Controller_Router_Route('attendancereport/printreport', [
            'module' => 'schedule',
            'controller' => 'attendancereport',
            'action' => 'printreport'
        ]);
        $attendancereportprintroute = new Zend_Controller_Router_Route('attendancereport/checktime', [
            'module' => 'schedule',
            'controller' => 'attendancereport',
            'action' => 'checktime'
        ]);

        $overtimeindexroute = new Zend_Controller_Router_Route('overtime', [
            'module' => 'schedule',
            'controller' => 'overtime',
            'action' => 'index'
        ]);
        $overtimeaddroute = new Zend_Controller_Router_Route('overtime/add', [
            'module' => 'schedule',
            'controller' => 'overtime',
            'action' => 'add'
        ]);

        $overtimeeditroute = new Zend_Controller_Router_Route('overtime/edit/id/:id', [
            'module' => 'schedule',
            'controller' => 'overtime',
            'action' => 'edit'
        ]);
        $overtimeviewroute = new Zend_Controller_Router_Route('overtime/view/id/:id', [
            'module' => 'schedule',
            'controller' => 'overtime',
            'action' => 'view'
        ]);
        $overtimedeleteroute = new Zend_Controller_Router_Route('overtime/delete', [
            'module' => 'schedule',
            'controller' => 'overtime',
            'action' => 'delete'
        ]);

        $overtimecateroute = new Zend_Controller_Router_Route('overtime/addcategory', [
            'module' => 'schedule',
            'controller' => 'overtime',
            'action' => 'addcategory'
        ]);



        $overtimenewOTroute = new Zend_Controller_Router_Route('overtime/newot', [
            'module' => 'schedule',
            'controller' => 'overtime',
            'action' => 'addnewot'
        ]);
        $overtimechecktimeOTroute = new Zend_Controller_Router_Route('overtime/checktime', [
            'module' => 'schedule',
            'controller' => 'overtime',
            'action' => 'checktime'
        ]);
        //
        $myovertimesroute = new Zend_Controller_Router_Route('manageremployeeovertime', [
            'module' => 'schedule',
            'controller' => 'manageremployeeovertime',
            'action' => 'index',
        ]);
        $myovertimeseditroute = new Zend_Controller_Router_Route('manageremployeeovertime/edit/id/:id', [
            'module' => 'schedule',
            'controller' => 'manageremployeeovertime',
            'action' => 'edit',
        ]);
        $myovertimeseditpopuproute = new Zend_Controller_Router_Route('manageremployeeovertime/editpopup/id/:id', [
            'module' => 'schedule',
            'controller' => 'manageremployeeovertime',
            'action' => 'editpopup',
        ]);
        $myovertimesviewroute = new Zend_Controller_Router_Route('manageremployeeovertime/view/id/:id', [
            'module' => 'schedule',
            'controller' => 'manageremployeeovertime',
            'action' => 'view',
        ]);
        $myovertimesdeleteroute = new Zend_Controller_Router_Route('manageremployeeovertime/delete', [
            'module' => 'schedule',
            'controller' => 'manageremployeeovertime',
            'action' => 'delete',
        ]);
        $backuproute = new Zend_Controller_Router_Route('backup', [
            'module' => 'schedule',
            'controller' => 'backup',
            'action' => 'index',
        ]);

        //


        $timetablepopup = new Zend_Controller_Router_Route('timetable/addpopup', [
            'module' => 'schedule',
            'controller' => 'timetable',
            'action' => 'addpopup'
        ]);

        $deviceRoute = new Zend_Controller_Router_Route('device', [
            'module' => 'schedule',
            'controller' => 'device',
            'action' => 'index'
        ]);
        $scanRoute = new Zend_Controller_Router_Route('scandevice', [
            'module' => 'schedule',
            'controller' => 'device',
            'action' => 'scandevice'
        ]);

        $addDeviceRoute = new Zend_Controller_Router_Route('addconnection', [
            'module' => 'schedule',
            'controller' => 'device',
            'action' => 'addconnection'
        ]);

        $deleteDeviceRoute = new Zend_Controller_Router_Route('device/delete', [
            'module' => 'schedule',
            'controller' => 'device',
            'action' => 'delete'
        ]);
        $router->addRoute('delete', $deleteDeviceRoute);
        $router->addRoute('addDevice', $addDeviceRoute);
        $router->addRoute('scan', $scanRoute);
        $router->addRoute('device', $deviceRoute);
        $router->addRoute('cron', $cronjobroute);
        $router->addRoute('cycle', $overtimecategoryindex);
        $router->addRoute('schpopup', $schedulecyclepopup);
        $router->addRoute('schpopup', $schedulecyclepopupid);
        $router->addRoute('timetablepopup', $timetablepopup);
        $router->addRoute('getcycledata', $cycleshiftgetdata);
        $router->addRoute('schedulecyclepopup', $cyclepopup);
        $router->addRoute('cronjob', $takeattendroute);
        $router->addRoute('cycleshiftpopup', $cycleshiftPopup);
        $router->addRoute('viewall', $timetableviewallroute);
        $router->addRoute('checkday', $checkschedule);
        /*  $router->addRoute('add', $scheduleaddroute); */

        $router->addRoute('schedule', $scheduleroute);

        /* $router->addRoute('timetableeditroute', $timetableeditroute); */

        /* $router->addRoute('edit', $timetableaddroute); */

        /*  $router->addRoute('timetable', $route); */

        /*  $router->addRoute('timetableviewroute', $timetableviewroute); */

        $router->addRoute('timetabledeleteroute', $timetabledeleteroute);

        /* $router->addRoute('scheduleviewroute', $scheduleviewroute); */

        /*  $router->addRoute('scheduleeditroute', $scheduleeditroute); */

        $router->addRoute('scheduledeleteroute', $scheduledeleteroute);

        /* $router->addRoute('overtimecategoryedit', $overtimecategoryedit); */

        $router->addRoute('overtimecategorydelete', $overtimecategorydelete);

        $router->addRoute('overtimecateroute', $overtimecateroute);

        /*   $router->addRoute('logreportindex', $logreportindexroute); */
        $router->addRoute('myovertimesdeleteroute', $myovertimesdeleteroute);


        /* $router->addRoute('attendancerepindexroute', $attendancerepindexroute); */

        $router->addRoute('attendancerepprintroute', $attendancereportprintroute);

        $router->addRoute('attendancerepchecktimeroute', $attendancereportchecktimeroute);

        // $route->addRoute('overtimecategory',$otcategory);

        /* $router->addRoute('overtimeindexroute', $overtimeindexroute); */

        /*  $router->addRoute('overtimeaddroute', $overtimeaddroute); */

        $router->addRoute('overtimeediteroute', $overtimeeditroute);

        $router->addRoute('overtimeviewroute', $overtimeviewroute);

        $router->addRoute('overtimedeleteroute', $overtimedeleteroute);

        /*   $router->addRoute('overtimecategoryadd', $overtimecategory); */

        /* $router->addRoute('overtimecategoryindex', $overtimecategoryindex); */

        $router->addRoute('overtimechecktime', $overtimechecktimeOTroute);

        $router->addRoute('overtimenewOTroute', $overtimenewOTroute);

        /* $router->addRoute('myovertimesroute', $myovertimesroute); */

        $router->addRoute('myovertimesviewroute', $myovertimesviewroute);

        $router->addRoute('myovertimeseditroute', $myovertimeseditroute);

        $router->addRoute('myovertimeseditpopuproute', $myovertimeseditpopuproute);

        $router->addRoute('backuproute', $backuproute);
    }
}
