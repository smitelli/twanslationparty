<?php

  /**
   * Twitter Wrapper Class. Performs a dead-simple interface to communicate with
   * the Twitter API given a set of OAuth keys.
   * @author Scott Smitelli
   * @package twanslationparty
   */

  class TwitterWrapper {
    const TWEET_MAX_LEN = 140;

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
      $twitter = new TwitterOAuth($this->consumer_key, $this->consumer_secret, $this->access_token, $this->access_token_secret);
      $response = $twitter->post('http://api.twitter.com/1/statuses/update.json', $request);

      if (empty($response)) {
        // Response was blank
        throw new TwitterException("Could not contact Twitter API.");
      }

      if (isset($response->error)) {
        // Response had an error indication
        throw new TwitterException("Twitter says: {$response->error}");

      } else if (!isset($response->created_at)) {
        // Response lacked any indication that the tweet was created
        throw new TwitterException("Could not create tweet.");
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
      $twitter = new TwitterOAuth($this->consumer_key, $this->consumer_secret, $this->access_token, $this->access_token_secret);
      $response = $twitter->post("https://api.twitter.com/1.1/statuses/destroy/{$id}.json");
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
     * @param string $newText The mangled text. A fixed version of is returned.
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

      // Truncate the tweet if it is too long
      if (strlen($newText) > self::TWEET_MAX_LEN) {
        $newText = substr($newText, 0, self::TWEET_MAX_LEN - 3) . '...';
      }

      return $newText;
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