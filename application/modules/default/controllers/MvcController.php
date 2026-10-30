<?php

class Default_MvcController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }

    public function createAction()
    {

        $mvc_form = new Default_Form_Mvc();
        $mvc_model = new Default_Model_Mvc();
        $mvc_form->setAttrib('action', BASE_URL . 'mvc/create');
        $path = __DIR__;
        $path = substr($path, 0, strpos($path, 'default\\'));

        $modules = array_filter(glob($path . '*'), 'is_dir');
        if (!empty($modules)) {
            $mvc_form->module_name->addMultiOption('empty', 'Select...');
            foreach ($modules as $module) {
                $module = str_replace($path, '', $module);
                $mvc_form->module_name->addMultiOption($module, $module);
            }
        } else {
            $mvc_form->module_name->addMultiOption('empty', 'Select...');
            $msgarray['module_name'] = 'No Module Found!';
        }

        $tables = $mvc_model->getAllTables();
        if (!empty($tables)) {
            $mvc_form->table_name->addMultiOption('empty', 'Select...');
            foreach ($tables as $table) {
                $mvc_form->table_name->addMultiOption($table['TABLE_NAME'], $table['TABLE_NAME']);
            }
        } else {
            $msgarray['table_name'] = "Tables are not configured yet!";
        }
        if ($this->getRequest()->getPost()) {


            if ($mvc_form->isValid($this->_request->getPost())) {
                $module_name = $this->getRequest()->getParam('module_name');
                $controller_name = $this->getRequest()->getParam('controller_name');

                // $abs_path = str_replace('controllers', '', __DIR__);
                $abs_path = $path . $module_name . '\\';

                $controller_path = $abs_path . 'controllers\\';
                $form_path = $abs_path . 'forms\\';
                $model_path = $abs_path . 'models\\';
                $view_path = $abs_path . 'views\scripts\\';

                if (!file_exists($controller_path)) {
                    mkdir($controller_path, 0777, true);
                }
                if (!file_exists($form_path)) {
                    mkdir($form_path, 0777, true);
                }
                if (!file_exists($model_path)) {
                    mkdir($model_path, 0777, true);
                }
                if (!file_exists($view_path)) {
                    mkdir($view_path, 0777, true);
                }
                $temp_path = $abs_path . 'controllers\\' . $controller_name . 'Controller.php';

                if (is_file($temp_path)) {
                    $msgarray['controller_name'] = "Controller Exists";
                    $this->view->msgarray = $msgarray;
                } else {

                    $controller_file = fopen($controller_path . $controller_name . 'Controller.php', 'a') or $msgarray['controller_name'] .= "Can not open or create controller :(\n";
                    $model_file = fopen($model_path . $controller_name . '.php', 'a') or $msgarray['controller_name'] .= "Can not open or create model \n";
                    $form_file = fopen($form_path . $controller_name . '.php', 'a') or $msgarray['controller_name'] .= "Can not open or create from \n";
                    if (!file_exists($view_path . $controller_name)) {
                        mkdir($view_path . strtolower($controller_name));
                    }

                    $view_index_file = fopen($view_path . strtolower($controller_name) . '/index.phtml', 'a') or $msgarray['controller_name'] .= "Can not open or create index \n";
                    $view_add_file = fopen($view_path . strtolower($controller_name) . '/add.phtml', 'a') or $msgarray['controller_name'] .= "Can not open or create add \n";
                    $view_edit_file = fopen($view_path . strtolower($controller_name) . '/edit.phtml', 'a') or $msgarray['controller_name'] .= "Can not open or create edit \n";
                    $view_view_file = fopen($view_path . strtolower($controller_name) . '/view.phtml', 'a') or $msgarray['controller_name'] .= "Can not open or create view \n";

                    $abs_path = str_replace('default\\', '', $abs_path);
                    $mvc_initializer_path = 'services\mvc_initializer\\';

                    /** 
                     * Intializing Controller with default data START 
                     */
                    try{
                    $controller_initializer = fopen($abs_path . $mvc_initializer_path . 'controller.php', 'r') or $msgarray['controller_name'] = "Can not open  controller initializer\n";
                    }catch(\Throwable $th)
                    {
                       
                    }
                    $data_to_write = '';
                    
                    while (!feof($controller_initializer)) {
                    //  die();
                        $temp_data = '';
                        $temp_data = fgets($controller_initializer);
                        $temp_data = str_replace('ClassName', 'Default_' . $controller_name . 'Controller', $temp_data);
                        $temp_data = str_replace('ControllerName', $controller_name, $temp_data);
                        $temp_data = str_replace('\'Form_Class\'', 'new Default_Form_' . $controller_name . '()', $temp_data);
                        $data_to_write .= $temp_data;
                    }
                    
                    fwrite($controller_file, $data_to_write);
                    fclose($controller_file);
                    fclose($controller_initializer);
                    /**
                     * Initializing Controller with default data END
                     */

                    /**
                     * Initializing Model with default data START
                     */
                    $model_initializer = fopen($abs_path . $mvc_initializer_path . 'model.php', 'r') or $msgarray['controller_name'] .= 'Can not open model initializer\n';
                    $data_to_write = '';

                    $tableName = $this->getRequest()->getParam('table_name');
                    $pk = '';
                    if ($tableName != 'empty') {
                        $pk = $mvc_model->getTablePK($tableName);
                        $pk = $pk[0]['COLUMN_NAME'];
                    }
                    while (!feof($model_initializer)) {
                        $temp_data = '';
                        $temp_data = fgets($model_initializer);
                        $temp_data = str_replace('ClassName', 'Default_Model_' . $controller_name, $temp_data);
                        $temp_data = str_replace('ControllerName', strtolower($controller_name), $temp_data);
                        if (!empty($tableName) && $tableName != 'empty') {
                            $temp_data = str_replace('TableName', $tableName, $temp_data);
                        }
                        if (!empty($pk)) {
                            $temp_data = str_replace('PrimaryKey', $pk, $temp_data);
                        }
                        $data_to_write .= $temp_data;
                    }
                    fwrite($model_file, $data_to_write);
                    fclose($model_file);
                    fclose($model_initializer);
                    /**
                     * Initializing Model with default data END
                     */

                    /**
                     * Initializing Form with default data START
                     */
                    $form_initializer = fopen($abs_path . $mvc_initializer_path . 'form.php', 'r') or $msgarray['controller_name'] .= 'Can not open from initializer\n';
                    $data_to_write = '';
                   /*  while (!feof($form_initializer)) {
                        $temp_data = '';
                        $temp_data = fgets($form_initializer);
                        $temp_data = str_replace('ClassName', 'Default_Form_' . $controller_name, $temp_data);
                        $temp_data = str_replace('ControllerName', strtolower($controller_name), $temp_data);
                        $data_to_write .= $temp_data;
                    }
                        fwrite($form_file, $data_to_write);
                    fclose($form_file);
                    fclose($form_initializer); */
                    /**
                     * Initializing Form with default data END
                     */

                    fclose($view_index_file);
                    fclose($view_add_file);
                    fclose($view_edit_file);
                    fclose($view_view_file);

                    if (!isset($msgarray['controller_name'])) {
                        $this->view->successmessage = "Controller created successfully";
                        
                        $this->_forward('index','index','formutil',['module_name'=>$this->_getParam('module_name'),'controller_name'=>$this->_getParam('controller_name')]);
                    }
                    $this->view->msgarray = $msgarray;
                }
            } else {
                $messages = $mvc_form->getMessages();
                foreach ($messages as $key => $val) {
                    foreach ($val as $key2 => $val2) {
                        $msgarray[$key] = $val2;
                    }
                }
                $this->view->msgarray = $msgarray;
            }
        }
        $this->view->form =  $mvc_form;
    }

    public function addmoduleAction()
    {

        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/popup/");
        $form = new Default_Form_Mvc();

        $form->setAttrib('action', BASE_URL . 'mvc/addmodule');

        if ($this->getRequest()->getPost()) {
            if (!empty($this->getRequest()->getParam('new_module_name'))) {

                $path = __DIR__;
                $path = substr($path, 0, strpos($path, 'default\\'));
                $module_name = $this->getRequest()->getParam('new_module_name');
                mkdir($path . $module_name);
                $this->view->successmessage = "Module Successfully Added!";
                $this->view->controllername = "mvc";
            } else {
                $msgarray['new_module_name'] = "Value is required and can't be empty";
                $this->view->msgarray = $msgarray;
            }
        }
        $this->view->form = $form;
    }
}
