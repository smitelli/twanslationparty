<?php

  /**
   * Twitter Wrapper Class. Performs a dead-simple interface to communicate with
   * the Twitter API given a set of OAuth keys.
   * @author Scott Smitelli
   * @package twanslationparty
   */

  class TwitterWrapper {
    const TWEET_MAX_LEN = 280;

    private $consumer_key;
    private $consumer_secret;
    private $access_token;
    private $access_token_secret;

    /**
     * Constructor function. Parses a config array for 'consumer_key',
     * 'consumer_secret', 'access_token', and 'access_token_secret' keys.
     * @access public
     * @param array $config The configuration array
     */
    public function __construct($config) {
      // Store the OAuth keys from the user's config
      $this->consumer_key        = $config['consumer_key'];
      $this->consumer_secret     = $config['consumer_secret'];
      $this->access_token        = $config['access_token'];
      $this->access_token_secret = $config['access_token_secret'];

      // Set up the OAuth library to talk to Twitter
      $this->twitter = new TwitterOAuth($this->consumer_key, $this->consumer_secret, $this->access_token, $this->access_token_secret);
    }

    /**
     * Sends one tweet to the account referred to by the OAuth credentials
     * stored in this object's instance.
     * @access public
     * @param object $tweet A tweet object to send to Twitter
     */
    public function sendTweet($tweet) {
      // Build the request array
      $request = array(
        'status'              => $tweet->text,
        'display_coordinates' => TRUE,
        'trim_user'           => TRUE
      );
      if ($tweet->reply_id) $request['in_reply_to_status_id'] = $tweet->reply_id;
      if ($tweet->place_id) $request['place_id'] = $tweet->place_id;
      if (floatval($tweet->latitude) && floatval($tweet->longitude)) {
        $request['lat'] = $tweet->latitude;
        $request['long'] = $tweet->longitude;
      }

      // Make the request and read the API response
      $response = $this->twitter->post('https://api.twitter.com/1.1/statuses/update.json', $request);

      if (empty($response)) {
        // Response was blank
        throw new TwitterException("Could not contact Twitter API.");
      }

      if (isset($response->error)) {
        // Response had an error indication
        throw new TwitterException("Twitter says: {$response->error}");

      } else if (!isset($response->created_at)) {
        // Response lacked any indication that the tweet was created
        $details = print_r($response, TRUE);
        throw new TwitterException("Could not create tweet. {$details}");
      }

      return $response->id_str;
    }

    /**
     * Deletes a specific tweet from the account referred to by the OAuth
     * credentials stored in this object's instance.
     * @access public
     * @param string $id The ID string of the tweet to delete
     * @return boolean TRUE if the request succeeded, FALSE otherwise
     */
    public function deleteTweet($id) {
      // Make the request and read the API response
      $response = $this->twitter->post("https://api.twitter.com/1.1/statuses/destroy/{$id}.json");
      return isset($response->id_str);
    }

    /**
     * Somewhat non-standard method to reconstruct most @replies, @mentions,
     * #hashtags and URLs that typically get mangled in the text conversion. The
     * original tweet text is scanned for these features, and if anything
     * similar is seen in the mangled text, they are space-padded to separate
     * them from their surrounding text and any special leading characters are
     * reattached. The original @reply recipient, if present, is always moved to
     * the front of the string so "in_reply_to_status_id" is not rejected.
     * @access public
     * @param string $oldText The text before mangling, used to find features
     * @param string $newText Mangled text. A repaired copy of this is returned.
     * @return string Basically $newText, with original features reconstructed.
     */
    public static function salvage($oldText, $newText) {
      $extractor = new Twitter_Extractor($oldText);

      // Completely strip out any @reply and #hashtag characters.
      $newText = str_ireplace(array('@', '#'), ' ', $newText);

      // See if the old text started with an @reply and try to keep it
      $reply = $extractor->extractRepliedUsernames();
      if ($reply) {
        // Remove the first occurrence of the name, and reinsert it at the front
        $newText = self::str_ireplace_once($reply, ' ', $newText);
        $newText = "$reply $newText";
      }

      // Re-add the "@" character to any surviving username from the old text
      foreach ($extractor->extractMentionedUsernames() as $name) {
        $newText = self::str_ireplace_once($name, " @{$name} ", $newText);
      }

      // Re-add the "#" character to any surviving hashtag from the old text
      foreach ($extractor->extractHashtags() as $hTag) {
        $newText = self::str_ireplace_once($hTag, " #{$hTag} ", $newText);
      }

      // Try to pad URLs to prevent them from fusing to surrounding text
      foreach ($extractor->extractURLs() as $url) {
        $newText = self::str_ireplace_once($url, " {$url} ", $newText);
      }

      // Collapse all multi-spaces to a single space; trim the ends
      $newText = preg_replace('/\s+/', ' ', $newText);
      $newText = trim($newText);

      // Neuter any SMS command sequences that are present in this text
      $newText = self::smsCommandEscape($newText);

      // Truncate the tweet if it is too long
      if (strlen($newText) > self::TWEET_MAX_LEN) {
        $newText = substr($newText, 0, self::TWEET_MAX_LEN - 3) . '...';
      }

      return $newText;
    }

    /**
     * Escapes certain string sequences that can be interpreted by Twitter as
     * being commands to perform undesired actions.
     * @access private
     * @param string $text The input text to sanitize
     * @return string The same text, with special SMS command sequences escaped
     */
    private static function smsCommandEscape($text) {
      // https://support.twitter.com/articles/14020-twitter-for-sms-basic-features
      if (preg_match('/^on$/i', $text)  //ON
       || preg_match('/^on\s+\w+$/i', $text)  //ON [name]
       || preg_match('/^off$/i', $text)  //OFF
       || preg_match('/^off\s+\w+$/i', $text)  //OFF [name]
       || preg_match('/^follow\s+\w+$/i', $text)  //FOLLOW [name]
       || preg_match('/^f\s+\w+$/i', $text)  //F [name]
       || preg_match('/^unfollow\s+\w+$/i', $text)  //UNFOLLOW [name]
       || preg_match('/^leave\s+\w+$/i', $text)  //LEAVE [name]
       || preg_match('/^l\s+\w+$/i', $text)  //L [name]
       || preg_match('/^block\s+\w+$/i', $text)  //BLOCK [name]
       || preg_match('/^blk\s+\w+$/i', $text)  //BLK [name]
       || preg_match('/^unblock\s+\w+$/i', $text)  //UNBLOCK [name]
       || preg_match('/^unblk\s+\w+$/i', $text)  //UNBLK [name]
       || preg_match('/^report\s+\w+$/i', $text)  //REPORT [name]
       || preg_match('/^rep\s+\w+$/i', $text)  //REP [name]
       || preg_match('/^stop$/i', $text)  //STOP
       || preg_match('/^quit$/i', $text)  //QUIT
       || preg_match('/^end$/i', $text)  //END
       || preg_match('/^cancel$/i', $text)  //CANCEL
       || preg_match('/^unsubscribe$/i', $text)  //UNSUBSCRIBE
       || preg_match('/^arret$/i', $text)  //ARRET
       || preg_match('/^d\s+/i', $text)  //D [...]
       || preg_match('/^m\s+/i', $text)  //M [...]
       || preg_match('/^retweet\s+\w+$/i', $text)  //RETWEET [name]
       || preg_match('/^rt\s+\w+$/i', $text)  //RT [name]
       || preg_match('/^set\s+/i', $text)  //SET [...]
       || preg_match('/^whois\s+\w+$/i', $text)  //WHOIS [name]
       || preg_match('/^w\s+\w+$/i', $text)  //W [name]
       || preg_match('/^get\s+\w+$/i', $text)  //GET [name]
       || preg_match('/^g\s+\w+$/i', $text)  //G [name]
       || preg_match('/^fav\s+\w+$/i', $text)  //FAV [name]
       || preg_match('/^fave\s+\w+$/i', $text)  //FAVE [name]
       || preg_match('/^favorite\s+\w+$/i', $text)  //FAVORITE [name]
       || preg_match('/^favourite\s+\w+$/i', $text)  //FAVOURITE [name]
       || preg_match('/^\*\w+$/i', $text)  //*[name]
       || preg_match('/^stats\s+\w+$/i', $text)  //STATS [name]
       || preg_match('/^suggest$/i', $text)  //SUGGEST
       || preg_match('/^sug$/i', $text)  //SUG
       || preg_match('/^s$/i', $text)  //S
       || preg_match('/^wtf$/i', $text)  //WTF
       || preg_match('/^help$/i', $text)  //HELP
       || preg_match('/^info$/i', $text)  //INFO
       || preg_match('/^aide$/i', $text)  //AIDE
      ) {
        $text = ". {$text}";
      }
      return $text;
    }

    /**
     * Custom variant of str_ireplace() which replaces the first instance of the
     * $search text and then stops.
     * @access private
     * @param string $search The text to search for
     * @param string $replace The text to replace with
     * @param string $subject The text to search/replace within
     * @return string First instance of $search replaced with $replace
     */
    private static function str_ireplace_once($search, $replace, $subject) {
      $pos = stripos($subject, $search);
      if ($pos !== FALSE) {
        return substr_replace($subject, $replace, $pos, strlen($search));
      }
      return $subject;
    }
  }

?>
