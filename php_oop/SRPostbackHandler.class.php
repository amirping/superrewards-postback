<?php

/*
  SuperRewards.com App Postback Handling Script for Publishers.

  This class is for use with the accompanying ./postback.php
*/

class SRPostbackHandler 
{
  function __construct($app_secret, $db_user, $db_password, $db_host, $db_host_port, $db_name, $db_prefix) 
  {
    $this->app_secret = $app_secret;
    $this->db_user = $db_user;
    $this->db_password = $db_password;
    $this->db_host = $db_host;
    $this->db_host_port = $db_host_port;
    $this->db_name = $db_name;
    $this->db_prefix = $db_prefix;
  }

  function HandlePostback($id = 0, $uid = 0, $oid = 0, $new = 0, $total = 0, $sig = 0)
  {
    /**
     * Sanity check.
     *
     * If you are using alphanumeric user ids, remove the is_numeric($uid) check. Alphanumeric
     * ids can only be enabled by Super Rewards Support
     *
     * If you are using alphanumeric user ids, please ensure that you use the appropriate URL-encoding
     * for non-text or unicode characters. For example: ~ should be encoded as %7E
     */
    if(!(is_numeric($id) && is_numeric($uid) && is_numeric($oid) && is_numeric($new) && is_numeric($total)))
      return 0;

    $this->id = $id; // ID of this transaction.
    $this->uid = $uid; // ID of the user which performed this transaction. 
    $this->oid = $oid; // ID of the offer or direct payment method.
    $this->new = $new; // Number of in-game currency your user has earned by completing this offer.
    $this->total = $total; // Total number of in-game currency your user has earned on this App.
    $this->sig = $sig; // Security hash used to verify the authenticity of the postback.

    $result = 1;

    $sig_compare = md5($this->id.':'.$this->new.':'.$this->uid.':'.$this->app_secret);

    // Only accept if the Security Hash matches what we have.
    if($this->sig == $sig_compare)
    {
      $timestamp = date("Y-m-d H:i:s", time());

      try 
      {
        // Connect to Database.
        $dbh = new PDO("mysql:host=".$this->db_host.";dbname=".$this->db_name.";port=".$this->db_host_port, $this->db_user, $this->db_password, array( PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING ));

        // Add new transaction
        $query = $dbh->prepare("INSERT INTO ".$this->db_prefix."transactions(id, uid, oid, new, time) VALUES (:id,:uid,:oid,:new,:time) ON DUPLICATE KEY UPDATE id=:id,uid=:uid,oid=:oid,new=:new,time=:time");
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->bindParam(':uid', $uid, PDO::PARAM_INT);
        $query->bindParam(':oid', $oid, PDO::PARAM_INT);
        $query->bindParam(':new', $new, PDO::PARAM_INT);
        $query->bindParam(':time', $timestamp, PDO::PARAM_STR);
        if(!$query->execute())
          $result = 0; // Problems executing SQL. Fail.

        // Add/Update user.
        $query = $dbh->prepare("INSERT INTO ".$this->db_prefix."users(uid, total, time) VALUES (:uid,:total,:time) ON DUPLICATE KEY UPDATE uid=:uid,total=:total,time=:time");
        $query->bindParam(':uid', $uid, PDO::PARAM_INT);
        $query->bindParam(':total', $total, PDO::PARAM_INT);
        $query->bindParam(':time', $timestamp, PDO::PARAM_STR);
        if(!$query->execute())
          $result = 0;  // Problems executing SQL. Fail.

        $dbh = null;
      }
      catch (PDOException $e) 
      {
        exit($e->getMessage());
      }
    }
    else
      $result = 0; // Security hash incorrect. Fail.

    return $result;
  }

  function SetupTables()
  {
    $query = 
    "CREATE TABLE IF NOT EXISTS `".$this->db_prefix."transactions` (
    `id` INT NOT NULL,
    `uid` BIGINT,
    `oid` INT,
    `new` INT,
    `time` DATETIME,
    PRIMARY KEY (`id`)) 
    CHARACTER SET utf8 COLLATE utf8_general_ci;

    CREATE TABLE IF NOT EXISTS `".$this->db_prefix."users` (
    `uid` BIGINT NOT NULL,
    `total` INT,
    `time` DATETIME,
    PRIMARY KEY (`uid`)) 
    CHARACTER SET utf8 COLLATE utf8_general_ci;";

    try 
    {
      // Connect to Database.
      $dbh = new PDO("mysql:host=".$this->db_host.";dbname=".$this->db_name.";port=".$this->db_host_port, $this->db_user, $this->db_password, array( PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING ));
      $query = $dbh->prepare($query);
      if(!$query->execute())
        echo "Could not create tables in database: ".$this->db_name." @ ".$this->db_host.'. Check your configuration above.';
      else
        echo "Tables setup successfully!";

      $dbh = null;
    }
    catch (PDOException $e) 
    {
      exit($e->getMessage());
    }
  }
}

?>
