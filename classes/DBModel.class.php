<?php

  /**
   * Class which keeps track of which records should be tweeted and which should
   * be deleted.
   * @author Scott Smitelli
   * @package twanslationparty
   */

  class DBModel extends Database {
    private $tableName = '';
  
    /**
     * Constructor function. Passes the configuration directly to the Database
     * base class to allow it to read the DB connection info.
     * @access public
     * @param array $config The configuration array
     */
    public function __construct($config) {
      $this->tableName = $config['table'];
    
      // Connect to the DB
      parent::__construct($config);
    }
    
    /**
     * @access public
     */
    public function getNextTweet() {
      self::db_query("
        SELECT `tweets`.*
        FROM `tweets` LEFT JOIN `{$this->tableName}` ON `tweets`.`id` = `{$this->tableName}`.`original_id`
        WHERE
          `{$this->tableName}`.`original_id` IS NULL
          AND `tweets`.`rt_id` = 0
          AND `tweets`.`deleted` IS NULL
        ORDER BY `tweets`.`id` ASC LIMIT 1
      ");
      return self::db_fetch();
    }
    
    /**
     * @access public
     */
    public function getNextDeleted() {
      self::db_query("
        SELECT `{$this->tableName}`.`translated_id`
        FROM `tweets` LEFT JOIN `{$this->tableName}` ON `tweets`.`id` = `{$this->tableName}`.`original_id`
        WHERE
          `{$this->tableName}`.`translated_id` IS NOT NULL
          AND `tweets`.`rt_id` = 0
          AND `tweets`.`deleted` IS NOT NULL
        ORDER BY `tweets`.`id` ASC LIMIT 1
      ");
      return self::db_fetch();
    }
    
    /**
     * @access public
     */
    public function insertTranslation($origID, $transID) {
      self::db_query("
        INSERT INTO `{$this->tableName}` (`original_id`, `translated_id`)
        VALUES(:origID, :transID)
      ", array('origID' => $origID, 'transID' => $transID));
    }
    
    /**
     * @access public
     */
    public function markTranslationDeleted($transID) {
      self::db_query("UPDATE `{$this->tableName}` SET `translated_id` = NULL WHERE `translated_id` = :transID", array('transID' => $transID));
    }
  }

?>