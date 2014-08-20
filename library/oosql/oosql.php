<?php
namespace Phiber\oosql;

class oosql extends \PDO
{
/**
 * $oosql_result After a select this field will hold a copy of the result
 * @var collection
 * @access protected
 * @static
 */
  protected static $oosql_result = null;
  /**
   * $oosql_class The class name of the entity
   * @var string
   * @access protected
   */
  protected $oosql_class;
  /**
   * $oosql_table The table we are querying
   * @var string
   * @access protected
   */
  protected $oosql_table;

  /**
   * $oosql_entity_obj An instance of the entity class
   * @var mixed
   * @access private
   */
  private $oosql_entity_obj = null;
  /**
   * $oosql_limit
   * @var string Limit clause
   * @access private
   */
  private $oosql_limit = null;

  private $oosql_order = null;

  private $oosql_where = null;

  private $oosql_join = null;

  private $oosql_stmt;

  private $oosql_conValues = array();

  private $oosql_numargs;

  private $oosql_fromFlag = false;

  private $oosql_multiFlag = false;

  private $oosql_del_multiFlag = false;

  private $oosql_multi = array();

  private $oosql_del_numargs;

  private $oosql_sql;

  private $oosql_select;

  private $oosql_distinct = false;

  private $oosql_insert = false;

  private $oosql_sub = false;

  private $oosql_table_alias;

  private $oosql_fields;

  private $oosql_hashes = array();

  private $oosql_exec_prep = false;

  private $oosql_driver;

  private $oosql_in;

  private $oosql_between;

  private static $instance;
  /**
   * __construct()
   * @param string $oosql_table The table we are querying
   * @param string $oosql_class The class name (type of the object holding the results)
   * @throws \Exception
   */
  function __construct($oosql_table = null, $oosql_class = null,$config = null)
  {
    if($oosql_class === null || $oosql_table === null){
      throw new \Exception('Class or Table name not provided!',9805,null);
    }
    if(null === $config){
      $config = \config::getInstance();
    }
    $this->oosql_class = $oosql_class;
    $this->oosql_table = $oosql_table;

    parent::__construct($config->PHIBER_DB_DSN, $config->PHIBER_DB_USER, $config->PHIBER_DB_PASS);
    $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    $dsn = explode(':',$config->PHIBER_DB_DSN);
    $this->oosql_driver = $dsn[0];
  }

  /**
   * Get a copy of the results of the previous select (if any) or null if not
   * @return collection or null
   */
  public static function getPrevious()
  {
    return self::$oosql_result;
  }

  /**
   * Get an instance of this class
   * @return oosql An oosql\oosql object
   * @static
   */
  public static function getInstance($oosql_table = null, $oosql_class = null, $config = null)
  {


    if(null !== self::$instance){
      self::$instance->reset();
      self::$instance->setClass($oosql_class);
      self::$instance->setTable($oosql_table);

      return self::$instance;
    }
    return self::$instance = new self($oosql_table, $oosql_class,$config);
  }
  public function reset()
  {

    $this->oosql_limit = null;

    $this->oosql_order = null;

    $this->oosql_where = null;

    $this->oosql_join = null;

    $this->oosql_stmt = null;

    $this->oosql_conValues = array();

    $this->oosql_numargs = null;

    $this->oosql_fromFlag = false;

    $this->oosql_multiFlag = false;

    $this->oosql_del_multiFlag = false;

    $this->oosql_multi = array();

    $this->oosql_del_numargs = null;

    $this->oosql_sql = null;

    $this->oosql_select = null;

    $this->oosql_distinct = false;

    $this->oosql_insert = false;

    $this->oosql_sub = false;

    $this->oosql_table_alias = null;

    $this->oosql_fields = array();

    $this->oosql_exec_prep = false;

    $this->oosql_entity_obj = null;

    $this->oosql_in = null;

    $this->oosql_between = null;

    return $this;
  }
  public function setClass($class)
  {
    $this->oosql_class = $class;
  }
  public function setTable($table)
  {
    $this->oosql_table = $table;
  }
  /**
   * Returns an instance of the entity class used
   */
  public function getEntityObject()
  {
    if(null != $this->oosql_entity_obj){
      return $this->oosql_entity_obj;
    }else{
      return new $this->oosql_class($this);

    }
  }
  private function sql($sql = null,$replace = false)
  {
    if(null !== $sql){
      if(!isset($this->oosql_sql[$this->oosql_table]) || $replace){
        $this->oosql_sql[$this->oosql_table] = $sql;
      }else{

        $this->oosql_sql[$this->oosql_table] .= $sql;
      }
      return;
    }
    return $this->oosql_sql[$this->oosql_table];
  }
  public function select()
  {
    self::$instance->reset();
    $this->sql('SELECT ');
    if($this->oosql_distinct ){
      $this->sql('DISTINCT ');
    }
    $numargs = func_num_args();

    if($numargs > 0){

      $arg_list = func_get_args();

      $this->oosql_fields = $arg_list;

      for($i = 0; $i < $numargs; $i++){
        if($i != 0 && $numargs > 1){
          $this->sql(',');
        }
        $this->sql($arg_list[$i]);

      }
    }else{
      $this->oosql_fields[] = '*';
      $this->sql($this->oosql_table.'.* ');
    }

    $this->oosql_fromFlag = true;
    $this->oosql_select = $this;
    $this->oosql_where = null;
    return $this;
  }
  public function getFields($table = null)
  {
    if(null == $table){
      $table = $this->oosql_table;
    }
    $newFields = array();
    foreach ($this->oosql_fields as $field){
      $newFields[] = $table.'.'.$field;
    }
    return $newFields;
  }
  public function getPlainFields()
  {
    return $this->oosql_fields;
  }
  public function insert()
  {
    self::$instance->reset();
    $this->sql('INSERT INTO '.$this->oosql_table);

    $arg_list = func_get_args();
    $numargs = func_num_args();

    if($numargs > 0){
      $this->oosql_numargs = $numargs;
      $this->sql(' (');

      $this->sql(implode(',', $arg_list));

      $this->sql(')');
    }
    $this->oosql_insert = true;
    return $this;
  }

  public function update()
  {
    self::$instance->reset();
    $this->sql('UPDATE');

    $numargs = func_num_args();

    if($numargs > 0){
      $arg_list = func_get_args();

      $this->oosql_multiFlag = true;

      $this->oosql_multi = $arg_list;

      for($i = 0; $i < $numargs; $i++){
        if($i != 0 && $numargs > $i){
          $this->sql(',');
        }
        $this->sql(' ' . $arg_list[$i]);
      }
    }else{
      $this->sql(" $this->oosql_table");
    }

    $this->sql(' SET ');
    $this->oosql_where = null;
    return $this;
  }

  public function delete()
  {
    self::$instance->reset();
    $this->sql('DELETE');
    $this->oosql_where = null;
    $numargs = func_num_args();

    if($numargs > 0){
      if($numargs > 1){
        $this->oosql_del_multiFlag = true;
        $this->oosql_del_numargs = $numargs;
      }
      $arg_list = func_get_args();
      if(is_array($arg_list[0])){
        $this->sql(' FROM '.$this->oosql_table);
        $this->where($arg_list[0][0].' = ?', $arg_list[0][1]);
        return $this;
      }
      $this->oosql_sql .= ' FROM';
      for($i = 0; $i < $numargs; $i++){
        if($i != 0 && $numargs > 1){
          $this->sql(',');
        }
        $this->sql(' ' . $arg_list[$i]);
      }

    }else{
      $this->oosql_fromFlag = true;
    }


    return $this;
  }

  public function deleteRecord($oosql = null,array $criteria)
  {
    if(null == $oosql){
      $oosql = $this;
    }
    $oosql->delete()->createWhere($criteria)->exe();
    return $this;
  }
  /**
   * Sets the column, value pairs in update queries
   * @param array $data An array of the fields with their corresponding values in a key => value format
   */
  public function set(array $data)
  {
    $sql = '';
    foreach($data as $field => $value){

        $sql .= $field.' = ?,';
        $this->oosql_conValues[] = $value;

    }
    $this->sql(rtrim($sql, ','));

    return $this;
  }

  /**
   * Decides if this is an insert or an update and what fields have changed if appropriate
   * @param mixed $object If null this is an insert if not than it's an update
   * @throws \Exception
   */
  public function save($object = null)
  {
    $data = null;
    if(null === $object){
      if(null === $this->oosql_entity_obj){
        $msg = 'Nothing to save! ' . $this->sql();
        throw new \Exception($msg,9806,null);
      }
      // This is a brand new record let's insert;
      $entity = $this->getEntityObject();
      $fields = (array) $entity;

      if($identity = $entity->identity()){
        unset($fields[$identity]);
      }
      $fieldnames = array_keys($fields);
      $fvalues = array_values($fields);
      $lastID = $this->insert(implode(',', $fieldnames))->values($fvalues)->exe();
      $entity->{$identity} = $lastID;

      return $entity;
    }

    if(isset(self::$oosql_result)){
      // Updating after a select

      $primaryField = $object->getPrimary();

      $old = self::$oosql_result->objectWhere($primaryField[0], $object->{$primaryField[0]});

      // Is it really a modification of a selected row?
      if($old){

      $identity = $this->getEntityObject()->identity();

      foreach(array_diff((array) $object, (array) $old) as $key => $value){
        if($key == $identity){
          continue;
        }
        $data[$key] = $value;
      }


      if(null === $data){
        $msg = 'Nothing to save! ' .$this->sql();
        throw new \Exception($msg,9807,null);
      }
      $this->update()->set($data)->createWhere($object->getPrimaryValue())->exe();

      return $object;

      }

    }
      // update a row just after inserting it
      // Or
      // update a related table (no select on it)
      $identity = $object->identity();

      foreach((array)$object as $k => $v){
        if($v === null || $k == $identity){
          continue;
        }
        $data[$k] = $v;
      }

      if(count($data) !== 0){

        $this->update()->set($data)->createWhere($object->getPrimaryValue())->exe();

        return $object;
      }
      $msg = 'Nothing to save! ' . $this->sql();
      throw new \Exception($msg,9808,null);
  }

  /**
   * Creates where clause(s) from an array of conditions
   * @param array $conditions An array of conditions in the format:
   *              <code>array("column" => $value)</code>
   */
  public function createWhere(array $conditions, $operator = '=', $condition = 'and')
  {

    foreach($conditions as $col => $value){

      if(null === $this->oosql_where){
        $this->where($col . ' '.$operator.'?', $value);
        continue;
      }

      $method = $condition.'Where';

      $this->{$method}($col . ' '.$operator.'?', $value);

    }

    return $this;
  }

  /**
   * Assembles values part of an insert
   * @throws \Exception
   */
  public function values()
  {

    $arg_list = func_get_args();

    $numargs = func_num_args();

    if(($this->oosql_numargs !== 0 && $numargs !== $this->oosql_numargs) || $numargs === 0){
      $msg = 'Insert numargs: '.$this->oosql_numargs.' | values numargs = '.$numargs.', Columns and passed data do not match! ' . $this->sql();
      throw new \Exception($msg,9809,null);
    }

    $this->sql(' VALUES (');

    for($i = 0; $i < $numargs; $i++){
      if($i != 0 && $numargs > 1){
        $this->sql(',');
      }

      if(is_array($arg_list[$i])){
        $this->sql(rtrim(str_repeat('?,',count($arg_list[$i])),','));
        $this->oosql_conValues += $arg_list[$i];
      }else{
      $this->oosql_conValues[] = $arg_list[$i];
      $this->sql(' ?');
      }
    }


    $this->sql(')');

    $this->oosql_fromFlag = false;
    return $this;
  }

  /**
   * Assembles the FROM part of the query
   * @throws \Exception
   */
  public function from()
  {
    $from = '';

    $numargs = func_num_args();

    if($this->oosql_del_multiFlag){


      if($numargs < $this->oosql_del_numargs){
        $msg = 'Columns and passed data do not match! ' . $this->sql();
        throw new \PDOException($msg,9810,null);
      }


    }

    $from .= ' FROM ';

    $from .= $this->oosql_table;

    if($numargs > 0){
      $from .= ', ';

      $arg_list = func_get_args();

      for($i = 0; $i < $numargs; $i++){
        if($i !== 0 && $numargs > $i){
          $from .= ', ';
        }

        if($arg_list[$i] instanceof oosql){

          $arg_list[$i]->alias();
          $fields = $arg_list[$i]->getFields($arg_list[$i]->getTableAlias());

          $this->sql(','.implode(', ',$fields));
          $from .= $arg_list[$i]->getSql().' AS '.$arg_list[$i]->getTableAlias();
        }else{
          $from .= $arg_list[$i];
        }
      }
    }

    $this->sql($from);

    $this->oosql_fromFlag = false;

    return $this;
  }

  public function join($table, $criteria, $type = '')
  {

    $this->oosql_join .= " $type JOIN $table ON $criteria";
    return $this;
  }

  public function joinLeft($table, $criteria)
  {
    return $this->join($table, $criteria, 'LEFT');
  }

  public function joinRight($table, $criteria)
  {
    return $this->join($table, $criteria, 'RIGHT');
  }

  public function joinFull($table, $criteria)
  {
    return $this->join($table, $criteria, 'FULL OUTER');
  }

  public function where($condition, $value=null, $type = null)
  {

    switch($type){
      case null:
        $clause = 'WHERE';
        break;
      case 'or':
        $clause = 'OR';
        break;
      case 'and':
        $clause = 'AND';
        break;
      default:
        $clause = 'WHERE';
    }

      $this->oosql_where .= " $clause $condition";

    if($value instanceof oosql){

      $this->oosql_where .= $value->getSql();
      }elseif(null !== $value){

      $this->oosql_conValues[] = $value;
    }

    return $this;
  }
  public function sub(){
    $this->oosql_sub = true;
    $this->exe();
    $this->sql('('.$this->getSql().')',true);

    return $this;
  }
  public function andWhere($condition, $value=null)
  {
    $this->where($condition, $value, 'and');
    return $this;
  }

  public function orWhere($condition, $value)
  {
    $this->where($condition, $value, 'or');
    return $this;
  }

  public function validInt($val)
  {
    return ctype_digit(strval($val));
  }
  public function prep($values = null)
  {
    $hash = $this->queryHash();

    $prepOnly = true;

    if(is_array($values)){

      $prepOnly = false;

    }

    if(isset($this->oosql_hashes[$hash])){

      $this->oosql_stmt = $this->oosql_hashes[$hash];

    }else{

      $this->oosql_stmt = $this->prepare(trim($this->sql()));
      $this->oosql_hashes[$hash] = $this->oosql_stmt;
    }



    if($prepOnly){

      return $this->oosql_stmt;

    }

    return $this->execBound($this->oosql_stmt,$values);
  }
  public function execBound($stmt,$values)
  {
    $ord = 1;
    foreach($values as $val){

      if($this->validInt($val)){

        $stmt->bindValue($ord, $val, \PDO::PARAM_INT);

      }else{

        $stmt->bindValue($ord, $val, \PDO::PARAM_STR);
      }
      $ord++;
    }

    return  $stmt->execute();
  }
  public function exe()
  {

    if($this->oosql_fromFlag){
      $this->from();
    }
    if(null != $this->oosql_join){
      $this->sql($this->oosql_join);
    }
    if(null != $this->oosql_where){
      $this->sql($this->oosql_where);
    }
    if(null != $this->oosql_in){
      $this->sql($this->oosql_in);
    }
    if(null != $this->oosql_between){
      $this->sql($this->oosql_between);
    }
    if(null != $this->oosql_limit){
      $this->sql(' ' . $this->oosql_limit);
    }
    if(null != $this->oosql_order){
      $this->sql(' ' . $this->oosql_order);
    }

    if(count($this->oosql_conValues) !== 0){

      $return = $this->prep($this->oosql_conValues);
      $this->oosql_conValues = array();
      if($return === false){
        $msg = 'Execution failed! ' . $this->sql();
        throw new \Exception($msg,9811,null);
      }

    }else{

      $this->oosql_stmt = $this->query($this->sql());

    }

    if($this->oosql_insert){

      $identity = $this->getEntityObject()->identity();

      if($identity !== false){
        if($this->oosql_driver == 'pgsql'){
          $identity = $this->oosql_table.'_'.$identity.'_seq';
        }
        $lastID = $this->lastInsertId($identity);

        return $lastID;
      }

    }
    /*
     * $str = $this->oosql_sql." | "; $str .= implode(',
     * ',$this->oosql_conValues)."\r\n"; $f = fopen("g:\log.txt","a+");
     * fwrite($f, $str); fclose($f);
     */
    // echo $this->sql()."</br></br>";


    return $this->oosql_stmt;
  }


  /**
   *
   * @throws \InvalidArgumentException
   * @throws \Exception
   */
  public function fetch()
  {
    if($this->oosql_sub){
      return $this;
    }
    $numargs = func_num_args();
    if($numargs !== 0){
      $argumants = func_get_args();
      switch($numargs){
        case 1:
          $this->limit(0, $argumants[0]);
          break;
        case 2:
          $this->limit($argumants[0], $argumants[1]);
          break;
        default:
          throw new \InvalidArgumentException('Fetch expects zero, one or two arguments as a query result limit',9812,null);
      }
    }

    if(!$this->oosql_select instanceof oosql){
      $this->select();
    }
      $this->oosql_select->exe();



    if(! $this->oosql_stmt){

      $msg = 'Query returned no results! ' . $this->sql();
      throw new \Exception($msg,9814,null);
    }
    $this->oosql_stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->oosql_class);
    $result = $this->oosql_stmt->fetchAll();

    $collection = new collection();

   // foreach($result as $res){
      $collection->addBulck($result);

    //}
    $collection->obj_name = $this->oosql_class;
    self::$oosql_result = clone $collection;

    return $collection;
  }

  public function with(array $related)
  {

    $relations = $this->getEntityObject()->getRelations();
    foreach($relations as $fk => $target){
      $table = strstr($target,'.',true);

      if(in_array($table, $related)){

        $this->sql(" ,$table.*");
        $this->join($table, "$this->oosql_table.$fk = $target");
      }elseif(isset($related[$table])){

        foreach($related[$table] as $field){
          $this->sql(" ,$table.$field");
        }
        $this->join($table, "$this->oosql_table.$fk = $target");
      }
    }
    return $this;
  }

  public function limit($from, $to)
  {
    if(!$this->oosql_multiFlag){
      $this->oosql_limit = ' LIMIT ' . $from . ', ' . $to;
    }
    return $this;
  }
  public function orderBy($field){
    if(!$this->oosql_multiFlag){
      $this->oosql_order = ' ORDER BY ' . $field;
    }
    return $this;
  }

  public function findOne($arg, $operator = null, $fields = array('*'))
  {
    return $this->find($arg, $operator, $fields)->limit(0, 1);
  }

  public function findLimited($arg, $from, $to, $operator = null, $fields = array('*'))
  {
    return $this->find($arg, $operator, $fields)->limit($from, $to);
  }

  public function findAll($arg, $operator = '=', $fields = array('*'))
  {
    return $this->find($arg, $operator = '=', $fields = array('*'));
  }

  public function find($arg, $operator = '=', $fields = array('*'))
  {
    if($fields[0] == '*'){
      $this->select();
    }else{
    $select_args = '';
    foreach ($fields as $key => $field){
      if(is_array($field) && is_string($key)){
         foreach ($field as $part){
           $select_args .= $key.'.'.$part.', ';
         }
      }else{
        $select_args .= $this->oosql_table.'.'.$field.', ';
      }
    }

    $this->select(rtrim($select_args,','));
    }
    if(! is_array($arg)){
      $obj = $this->getEntityObject();
      $pri = $obj->getPrimary();
      $arg = array($pri[0] => $arg);
    }
    $i = 0;
    $flag = '';
    foreach($arg as $col => $val){
      if($i > 0){
        $flag = 'and';
      }
      $this->where("$this->oosql_table.$col $operator ?", $val, $flag);
      $i++;
    }

    return $this;
  }

  /**
   * @todo define these
   */


  public function alias($alias = null)
  {
    if(null === $alias){
      $alias = $this->getTableAlias();
    }
    $this->oosql_table_alias = $alias;
    return $this;
  }
  public function getTableAlias()
  {
    if(null !== $this->oosql_table_alias){
      return $this->oosql_table_alias;
    }
    $hash = $this->queryHash();
    $this->oosql_table_alias = $hash;
    return $hash;
  }
  public function queryHash()
  {
    return hash('adler32',$this->sql());
  }
  public function groupBy($field)
  {
    $this->oosql_group = ' GROUP BY ' . $field;

    return $this;
  }
  public function having()
  {
  }
  public function notIn($item, array $list, $cond = null, $not=true)
  {
    return $this->in($item, $list, $cond, $not);
  }
  public function orIn($item, array $list, $cond = 'or', $not=false)
  {
    return $this->in($item, $list, $cond, $not);
  }
  public function orNotIn($item, array $list, $cond = 'or', $not=true)
  {
    return $this->in($item, $list, $cond, $not);
  }
  public function in($item, array $list, $cond = null, $not=false)
  {
    $inClause = '';

    if(null == $this->oosql_where && null == $this->oosql_in && null == $this->oosql_between){
      $inClause .= ' WHERE ';
    }else{
      $cnd = ' AND ';

      if(null != $cond){

        if(strtolower($cond) == 'or'){
          $cnd = ' OR ';
        }

      }
      $inClause .= $cnd;
    }
    if($not){
      $item = $item.' NOT ';
    }
    $inClause .= $item.' IN ';

    $obj = $this;

    $list = array_map(function($data) use($obj){return (!$obj->validInt($data))?$obj->quote($data):$data;},$list);

    $inClause .= '('.implode(',', $list).')';

    $this->oosql_in = $inClause;

    return $this;
  }
  public function between($item, $low, $up, $cond = null, $not=false)
  {
    $bClause = '';

    if(null == $this->oosql_where && null == $this->oosql_between && null == $this->oosql_in){
      $bClause .= ' WHERE ';
    }else{
      $cnd = ' AND ';

      if(null != $cond){

        if(strtolower($cond) == 'or'){
          $cnd = ' OR ';
        }

      }
      $bClause .= $cnd;
    }

    if($not){
      $item = $item.' NOT ';
    }
    $bClause .= $item.' BETWEEN '.$low.' AND '.$up;

    $this->oosql_between = $bClause;

    return $this;
  }
  public function union()
  {
  }
  public function distinct()
  {
    $this->oosql_distinct = true;
    return $this;
  }
  public  function transaction($fn)
  {
    return self::transactional($fn);
  }
  public static function transactional($fn)
  {
    $oosql = self::getInstance('oosql','void');
    if(!$oosql->beginTransaction()){
      $msg = 'Could not start this transaction. BeginTransaction failed!';
      throw new \Exception($msg,9815,null);
    }
    if(is_callable($fn)){
      $ret = $fn();
      return $ret;
    }
    $msg = 'Please pass a Lamda function as a parameter to this method!';
    throw new \Exception($msg,9816,null);
  }

  public function getSql()
  {
    return $this->sql();
  }
  public function __set($var, $val)
  {

    if(null != $this->oosql_entity_obj){
      $this->oosql_entity_obj->{$var} = $val;
    }else{
      $this->oosql_entity_obj = $this->getEntityObject();
      $this->oosql_entity_obj->{$var} = $val;
    }
    return $this->oosql_entity_obj;
  }



}
?>
