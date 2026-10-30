<?php 

/**
 * @deprecated Use Default_Model_Country instead. Scheduled for removal in v4.0.
 */
class Default_Model_Countrylist extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_country';
    protected $_primary = 'id';


    public function getAllCountries()
    {
        $select = $this->select()
        ->setIntegrityCheck(false)
        ->from(array('n'=>'main_country'),array('n.id','n.country_name'))
        ->where('n.isactive = 1')
        ->order('n.country_name');
        return $this->fetchAll($select)->toArray();
    }
    


}

?>