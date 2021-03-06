<?php

  /**
   * Twitter Exception Class. A generic extension of the Exception class which
   * is thrown by the TwitterWrapper class.
   * @author Scott Smitelli
   * @package twanslationparty
   */

  class TwitterException extends Exception {
    /**
     * Constructor function. Same as the constructor for Exception, except
     * $message is now a mandatory argument.
     * @param string $message The exception message
     * @param integer $code The exception code (optional)
     * @param Exception $previous The previous Exception (optional)
     */
    public function __construct($message, $code = 0, Exception $previous = null) {
      parent::__construct($message, $code, $previous);
    }
  }

?>
