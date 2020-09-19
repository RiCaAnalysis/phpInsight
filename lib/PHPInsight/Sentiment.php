<?php
namespace PHPInsight;

/*
  phpInsight is a Naive Bayes classifier to calculate sentiment. The program
  uses a database of words categorised as positive, negative or neutral

  Copyright (C) 2012  James Hennessey
  Class modifications and improvements by Ismayil Khayredinov (ismayil.khayredinov@gmail.com)

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>

 */

class Sentiment
{

    /**
     * Location of the dictionary files
     * @var str
     */
    private $dataFolder = '';

    /**
     * List of tokens to ignore
     * @var array
     */
    private $ignoreList = array();

    /**
     * List of words with negative prefixes, e.g. isn't, arent't
     * @var array
     */
    private $negPrefixList = array();

    /**
     * List of words thats supposed to split expression, e.g ':', ',', '.', ';'
     * @var array
     */
    private $splitWordsList = array();

    /**
     * Storage of cached dictionaries
     * @var array
     */
    private $dictionary = array();

    /**
     * Min length of a token for it to be taken into consideration
     * @var int
     */
    private $minTokenLength = 1;

    /**
     * Max length of a taken for it be taken into consideration
     * @var int
     */
    private $maxTokenLength = 15;

    /**
     * Classification of opinions
     * @var array
     */
    private $classes = array('pos', 'neg', 'neu');

    /**
     * Classes equivalent for inverse
     * @var array
     */
    private $inverseClasses = array(
        'pos' => 'neg',
        'neg' => 'pos',
        'neu' => 'ign',
    );

    /**
     * Token score per class
     * @var array
     */
    private $classTokCounts = array(
        'pos' => 0,
        'neg' => 0,
        'neu' => 0
    );

    /**
     * Analyzed text score per class
     * @var array
     */
    private $classDocCounts = array(
        'pos' => 0,
        'neg' => 0,
        'neu' => 0
    );

    /**
     * Number of tokens in a text
     * @var int
     */
    private $tokCount = 0;

    /**
     * Number of analyzed texts
     * @var int
     */
    private $docCount = 0;

    /**
     * Implication that the analyzed text has 1/3 chance of being in either of the 3 categories
     * @var array
     */
    private $prior = array(
        'pos' => 0.333333333333,
        'neg' => 0.333333333333,
        'neu' => 0.333333333334
    );

    /**
     * Class constructor
     * @param str $dataFolder base folder
     * Sets defaults and loads/caches dictionaries
     */
    public function __construct($dataFolder = false, $lang = 'en')
    {

        //set the base folder for the data models
        $this->setDataFolder($dataFolder, $lang);

        //load and cache directories, get ignore and prefix lists
        $this->loadDefaults();
    }

    /**
     * Search a token into the dictionary, supportingt wildcare
     *
     * @param str $token : The token to search for in dictionnary
     * @param mixed $class : Default = false -> search for all class, else, the class to search for ('neu', 'neg', 'pos')
     * @return mixed : false if not found, else, the word
     */
    public function searchTokenInDictionary($token, $class = false)
    {
        $words = [];
            
        //Get list of dictionary words
        if (!$class) {
            $words = array_keys($this->dictionary);
        } else {
            foreach ($this->dictionary as $word => $wordClasses) {
                if (isset($this->dictionary[$word][$class])) {
                    $words[] = $word;
                }
            }
        }

        //Try to match using str_pos and wildcare for each word of dictionary
        foreach ($words as $word) {

            //Search for a wildare
            $wildcare = false;
            if (mb_strpos($word, '*') !== false) {
                $word_escape = str_replace('*', '', $word);
                $wildcare = true;
            }

            //If wildcare, search for a string starting by
            if ($wildcare) {
                $stringPos = mb_strpos($token, $word_escape);
                if ($stringPos !== false && $stringPos == 0) {
                    return $word;
                }
            }

            //if no wildcare, search for exact string
            if ($word == $token) {
                return $word;
            }
        }

        return false;
    }

    
    /**
     * Search a token into the negPrefixList
     *
     * @param str $token
     * @return boolean : true if found, false else
     */
    public function searchTokenInNegPrefixList($token)
    {

        //Try to match using str_pos and wildcare for each prefix of the list
        foreach ($this->negPrefixList as $negPrefix) {

            //Search for wildcare
            $wildcare = false;
            if (mb_strpos($negPrefix, '*') !== false) {
                $negPrefixEscape = str_replace('*', '', $negPrefix);
                $wildcare = true;
            }

            //If wildcare, searching for a word starting by prefix
            if ($wildcare) {
                $strPos = mb_strpos($token, $negPrefixEscape);
                if ($strPos !== false && $strPos == 0) {
                    return true;
                }
            }

            //If no wildcare, searching for exact match
            if ($negPrefix == $token) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get scores for each class
     *
     * @param str $sentence Text to analyze
     * @return int Score
     */
    public function score($sentence)
    {
        //Tokenise Document
        $tokens = $this->_getTokens($sentence);
        // calculate the score in each category

        $total_score = 0;

        //Empty array for the scores for each of the possible categories
        $scores = array();

        //Loop to initialize scores[$class] for each $class
        foreach ($this->classes as $class) {
            $scores[$class] = 1;
        }

        //Loop through all of the different classes set in the $classes variable
        foreach ($this->classes as $class) {

            //For each of the individual words used loop through to see if they match anything in the $dictionary
            foreach ($tokens as $token_key => $token) {

                //If statement so to ignore tokens which are either too long or too short or in the $ignoreList
                if (strlen($token) < $this->minTokenLength || strlen($token) > $this->maxTokenLength || in_array($token, $this->ignoreList)) {
                    continue;
                }

                //Search for current token in dictionaries
                $token_found = $this->searchTokenInDictionary($token, $class);

                //If there is not for the current class, pass to next token
                if ($token_found === false || !isset($this->dictionary[$token_found][$class])) {
                    continue;
                } //Else, let's go for processing

                //Set count equal to it
                $count = $this->dictionary[$token_found][$class];

                //Else, we are going to check for prefix that should inverse meaning
                
                /*
                    Note : Here, we are using what i call "Forward-back journey", wich is a way to search before (and potentialy after, but, for now, just before) the current token, for finding a potential meaning modifier. This method take in consideration the "split words", and potentials meaningful words.
                */

                $found_negative_prefix = false;
                $continue_search_forward = true;
                $continue_search_backward = true;
                $i = 0;
                while (!$found_negative_prefix && ($continue_search_backward || $continue_search_forward)) {

                    //Go for previous word
                    $i++;

                    //Set backward and forward tokens, if they exists
                    $forward_token = isset($tokens[$token_key + $i]) ? $tokens[$token_key + $i] : false;
                    $backward_token = isset($tokens[$token_key - $i]) ? $tokens[$token_key - $i] : false;

                    //If we reach end of the text, or find a split word after current token, or another meaningful token, then, stop looking forward
                    if (!$forward_token || !$continue_search_forward || in_array($forward_token, $this->splitWordsList) || $this->searchTokenInDictionary($forward_token)) {
                        $continue_search_forward = false;
                    }
                    
                    //If we reach begenning of the text, or find a split word before current token, or another meaningful token, then stop looking backward
                    if (!$backward_token || !$continue_search_backward || in_array($backward_token, $this->splitWordsList) || $this->searchTokenInDictionary($backward_token)) {
                        $continue_search_backward = false;
                    }

                    //If we found a negative prefix in this part of the sentence, we can consider it as meaningful
            
                    if ($continue_search_forward && $this->searchTokenInNegPrefixList($forward_token)) {
                        $found_negative_prefix = true;
                    
                        //For forward token only, take count of potential interogation mark after.
                        //In case of, interrogation mark, dont apply negative prefix
                        if (isset($tokens[$token_key + $i + 1]) && $tokens[$token_key + $i + 1] == '?') {
                            $found_negative_prefix = false;
                        }
                    }
 
                    if ($continue_search_backward && $this->searchTokenInNegPrefixList($backward_token)) {
                        $found_negative_prefix = true;
                    }
                }

                //If we found a negative prefix for this token
                if ($found_negative_prefix) {
                    //If there is an inverse class for the current one, we improve his score instead
                    if (isset($scores[$this->inverseClasses[$class]])) {
                        $scores[$this->inverseClasses[$class]] *= ($count + 1);
                    } //Else, we simply ignore the token

                    continue;
                }

                //If we do not found a negative prefix, increment score as regular

                //Score[class] is calcumeted by $scores[class] x $count +1 divided by the $classTokCounts[class] + $tokCount
                $scores[$class] *= ($count + 1);
            }

            //Score for this class is the prior probability multiplyied by the score for this class
            $scores[$class] = $this->prior[$class] * $scores[$class];
        }

        //Makes the scores relative percents
        foreach ($this->classes as $class) {
            $total_score += $scores[$class];
        }

        foreach ($this->classes as $class) {
            $scores[$class] = round($scores[$class] / $total_score, 3);
        }

        //Sort array in reverse order
        arsort($scores);

        return $scores;
    }

    /**
     * Get the class of the text based on it's score
     *
     * @param str $sentence
     * @return str pos|neu|neg
     */
    public function categorise($sentence)
    {
        $scores = $this->score($sentence);

        //If no clear score, return 'neu'
        if ($scores[array_keys($scores)[0]] == $scores[array_keys($scores)[1]]) {
            return 'neu';
        }

        //Classification is the key to the scores array
        $classification = key($scores);

        return $classification;
    }

    /**
     * Load and cache dictionary
     *
     * @param str $class
     * @return boolean
     */
    public function setDictionary($class)
    {
        /**
         *  For some people this file extention causes some problems!
         */
        $fn = "{$this->dataFolder}data.{$class}.php";

        if (file_exists($fn)) {
            $temp = file_get_contents($fn);
            $words = unserialize($temp);
        } else {
            echo 'File does not exist: ' . $fn;
        }

        //Loop through all of the entries
        foreach ($words as $word) {
            $this->docCount++;
            $this->classDocCounts[$class]++;

            //Trim word
            $word = trim($word);

            //If this word isn't already in the dictionary with this class
            if (!isset($this->dictionary[$word][$class])) {

                //Add to this word to the dictionary and set counter value as one. This function ensures that if a word is in the text file more than once it still is only accounted for one in the array
                $this->dictionary[$word][$class] = 1;
            }//Close If statement

            $this->classTokCounts[$class]++;
            $this->tokCount++;
        }//Close while loop going through everyline in the text file

        return true;
    }

    /**
     * Set the base folder for loading data models
     * @param str  $dataFolder base folder
     * @param bool $loadDefaults true - load everything by default | false - just change the directory
     */
    public function setDataFolder($dataFolder = false, $lang = 'en', $loadDefaults = false)
    {
        //if $dataFolder not provided, load default, else set the provided one
        if ($dataFolder == false) {
            if (file_exists(__DIR__ . '/data/' . $lang .'/')) {
                $this->dataFolder = __DIR__ . '/data/' . $lang .'/';
            } else {
                echo 'Error: could not find the directory - '. __DIR__ . '/data/' . $lang .'/';
            }
        } else {
            if (file_exists($dataFolder)) {
                $this->dataFolder = $dataFolder;
            } else {
                echo 'Error: could not find the directory - '.$dataFolder;
            }
        }

        //load default directories, ignore and prefixe lists
        if ($loadDefaults !== false) {
            $this->loadDefaults();
        }
    }

    /**
     * Load and cache directories, get ignore and prefix lists
     */
    private function loadDefaults()
    {
        // Load and cache dictionaries
        foreach ($this->classes as $class) {
            if (!$this->setDictionary($class)) {
                echo "Error: Dictionary for class '$class' could not be loaded";
            }
        }

        if (!isset($this->dictionary) || empty($this->dictionary)) {
            echo 'Error: Dictionaries not set';
        }

        //Run function to get ignore list
        $this->ignoreList = $this->getList('ign');

        //If ingnoreList not get give error message
        if (!isset($this->ignoreList)) {
            echo 'Error: Ignore List not set';
        }

        //Get the list of negative prefixes
        $this->negPrefixList = $this->getList('prefix');

        //If neg prefix list not set give error
        if (!isset($this->negPrefixList)) {
            echo 'Error: Negative Prefix List not set';
        }

        //Get the list of split words
        $this->splitWordsList = $this->getList('split');

        //If split words list not set give error
        if (!isset($this->splitWordsList)) {
            echo 'Error: Split Word List not set';
        }
    }

    /**
     * Break text into tokens
     *
     * @param str $string	String being broken up
     * @return array An array of tokens
     */
    private function _getTokens($string)
    {

        // Replace line endings with spaces
        $string = str_replace("\r\n", " ", $string);
        
        //Clean the string so is free from accents
        $string = $this->_cleanString($string);

        //Make all texts lowercase as the database of words in in lowercase
        $string = strtolower($string);

        //Separe split word from real ones
        foreach ($this->splitWordsList as $key => $value) {
            $string = mb_ereg_replace('([a-zA-Z])(' . preg_quote($value) . ')( )?', '\\1 \\2\\3', $string);
        }

        //Break string into individual words using explode putting them into an array
        $matches = mb_split("( |')", $string);

        //Remove empty strings from $matches and reindex
        $matches = array_values(array_filter($matches, function ($value) {
            return !($value == "");
        }));

        //Return array with each individual token
        return $matches;
    }

    /**
     * Load and cache additional word lists
     *
     * @param str $type
     * @return array
     */
    public function getList($type)
    {
        //Set up empty word list array
        $wordList = array();

        $fn = "{$this->dataFolder}data.{$type}.php";
        ;
        if (file_exists($fn)) {
            $temp = file_get_contents($fn);
            $words = unserialize($temp);
        } else {
            return 'File does not exist: ' . $fn;
        }

        //Loop through results
        foreach ($words as $word) {
            //remove any slashes
            $word = stripcslashes($word);
            //Trim word
            $trimmed = trim($word);

            //Push results into $wordList array
            array_push($wordList, $trimmed);
        }
        //Return $wordList
        return $wordList;
    }

    /**
     * Function to clean a string so all characters with accents are turned into ASCII characters. EG: ‡ = a
     *
     * @param str $string
     * @return str
     */
    private function _cleanString($string)
    {
        $a = array('À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','ÿ','Ā','ā','Ă','ă','Ą','ą','Ć','ć','Ĉ','ĉ','Ċ','ċ','Č','č','Ď','ď','Đ','đ','Ē','ē','Ĕ','ĕ','Ė','ė','Ę','ę','Ě','ě','Ĝ','ĝ','Ğ','ğ','Ġ','ġ','Ģ','ģ','Ĥ','ĥ','Ħ','ħ','Ĩ','ĩ','Ī','ī','Ĭ','ĭ','Į','į','İ','ı','Ĳ','ĳ','Ĵ','ĵ','Ķ','ķ','Ĺ','ĺ','Ļ','ļ','Ľ','ľ','Ŀ','ŀ','Ł','ł','Ń','ń','Ņ','ņ','Ň','ň','ŉ','Ō','ō','Ŏ','ŏ','Ő','ő','Œ','œ','Ŕ','ŕ','Ŗ','ŗ','Ř','ř','Ś','ś','Ŝ','ŝ','Ş','ş','Š','š','Ţ','ţ','Ť','ť','Ŧ','ŧ','Ũ','ũ','Ū','ū','Ŭ','ŭ','Ů','ů','Ű','ű','Ų','ų','Ŵ','ŵ','Ŷ','ŷ','Ÿ','Ź','ź','Ż','ż','Ž','ž','ſ','ƒ','Ơ','ơ','Ư','ư','Ǎ','ǎ','Ǐ','ǐ','Ǒ','ǒ','Ǔ','ǔ','Ǖ','ǖ','Ǘ','ǘ','Ǚ','ǚ','Ǜ','ǜ','Ǻ','ǻ','Ǽ','ǽ','Ǿ','ǿ');
        $b = array('A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','s','a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','o','u','u','u','u','y','y','A','a','A','a','A','a','C','c','C','c','C','c','C','c','D','d','D','d','E','e','E','e','E','e','E','e','E','e','G','g','G','g','G','g','G','g','H','h','H','h','I','i','I','i','I','i','I','i','I','i','IJ','ij','J','j','K','k','L','l','L','l','L','l','L','l','l','l','N','n','N','n','N','n','n','O','o','O','o','O','o','OE','oe','R','r','R','r','R','r','S','s','S','s','S','s','S','s','T','t','T','t','T','t','U','u','U','u','U','u','U','u','U','u','U','u','W','w','Y','y','Y','Z','z','Z','z','Z','z','s','f','O','o','U','u','A','a','I','i','O','o','U','u','U','u','U','u','U','u','U','u','A','a','AE','ae','O','o');
            
        return mb_strtolower(str_replace($a, $b, $string));
    }
}
