<?php
namespace TymFrontiers;

class ToDo{
  use Helper\MySQLDatabaseObject,
      Helper\Pagination;

  protected static $_primary_key='id';
  protected static $_db_name;
  protected static $_table_name;
  protected static $_db_fields=["id",	"priority",	"done",	"user",	"method", "force_do", "uid", "path",	"title","description","action", "expiry",	"_created",	"_updated"];

  public $id;
  public $priority = 10;
  public $done = false;
  public $user;
  public $method = "POPUP";
  public $force_do = false;
  public $uid;
  public $path='/';
  public $title;
  public $description;
  public $action;
  public $expiry;

  protected $_created;
  protected $_updated;

	public $errors = []; # follows Tym Error system

  function __construct($prop = []){
    $this->_checkEnv();
    if( \is_array($prop) && !empty($prop) ) $this->commit($prop);
  }
  private function _checkEnv(){
    if( ! \defined('TODO_DB') ){
      throw new \Exception("File storage database not defined. Define constance 'TODO_DB' to hold name of database where file meta info will be stored.", 1);
    }
    if( ! \defined('TODO_TBL') ){
      throw new \Exception("File storage table not defined. Define constance 'TODO_TBL' to hold name of database table where file meta info will be stored.", 1);
    }
    $this->setDatabase( \TODO_DB );
    $this->setTable( \TODO_TBL );
  }
  public function load(int $id){
    return self::findById($id);
  }
  public function commit(array $prop){
    foreach($prop as $prop=>$val){
      if( \property_exists($this,$prop) ) $this->$prop = $val;
    }
    if( empty($this->id) ){
      if(
        !empty($this->user)
        && !empty($this->description)
        && !empty($this->title)
        && !empty($this->uid)
      ){
        if( empty($this->path) ) $this->path = '/';
        return $this->_create();
      }
    }
    return false;
  }
  public function setDatabase(string $db){
    self::$_db_name = \preg_replace('/[^\w]+/', '', \str_replace(' ','_',$db) );
  }
  public function setTable(string $tbl){
    self::$_table_name = \preg_replace('/[^\w]+/', '', \str_replace(' ','_',$tbl) );
  }
  public function done(){
    if( $this->done ) return true;
    if( !empty($this->id) ){
      $this->done = true;
      return $this->_update();
    }
    return false;
  }

  public function goDo(string $user, string $path='', string $rdt=''){
    global $db;
    $user = \strtoupper($db->escapeValue($user));
    $sql = "SELECT *
            FROM :db:.:tbl:
            WHERE user='{$user}'
            AND method='REDIRECT'
            AND done=FALSE
            AND (
              expiry <= 0 OR expiry > NOW()
            )
            AND action !='' ";
    if( !empty($path) ) $sql .= " AND `path`='{$db->escapeValue($path)}' ";
    $sql .= " ORDER BY
                priority ASC,
                _created DESC
              LIMIT 1 ";
    $found = self::findBySql($sql);
    $found = $found ? $found[0] : false;
    if( $found ){
      $rdt = empty($rdt)
          ? \urldecode($found->action)
          : Generic::setGet(\urldecode($found->action),['rdt'=>$rdt]);
      HTTP\Header::redirect($rdt);
    }
  }
  public function undone(string $user, string $path=''){
    global $db;
    $user = \strtoupper($db->escapeValue($user));
    $sql = "SELECT *
            FROM :db:.:tbl:
            WHERE user='{$user}'
            AND done=FALSE
            AND (
              expiry <= 0 OR expiry > NOW()
            ) ";
    if( !empty($path) ) $sql .= " AND `path`='{$db->escapeValue($path)}' ";
    $sql .= "ORDER BY
              priority ASC,
              _created DESC";
    $found = self::findBySql($sql);
    return $found;
  }
}
