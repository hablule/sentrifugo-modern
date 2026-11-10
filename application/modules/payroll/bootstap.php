<?php

class Payroll_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected function _initAppAutoload()
    {
    }

    public function _initRoute()
    {

        $router = Zend_Controller_Front::getInstance()->getRouter();
        $payrollindex = new Zend_Controller_Router_Route('payroll/cronjob',[
            'module'=> 'payroll',
            'controller'=> 'cronjob',
            'action'=> 'index'
        ]);
        $payrollreport = new Zend_Controller_Router_Route('payroll/payrollreport',[
            'module'=>'payroll',
            'controller'=>'report',
            'action'=>'index'
        ]);
        $router->addRoute('payrollreport',$payrollreport);
        $router->addRoute('payrollindex',$payrollindex);
    }
}
?>
