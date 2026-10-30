<?php
/**
 * simple class the is created to maintain 
 * form codes
 * depends on util class 
 */

class addformelement 
{
    public function __construct()
    {
        require_once 'Util.php';
    }
    /**
     * creates and gives value to html element
     * @param string $elementtype element type
     * @param string $elementname 
     * @param array $value database queried data
     * @param string $key to access the value
     * @return Zend_Form_Element_Checkbox
     */
    public function addelement($elementtype,$elementname,$value,$key)
    {
        $checkbox=new Zend_Form_Element_Checkbox($elementname);
        
        if ($elementtype=='checkbox') {
            $checkbox->setValue(utlis::getcheckboxvalue($value[$key],'form'));
            return $checkbox;
        }
       
    
        
    }
    public function changeformelement($type)
    {
        if ($type=='Button') {
            return 'new Zend_Form_Element_Button';
        }
        elseif ($type=='Captcha') {
            return 'new Zend_Form_Element_Captcha';
        }
        elseif ($type=='CheckBox') {
            return 'new Zend_Form_Element_Checkbox';
        }
        elseif ($type=='DatePicker') {
            return 'new Zend_Form_Element_Text'; 
        }
        elseif ($type=='File') {
            return 'new Zend_Form_Element_File';
        }
        elseif ($type=='Hash') {
            return 'new Zend_Form_Element_Hash';
        }
        elseif ($type=='Hidden') {
            return 'new Zend_Form_Element_Hidden';
        }
        elseif ($type=='Image') {
            return 'new Zend_Form_Element_Image';
        }
        elseif ($type=='Select') {
            return 'new Zend_Form_Element_Select';
        }
        elseif ($type=='Submit') {
            return 'new Zend_Form_Element_Submit';
        }
        elseif ($type=='Text') {
            return 'new Zend_Form_Element_Text';
        }
        elseif ($type=='TextArea') {
            return 'new Zend_Form_Element_Textarea';
        }
    }
}
