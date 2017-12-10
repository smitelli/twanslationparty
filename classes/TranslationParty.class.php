<?php

  /**
   * Bare-bones PHP port of the core functionality from TranslationParty.com.
   * No code was copied from their site; only the basic theory of operation is
   * replicated in this class.
   * @author Scott Smitelli
   * @package twanslationparty
   */

  class TranslationParty {
    private $apiKey;
    private $languageFrom;
    private $languageTo;
    private $maxCycles;
    private $cycles = array();

    /**
     * Constructor function. Takes several keys from a configuration array and
     * uses them to populate the private members of this class.
     * @access public
     * @param array $config The configuration array
     */
    public function __construct($config) {
      // Store some private vars based on the user's config
      $this->apiKey       = $config['api_key'];
      $this->languageFrom = $config['language_from'];
      $this->languageTo   = $config['language_to'];
      $this->maxCycles    = $config['cycles'];
    }

    /**
     * This method corrupts an input string into a bastardized pseudo-English
     * which is often slightly funny. It does this in a loop until $maxCycles
     * iterations have completed, or until two consecutive iterations produce
     * the same exact corruption of the text.
     * @access public
     * @param string $text The input text to be corrupted
     * @return string The corrupted text
     */
    public function mangle($text) {
      $this->cycles = array();

      $previousText = '';
      for ($i = 0; $i < $this->maxCycles; $i++) {
        // Run a single translation cycle
        $text = $this->translateCycle($text);

        // If this cycle produced the same result as the last one, we're done
        if ($text == $previousText) break;

        $previousText = $text;
        $this->cycles[] = $text;
      }

      return $text;
    }

    /**
     * Returns an array containing every intermediate corruption that was
     * encountered during the most recent mangle() call.
     * @access public
     * @return array Several strings, with earlier corruptions in lower indexes
     */
    public function getCycles() {
      return $this->cycles;
    }

    /**
     * Corrupts text by translating it from the class instance's 'from' language
     * into the 'to' language, then immediately translating it back again.
     * @access private
     * @param string $text The text to corrupt
     * @return string The corrupted text
     */
    private function translateCycle($text) {
      // Translate to the 'to' language and then back to the 'from' language
      $text = $this->translateText($text, $this->languageFrom, $this->languageTo);
      $text = $this->translateText($text, $this->languageTo, $this->languageFrom);
      return $text;
    }

    /**
     * Translates a portion of text from the language specified by $from into
     * the language specified by $to. Uses the Microsoft Translator V2 API.
     * @access private
     * @param string $inText The text to translate
     * @param string $from Language code to translate from (ex: 'en')
     * @param string $to Language code to translate into (ex: 'jp')
     * @return string The translated text
     */
    private function translateText($inText, $from, $to) {
      // See http://msdn.microsoft.com/en-us/library/ff512421.aspx for docs
      $url = 'http://api.microsofttranslator.com/V2/Http.svc/Translate?' . http_build_query(array(
        'contentType' => 'text/plain',
        'text'        => $inText,
        'from'        => $from,
        'to'          => $to
      ));

      // Perform an authenticated GET request to the Microsoft Translator API
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Ocp-Apim-Subscription-Key: {$this->apiKey}"
      ));
      $responseBody = curl_exec($ch);

      // Parse the XML, extract the data from it
      $domDoc = new DOMDocument();
      $domDoc->loadXML($responseBody);
      $outText = $domDoc->getElementsByTagName('string')->item(0)->nodeValue;
      return html_entity_decode($outText, ENT_QUOTES, 'UTF-8');
    }
  }

?>
