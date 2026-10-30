<?php

class Default_UserdefinedclassController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_options = $this->getInvokeArg('bootstrap')->getOptions();
    }
    private static function returnType($class, $methods, $returnType)
    {

        $filteredMethods = [];
        foreach ($methods as $method) {
            $filterMatched = true;
            $reflection = new ReflectionMethod($class, $method);
            $tempReturnType = strval($reflection->getReturnType());
            if (is_array($returnType)) {
                $filterMatched = false;
                foreach ($returnType as $type) {
                    if (strtoupper($tempReturnType) == strtoupper($type)) {
                        $filterMatched = true;
                    }
                }
            } else {
                if (strtoupper($tempReturnType) != strtoupper($returnType)) {
                    $filterMatched = false;
                }
            }
            if ($filterMatched) {
                array_push($filteredMethods, $method);
            }
        }
        return $filteredMethods;
    }
    private static function argNo($class, $methods, $max, $min = 0)
    {
        $filteredMethods = [];
        foreach ($methods as $method) {
            $reflection = new ReflectionMethod($class, $method);
            $paraNo = $reflection->getNumberOfParameters();
            if ($min <= $paraNo && $paraNo <= $max) {
                array_push($filteredMethods, $method);
            }
        }
        return $filteredMethods;
    }
    private static function argType($class, $methods, $argType)
    {

        $filteredMethods = [];
        foreach ($methods as $method) {
            $filterMatched = true;
            $reflection = new ReflectionMethod($class, $method);
            $parameters = $reflection->getParameters();
            foreach ($parameters as $parameter) {
                $parameter = substr($parameter, strpos($parameter, "[") + 1);
                $parameter = str_replace("]", "", $parameter);
                $parameter = str_replace(' ', '', $parameter);
                $parameter = str_replace('<required>', '', $parameter);
                $parameter = strval($parameter);
                $parameter = explode('$', $parameter);


                if (is_array($argType)) {
                    $filterMatched = false;
                    foreach ($argType as $type) {
                        if (strtoupper($parameter[0]) == strtoupper($type)) {
                            $filterMatched = true;
                        }
                    }
                } else {
                    if (strtoupper($parameter[0]) != strtoupper($argType)) {
                        $filterMatched = false;
                    }
                }
            }
            if ($filterMatched) {
                array_push($filteredMethods, $method);
            }
        }
        return $filteredMethods;
    }
    /**  
     * @method getAllClasses($filter=null, $class_path=null)
     * 
     * @param array $filter 
     *     [Optional]
     * @example =>  
     *      $filter = ['argNo'=>3,'returnType'=>'string', 'argType'=>'float'] 
     * @example =>
     *      $filter = ['argNo'=>3,'returnType'=>['string','boolean'], 'argType'=>['float','int']] 
     * @param array|string $class_path 
     *      [Optional] 
     * @example   =>
     *      $class_path = 'the/path/your/desired/file/found/specificfile.php'
     * @example   =>
     *      $class_path = 'the/path/your/desired/file/found/*.php'
     * @example   =>
     *       $class_path = ['the/path/your/desired/file/found/*.php','the/path/your/desired/file/found/specificfile.php']
     **/
    public static function getAllClasses($filter = null, $class_path = null)
    {
        
        $requiredClassPaths = [];
        $matchedClassNames = [];
        $unmatchedClassNames = [];
        array_push($requiredClassPaths, 'Classes/UserDefined/Classes/*.php');
        if ($class_path != null && $class_path != '') {
            if (is_array($class_path)) {
                foreach ($class_path as $path) {
                    array_push($requiredClassPaths, $path);
                }
            } else {
                array_push($requiredClassPaths, $class_path);
            }
        }
        foreach ($requiredClassPaths as $classPath) {
            foreach (glob($classPath) as $file) {
                require_once '' . $file;
                $class =  basename($file, '.php');
                if (class_exists($class)) {
                    $matchedClassNames[$class] = [];

                    $classPath = str_replace(substr($classPath, strrpos($classPath, '/') + 1), '', $classPath);
                    $matchedClassNames[$class]['path'] = $classPath;
                    $matchedClassNames[$class]['methods'];
                    $class_methods = get_class_methods($class);
                    $tepMethods = [];

                    if ($filter != null && $filter != '') {

                        if (isset($filter['returnType'])) {
                            $class_methods = Default_UserdefinedclassController::returnType($class, $class_methods, $filter['returnType']);
                        }

                        if (isset($filter['argNo'])) {
                            if (is_array($filter['argNo'])) {
                                $class_methods = Default_UserdefinedclassController::argNo($class, $class_methods, $filter['argNo']['max'], $filter['argNo']['min']);
                            } else {
                                $class_methods = Default_UserdefinedclassController::argNo($class, $class_methods, $filter['argNo']);
                            }
                        }
                        if (isset($filter['argType'])) {
                            $class_methods = Default_UserdefinedclassController::argType($class, $class_methods, $filter['argType']);
                        }
                    }
                    foreach ($class_methods as $method) {
                        $reflection = new ReflectionMethod($class, $method);
                        if ($reflection->isPublic()) {
                            $matchedClassNames[$class]['methods'][$method] = [];
                            $no_parametrs = $reflection->getNumberOfParameters();
                            $parameters = $reflection->getParameters();
                            $matchedClassNames[$class]['methods'][$method]["name"] = $class . "." . $method;
                            $matchedClassNames[$class]['methods'][$method]["no_args"] = $no_parametrs;
                            $matchedClassNames[$class]['methods'][$method]["arguments"] = [];
                            foreach ($parameters as $parameter) {
                                $parameter = substr($parameter, strpos($parameter, "[") + 1);
                                $parameter = str_replace("]", "", $parameter);
                                $parameter = trim($parameter);
                                $parameter = explode('$', $parameter);
                                $temp_arg_array = [];
                                $temp_arg_array['name'] = str_replace(" ", "", $parameter['1']);
                                $temp_arg_array['type'] = str_replace(" ", "", $parameter['0']);
                                array_push($matchedClassNames[$class]['methods'][$method]["arguments"], $temp_arg_array);
                            }
                        }
                    }
                } else {
                    array_push($unmatchedClassNames, $class);
                }
            }
        }
        // die(json_encode($matchedClassNames));
        return $matchedClassNames;
    }
    // Below function is currently not in use  
    public function indexAction()
    {
        $form = new Default_Form_userdefinedclass();
        $classes = $this->getAllClasses();

        $form->classfun->addMultiOption('empty', "Select...");
        foreach ($classes as $class) {
            foreach ($class as $method) {
                $form->classfun->addMultiOption($method["name"], $method["name"]);
            }
        }

        $this->view->form = $form;
        $this->view->classes = $classes;
    }
    //Function end
    public function getTableReferencePath($tableName, $endTableName)
    {
        $model = new Default_Model_Userdefinedclass();
        $emptyPath = [];
        $pathFound = false;
        $referencedTables = $model->getReferencedTables($tableName);
        Zend_Debug::dump($referencedTables, 'At function start=> ');
        Zend_Debug::dump($referencedTables, "All refernce columns of table " . $tableName);
        echo "\n";
        foreach ($referencedTables as $referencedTable) {
            if ($referencedTable['referenced_table'] != null || $referencedTable['referenced_table'] != '') {
                if ($referencedTable['referenced_table'] != $endTableName) {
                    Zend_Debug::dump($referencedTable);

                    $tempPath = $this->getTableReferencePath($referencedTable['referenced_table'], $endTableName);
                    if (count($tempPath) > 0) {
                        $referencedTable['parentTable'] = [];
                        array_push($referencedTable['parentTable'], $tempPath);
                        Zend_Debug::dump($tempPath, "Temp Path ");
                        return $referencedTable;
                    }
                } else {
                    Zend_Debug::dump($referencedTable, "Path Found ");
                    return $referencedTable;
                }
            }
        }
        return $emptyPath;
    }
    public function buildQuery($queryElement, $referencePath)
    {

        if (count($referencePath) > 0) {
            $alias_main_tbl = $referencePath['table'];
            $alias_ref_tbl = $referencePath['referenced_table'];
            $tempQuery = "INNER JOIN " . $referencePath['referenced_table'] . " " . $alias_ref_tbl . " ON " . $alias_ref_tbl . "." . $referencePath['referenced_column'] . "=" . $alias_main_tbl . "." . $referencePath['column'];
            if (isset($referencePath['parentTable'])) {
                $path = $referencePath['parentTable'];
                $tempQuery .= " " . $this->buildQuery('', $path[0]);
            }

            if ($queryElement == '') {
                return $tempQuery;
            } else {
                $query = "SELECT " . $alias_main_tbl . "." . $queryElement[1] .
                    " FROM " . $queryElement[0] . " " . $alias_main_tbl;
                $query = $query . " 
                                " . $tempQuery;
                return $query;
            }
        } else {
            $query = "SELECT " . $queryElement[1] .
                " FROM " . $queryElement[0];
        }

        return $query;
    }
    public function rundefinedfunctionAction()
    {
        
        $model = new Default_Model_Userdefinedclass();
        $userDefined = $model->getUserDefinedFunction('main_perk');
        Zend_Debug::dump($userDefined);
        foreach ($userDefined as $defined) {
            $parameterValues = [];
            $parameterArray = explode(',', $defined['parameters']);
          
            foreach ($parameterArray as $parameter) {
                $tempArray = explode('.', $parameter);

                $referencePath = $this->getTableReferencePath($tempArray[0], $tempArray[1]);
                $query = $this->buildQuery($tempArray, $referencePath);
                $parameterValue = $model->getParameterValues($query);
                array_push($parameterValues, $parameterValue);
            }
            $values = $this->executeMethod($defined, $parameterValues);
            Zend_Debug::dump($values, "Returned Values=> ");
           
        }
        die("here");
    }
    public function executeMethod($methodInfo, $parameterValues)
    {
        $classPath = str_replace('\\', '', $methodInfo['classPath']);
        $tempArray = explode('.', $methodInfo['method']);
        $class = $tempArray[0];
        $method = $tempArray[1];
        $class_method = str_replace('.', '::', $methodInfo['method']);
        require_once '' . $classPath . $class . ".php";

        $smallestLen = -1;
        $argValue = [];
        foreach ($parameterValues as $parameterValue) {
            if ($smallestLen == -1 || $smallestLen > count($parameterValue)) {
                $smallestLen = count($parameterValue);
            }
        }
        for ($i = 0; $i < $smallestLen; $i++) {
            $temp_argValue = [];
            foreach ($parameterValues as $parameterValue) {
                foreach ($parameterValue[$i] as $key => $value) {
                    $temp_argValue[$key] = $value;
                }
            }
            array_push($argValue, $temp_argValue);
        }
        Zend_Debug::dump($argValue, "Arg Values ");
        $returnValues = [];
        foreach ($argValue as $value) {
            echo "Class ". $class_method."\n";
            echo "Argument values ". json_encode($value);
            $returnValue = call_user_func_array($class_method, $value); 
            array_push($returnValues, $returnValue);
        }
        return $returnValues;
    }
    public function getfieldsAction()
    {
        //require_once 'Classes/utilClasses/Util.php';
        require_once 'application/modules/default/library/utilClasses/Util.php';
        $util = new utlis();
        $tables = $util->tablesList();
        $res = $util->getfields($_POST['fieldType'], $tables, '');
        die($res);
        //getfields('double',[]);

        /* if (isset($_POST['fieldType'])) {

            $fieldType = '';
            if ($_POST['fieldType'] == 'string') {
                $fieldType = 'varchar';
            } else if ($_POST['fieldType'] == 'int') {
                $fieldType = $_POST['fieldType'];
            } else if ($_POST['fieldType'] == 'float') {
                $fieldType = $_POST['fieldType'];
            } else {
                $fieldType = 'double';
            }
            $model = new Default_Model_Userdefinedclass();
            $data = $model->getTypeMatchedFields($fieldType, ['main_empsalarydetails', 'main_attendance'], 'shift');
            die(json_encode($data));
        } else {
            $error['message'] = "fieldType is required!";
            die(json_encode($error));
        } */
    }
}
