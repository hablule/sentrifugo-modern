<?php

class Formutil_Controller extends Zend_Controller_Action
{
    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
        require_once 'application/modules/default/library/utilClasses/addformcomponent.php';
    }

    public function indexAction()
    {


        $form = new Formutil_Form_Addform();
       

        $modulename = $this->_request->getParam('module_name');
        $formname = $this->_request->getParam('controller_name');

        $form->setAction(BASE_URL . 'formutil/');
        $form->noofele->addValidator('NotEmpty', false, ['messages' => 'must add not of elements']);
        $form->noofele->setRequired(true);
        if (!isset($_SESSION['module_name'])) {
            $_SESSION['module_name'] = $modulename;
            $_SESSION['formname'] = $formname;
        }

        Zend_Debug::dump($_SESSION['formname']);
        if ($this->getRequest()->isPost()) {
            $form->modules->setValue($modulename);

            $form->id->setValue($formname);
            $form->formname->setValue($formname);
            if ($form->isValid($this->getRequest()->getPost())) {

                $modulename = $_SESSION['module_name'];
                $formname = $_SESSION['formname'];
                unset($_SESSION['module_name']);
                unset($_SESSION['formname']);

                $this->_forward('add', 'formutil', 'formutil', ['noofele' => $this->_request->getParam('noofele'), 'module_name' => $modulename, 'controller_name' => $formname]);
            }
        }
        $this->view->form = $form;
    }

    public function addAction()
    {


        $form = new Formutil_Form_Addform();
        $formmodel = new Formutil_Model_addform();
        $data = $formmodel->modules();

        /*  foreach ($data as $key => $value) {
            $form->modules->addMultiOptions(array($value['modulename']=>$value['modulename']));
        } */
        $form->setMethod('get');
        $modulename = $this->_request->getParam('module_name');
        $formname = $this->_request->getParam('controller_name');
        $form->modules->setValue($modulename);
        $form->formname->setValue($formname);
        $elements = $form->moreelements($this->_request->getParam('noofele'), $form);
        $form->setAction(BASE_URL . 'formutil/index/add');
        $form->id->setValue($this->_request->getParam('noofele'));
        if ($this->_request->isGet()) {
            $formname = $this->_request->getParam('formname');
            $modulename = $this->_request->getParam('modules');
            $noofele = $this->_request->getParam('id');
            $addformutil = new addformelement();
            $this->headerfile(MODULE_PATH . '/' . $modulename . '/', $formname, $modulename);
            $allelements = '';
            Zend_Debug::dump($this->_request->getParams());
            for ($i = 0; $i < $noofele; $i++) {
                
                $elementname = $this->_request->getParam('elename' . $i);
                $elementtype = $this->_request->getParam('elementtype' . $i);
                $type = $addformutil->changeformelement($elementtype);
                $code = '$' . $elementtype . $i . ' = ' . $type . '("' . $elementname . '");' . "\n";
                $allelements .= '$' . $elementtype . $i . ',';
                try {
                    $this->writefile(MODULE_PATH . '/' . $modulename . '/', $formname, $code);
                } catch (\Throwable $th) {
                    Zend_Debug::dump($th->getMessage());
                }
            }
            $allelements = substr($allelements, 0, strlen($allelements) - 1);
            $this->writefile(MODULE_PATH . '/' . $modulename . '/', $formname, '$this->addElements(array($id,' . $allelements . '));' . "\n");
            $this->writefile(MODULE_PATH . '/' . $modulename . '/', $formname, "} \n } \n ?>");
            $this->_helper->getHelper("FlashMessenger")->addMessage(["success"=>"MVC created successfully."]);
            $this->_redirect('mvc/create');

        }


        $this->view->noofele = $this->_request->getParam('noofele');
        $this->view->form = $form;
        $this->view->newelement = $elements;
    }

    public function headerfile($dir, $formname, $modulename)
    {
        $file = fopen($dir . 'forms/' . $formname . '.php', 'a+') or die('error');
        fwrite($file, "<?php \n \n \n");
        fwrite($file, 'class ' . ucfirst($modulename) . '_Form_' . $formname . " extends Zend_Form{ \n \n");
        fwrite($file, "public function init(){ \n \n");
        fwrite($file, '$this->setMethod("post");' . "\n" .
            '$this->setAttrib("id","formid");' . "\n" .
            '$this->setAttrib("name","formindex");' . "\n" .
            '$id = new Zend_Form_Element_Hidden("id");' . "\n");
    }

    public function writefile($dir, $formname, $data)
    {

        $file = fopen($dir . 'forms/' . $formname . '.php', 'a+');
        fwrite($file, $data);
    }
}
