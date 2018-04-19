<?php 
/** 
 * LockSystem.php 
 * 
 * php锁机制
 * 
 * Copy right (c) 2016
 * 
 * modification history: 
 * -------------------- 
 * 2016/9/10, by CleverCode, Create 
 * 
 */
class LockSystem
{
 const LOCK_TYPE_DB = 'SQLLock';
 const LOCK_TYPE_FILE = 'FileLock';
 const LOCK_TYPE_MEMCACHE = 'MemcacheLock';
   
 private $_lock = null;
 private static $_supportLocks = array('FileLock', 'SQLLock', 'MemcacheLock'); 
   
 public function __construct($type, $options = array()) 
 {
  if(false == empty($type))
  {
   $this--->createLock($type, $options);
  }
 } 
 public function createLock($type, $options=array())
 {
  if (false == in_array($type, self::$_supportLocks))
  {
   throw new Exception("not support lock of ${type}");
  }
  $this->_lock = new $type($options);
 }  
 public function getLock($key, $timeout = ILock::EXPIRE)
 {
  if (false == $this->_lock instanceof ILock) 
  {
   throw new Exception('false == $this->_lock instanceof ILock');   
  } 
  $this->_lock->getLock($key, $timeout); 
 }
   
 public function releaseLock($key)
 {
  if (false == $this->_lock instanceof ILock) 
  {
   throw new Exception('false == $this->_lock instanceof ILock');   
  } 
  $this->_lock->releaseLock($key);   
 } 
}
interface ILock
{
 const EXPIRE = 5;
 public function getLock($key, $timeout=self::EXPIRE);
 public function releaseLock($key);
}
  
class FileLock implements ILock
{
 private $_fp;
 private $_single;
  
 public function __construct($options)
 {
  if (isset($options['path']) && is_dir($options['path']))
  {
   $this->_lockPath = $options['path'].'/';
  }
  else
  {
   $this->_lockPath = '/tmp/';
  }
   
  $this->_single = isset($options['single'])?$options['single']:false;
 }
 public function getLock($key, $timeout=self::EXPIRE)
 {
  $startTime = Timer::getTimeStamp();
  
  $file = md5(__FILE__.$key);
  $this->fp = fopen($this->_lockPath.$file.'.lock', "w+");
  if (true || $this->_single)
  {
   $op = LOCK_EX + LOCK_NB;
  }
  else
  {
   $op = LOCK_EX;
  }
  if (false == flock($this->fp, $op, $a))
  {
   throw new Exception('failed');
  }
   
  return true;
 }
 public function releaseLock($key)
 {
  flock($this->fp, LOCK_UN);
  fclose($this->fp);
 }
}
  
class SQLLock implements ILock
{
 public function __construct($options)
 {
  $this->_db = new mysql(); 
 }
  
 public function getLock($key, $timeout=self::EXPIRE)
 {  
  $sql = "SELECT GET_LOCK('".$key."', '".$timeout."')";
  $res = $this->_db->query($sql);
  return $res;
 }
  
 public function releaseLock($key)
 {
  $sql = "SELECT RELEASE_LOCK('".$key."')";
  return $this->_db->query($sql);
 }
}
  
class MemcacheLock implements ILock
{
 public function __construct($options)
 {
    
  $this->memcache = new Memcache();
 }
  
 public function getLock($key, $timeout=self::EXPIRE)
 {  
  $waitime = 20000;
  $totalWaitime = 0;
  $time = $timeout*1000000;
  while ($totalWaitime < $time && false == $this->memcache->add($key, 1, $timeout)) 
  {
   usleep($waitime);
   $totalWaitime += $waitime;
  }
  if ($totalWaitime >= $time)
   throw new Exception('can not get lock for waiting '.$timeout.'s.');
  
 }
  
 public function releaseLock($key)
 {
  $this->memcache->delete($key);
 }
}