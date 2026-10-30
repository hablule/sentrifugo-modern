<?php

class TreeNode{
    public $data;
    public $parent;
    public $children;
    public $child;
    public function __construct($data)
    {
        $this->data = $data;
        $this->parent = null;
        $this->children = [];
    }
    public function addchild($node)
    {
        $this->child = $node;
        $this->child->parent = $this;
        $this->children[] = $this->child;
    }

    public function printnode()
    {   
        Zend_Debug::dump($this->data);
        foreach ($this->children as $key=>$value) {
            Zend_Debug::dump($value->printnode());
            Zend_Debug::dump($key);
        }
    }
    public function treechildren()
    {
        return $this->data;
    }
    public function findpath($tablename)
    {
        $nod=null;
       
        if ($this->data['referenced_table'] == $tablename) {
           
           
             
            $node = $this->traversetree();
            Zend_Debug::dump($node,'lp');
            return $node;
        }
        else{
        foreach ($this->children as  $value) {
            
             $node=$value->findpath($tablename);
             
        }
    }
    return $node;

        
    }
    public function traversetree()
    {
        $nodepath = [];
        $node = $this;
        
        while($node!==null)
        {
            
            $nodepath[]=$node->data;
            $node = $node->parent;
            
        }
        
        return $nodepath;
    }

}


?>