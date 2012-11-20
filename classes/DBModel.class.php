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
     * Queries the database for the oldest tweet that is 1) not a retweet, 2) is
     * not deleted, and 3) has not yet been translated. Returns the entire
     * twitstash object row, or FALSE if there are no eligible tweets
     * @access public
     * @return object The twitstash row of the next tweet to translate, or FALSE
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
     * Queries the database for the oldest tweet that is 1) not a retweet, 2)
     * **HAS** been deleted, and 3) still has a `translated_id` value. This
     * means that the tweet was freshly deleted from the source, but still
     * exists on the destination account. It is therefore eligible for deletion
     * via the Twitter API.
     * @access public
     * @return string The ID string of the next tweet to operate on, or FALSE
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
     * Inserts a translation mapping into the twanslationparty table. This is a
     * mapping between the source tweet's ID and the translated tweet's ID.
     * @access public
     * @param string $origID The ID string of the source tweet
     * @param string $transID The ID string of the newly-translated tweet
     */
    public function insertTranslation($origID, $transID) {
      self::db_query("
        INSERT INTO `{$this->tableName}` (`original_id`, `translated_id`)
        VALUES(:origID, :transID)
      ", array('origID' => $origID, 'transID' => $transID));
    }

    /**
     * Marks a translation as deleted. Once the tweet is removed via the Twitter
     * API, this method ensures that getNextDeleted() will not pick this row up
     * again. We set `translated_id` to NULL because it no longer makes sense to
     * hold onto that data -- Twitter will have permanently deleted that tweet.
     * @access public
     * @param string $transID The ID string of the tweet to mark as deleted
     */
    public function markTranslationDeleted($transID) {
      self::db_query("UPDATE `{$this->tableName}` SET `translated_id` = NULL WHERE `translated_id` = :transID", array('transID' => $transID));
    }
  }

?>