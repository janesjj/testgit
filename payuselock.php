<?php
  
/** 
 * pay.php 
 * 
 * 支付应用锁
 * 
 * Copy right (c) 2016 
 * 
 * modification history: 
 * -------------------- 
 * 2016/9/10, by CleverCode, Create 
 * 
 */
//用户支付
function pay($userId,$money)
{
   
 if(false == is_int($userId) || false == is_int($money))
 {
  return false;
 } 
   
 try
 {
  //创建锁(推荐使用MemcacheLock)
  $lockSystem = new LockSystem(LockSystem::LOCK_TYPE_MEMCACHE);    
    
  //获取锁
  $lockKey = 'pay'.$userId;
  $lockSystem--->getLock($lockKey,8);
    
  //取出总额
  $total = getUserLeftMoney($userId);
    
  //花费大于剩余
  if($money > $total)
  {
   $ret = false; 
  }
  else
  { 
   //余额
   $left = $total - $money;
     
   //更新余额
   $ret = setUserLeftMoney($userId,$left);
  }
    
  //释放锁
  $lockSystem->releaseLock($lockKey); 
 }
 catch (Exception $e)
 {
  //释放锁
  $lockSystem->releaseLock($lockKey);  
 }
  
}
//取出用户的余额
function getUserLeftMoney($userId)
{
 if(false == is_int($userId))
 {
  return 0;
 }
 $sql = "select account form user_account where userid = ${userId}";
   
 //$mysql = new mysql();//mysql数据库
 return $mysql->query($sql);
}
  
//更新用户余额
function setUserLeftMoney($userId,$money)
{
 if(false == is_int($userId) || false == is_int($money))
 {
  return false;
 }  
   
 $sql = "update user_account set account = ${money} where userid = ${userId}";
   
 //$mysql = new mysql();//mysql数据库
 return $mysql->execute($sql);
}
?>