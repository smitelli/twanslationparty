<?php

  /**
   * twanslationparty: This is whose sole purpose is to butcher English script.
   *
   * @author Scott Smitelli
   * @package twanslationparty
   */

  // Support autoloading classes as they are needed
  define('APP_DIR', realpath(dirname(__FILE__)));
  spl_autoload_register(function($class_name) {
    $class_file = sprintf(APP_DIR . "/classes/{$class_name}.class.php");
    if (is_readable($class_file)) {
      // File exists; load it
      require_once $class_file;
    }
  });

  // Load and parse the configuration file
  $config = @parse_ini_file(APP_DIR . '/config.ini', TRUE);
  if (empty($config)) {
    die("The file config.ini is missing or malformed.\n\n");
  }

  // Classes that make things so much easier
  $twitter  = new TwitterWrapper($config['twitter']);
  $trparty  = new TranslationParty($config['translation']);
  $database = new DBModel($config['mysql']);

  // Delete any old tweets that may still be hanging around
  while ($row = $database->getNextDeleted()) {
    $twitter->deleteTweet($row->translated_id);  //TODO check success?
    $database->markTranslationDeleted($row->translated_id);
  }

  // Go through all the new tweets and repost their translations
  while ($tweet = $database->getNextTweet()) {
    // Mangle the text for a single tweet
    $oldText = $tweet->text;
    $tweet->text = $trparty->mangle($tweet->text);
    $tweet->text = $twitter::salvage($oldText, $tweet->text);

    try {
      // Try to send this tweet
      echo "Sending [{$tweet->text}]...";
      $newID = $twitter->sendTweet($tweet);
      if ($newID) {
        echo " got {$newID}!\n";
      } else {
        echo " warning, giving up!\n";
      }

    } catch (TwitterException $e) {
      // Error sending the tweet; shut the whole script down for safety
      die($e->getMessage() . "\n\n");
    }

    // Mark this tweet as successfully inserted
    $database->insertTranslation($tweet->id, $newID);
  }

?>
