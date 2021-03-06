<?php
/**
 * Entity class.
 * @version 	1.0
 * @author 	Housseyn Guettaf <ghoucine@gmail.com>
 * @package 	Phiber
 */
namespace Phiber\entity;

use Phiber\oosql\oosql;

abstract class entity
{

  protected static $oosql_model_extra = false;
  protected static $oosql_obj;
  protected static $tablename;


  public function __construct($oosql = null)
  {
    if(null !== $oosql){
      static::$oosql_obj = $oosql;
    }else{
      static::$oosql_obj = static::getooSQL(get_class($this));
    }

  }
  /*
   * Deliver an oosql instance instead and pass this class name so we can get
   * back with the caller instance with results
   */
  public static function getInstance()
  {
    return self::getooSQL(get_class(new static));
  }

  protected static function getooSQL($class)
  {

    self::$tablename = strstr($class, '\\');
    if(self::$tablename === false){
      return false;
    }

    self::$tablename = trim(str_replace('\\', '', self::$tablename));
    return self::$oosql_obj = oosql::getInstance(self::$tablename, $class);
  }

  public function save($saveRelated = false)
  {

    $instances = array();

    if(self::$oosql_model_extra){

      $originalProps = get_object_vars(new static);

      $mixedProps = array_keys(get_object_vars($this));



      foreach($mixedProps as $property){

        if(! key_exists($property, $originalProps)){

          if($saveRelated){
            $current = $this;
            foreach($this->getRelations() as $fk => $relation){

              $table = strstr($relation,'.',true);
              $field = ltrim(strstr($relation,'.'),'.');
               if(is_array($saveRelated)){
                 if(!in_array($table,$saveRelated)){
                   continue;
                 }
               }
              $entityname = "entity\\$table";
              $classVars = get_class_vars($entityname);

              if(array_key_exists($property,$classVars)){

                $hash = hash('adler32',$entityname);

                if(isset($instances[$hash])){
                  $instance = $instances[$hash];
                }else{
                  $instance = new $entityname;
                }
                //Set the property in the related object
                $instance->{$property} = $this->{$property};

                $instance->{$field} = $this->{$fk};

                $instances[$hash] = $instance;

                unset($instance);
              }

            }


          }
          unset($this->{$property});
        }
      }

    }

    if($saveRelated && count($instances)){

        try{
          foreach($instances as $inst){
            $inst->save();
          }
          return $current->save();
        }catch(\Exception $e){
          throw $e;
        }

    }else{
      return $this->reset()->save($this);
    }
  }
  public function reset()
  {
    return self::$oosql_obj->reset();
  }
  public function load($num = null)
  {
    $primary = $this->getPrimary();
    if(isset($this->{$primary[0]})){
      return self::$oosql_obj->findAll($this->getPrimaryValue())->fetch($num);
    }
  }

  public function __set($var, $val)
  {
    if(! key_exists($var, get_object_vars(new static))){
      self::$oosql_model_extra = true;
    }
    $this->{$var} = $val;
  }

  public function __unset($property)
  {
    unset($this->properties[$property]);
  }

  public function __call($tablename, $arg)
  {
    $relations = $this->getRelations();

    foreach($relations as $fk => $target){
      $objPath = explode('.', $target);
      if($objPath[0] == $tablename){
        $tablename = "entity\\$tablename";
        $instance = $tablename::getInstance()->getEntityObject();
        $instance->{$objPath[1]} = $this->{$objPath[1]};
        if($arg === true){
          return $instance->load();
        }
        return $instance;
      }

    }
  }

  public function __get($var)
  {
    if(key_exists($var, get_object_vars(new static))){
      return $this->{$var}();
    }

  }
  private function callFunc($fn,$args)
  {
    $class = get_class($this);
    return  call_user_func_array(array(self::getoosql($class),$fn), $args);
  }
  public function select()
  {
    return $this->callFunc('select',func_get_args());
  }
  public function insert()
  {
    return $this->callFunc('insert',func_get_args());
  }
  public function update()
  {
    return $this->callFunc('update',func_get_args());
  }
  public function delete()
  {
    $class = get_class($this);
    return $this->callFunc('deleteRecord',array(self::getoosql($class),$this->getPrimaryValue()));
  }
  public function getPrimary()
  {
    return array();
  }
  public function getPrimaryValue()
  {
    return array();
  }
  public function getCompositeValue()
  {
    return array();
  }
  public function getRelations()
  {
    return array();
  }
  public function belongsTo()
  {
    return array();
  }
  public function hasOne()
  {
    return array();
  }
  public function hasMany()
  {
    return array();
  }
}
?>