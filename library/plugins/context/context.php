<?php

class context extends Codup\main
{
    
   
    function run ($method)
    {
      
        echo "<pre>";
      
         //$cosql = new cosql\basemodel();
         $test = models\cms_component::getInstance();
         for($i = 0;$i<150;$i++){
             $test->name = "hussein$i";
             $test->cms_componenttype_id = 6;
             $test->is_enabled = 'Y';
             $test->save();
         }
        // $res = $test->find(array('name'=>'hussein'))->fetch($method);
         /*
         var_dump($test->errors);var_dump($res);exit;
         $res->getObject()->name = "guettaf5";
         $res->getObject()->save();
        
         
       //  echo self::$t;
         if(!$res){
             var_dump($test->errors);
             return;
         }
         while ( $r = $res->iterate()){
         //$test2 = (array) $res; 
         $r->name = "guettaf6";
         //$r->cms_componenttype_id = 7;
         //$r->is_enabled = 'Y';    
         $r->save();

         }
         var_dump($res);
        */
        // ;
        /*
         

           */
        if ($this->isAjax()) {
            $this->register('context', 'html');
            echo "AJax";
        }
    }
}