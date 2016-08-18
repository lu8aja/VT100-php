<?php
/**
 * VT-100 Terminal Emulator for PHP
 *
 * @package   VT100
 * @author    Javier Albinarrate <javier@blendsystems.com>
 * @version   1.0 2009-04-17
 * @copyright Copyright (c) 2009, Aldo Javier Albinarrate
 * @todo      Implement scrolling
 * @todo      Validate argument for getScreenBlock and getScreenString
 * @todo      Implement the decoding of file streams (instead of socket connections), usefull for debug
 */

/**
 * VT-100 Terminal Emulator for PHP Class
 * This class implements RFCs related to Telnet & VT-52 & VT-100 specifications
 *
 *
 * @package VT100
 */
class VT100{
	var $version = 1.0;
	var $release = '2009-04-17';

	// Note: To disable timing steps (improve performance) replace all "/*T*/" with "//*T*/"

	/**
	 * Server Host or IP
	 * @var string
	 */
	var $host;

	/**
	 * Server Port
	 * @var integer
	 */
	var $port = 23;

	/**
	 * Virtual screen array
	 * @var array
	 */
	var $Screen = false;

	/**
	 * Number of rows
	 * @var integer
	 */
	var $rows;

	/**
	 * Number of columns
	 * @var integer
	 */
	var $cols;

	/**
	 * Active row
	 * @var integer
	 */
	var $row = 0;

	/**
	 * Active column
	 * @var integer
	 */
	var $col = 0;

	/**
	 * Socket buffer
	 * $enableBufferMain defaults to false, so buffer remains empty
	 * used for debug purposes only
	 * @var string
	 */
	var $buffer;

	/**
	 * Mid Buffer for listen() method
	 * $enableBufferListen defaults to true, so bufferListen contains the input stream for the listen() cycle
	 * @var string
	 */
	var $bufferListen;

	/**
	 * Raw buffer for Telnet command decode
	 * @var string
	 */
	var $bufferRawCmd;

	/**
	 * Decoded Buffer for Telnet command
	 * @var string
	 */
	var $bufferCmd;

	/**
	 * Debug Buffer with decoded telnet commands
	 * $enableBufferDebug defaults false
	 * @var string
	 */
	var $bufferDebug;

	/**
	 * Type of stream
	 * See load() for available types (default is SOCKET)
	 * @var resource
	 */
	var $streamType = 0;

	/**
	 * Filename for streamType = FILE
	 * @var string
	 */
	var $streamFilename = '';

	/**
	 * String for input when streamType = STRING
	 * @var string
	 */
	var $streamString = '';

	/**
	 * Character pointer when streamType = STRING
	 * @var string
	 */
	var $streamPtr = 0;

	/**
	 * Resource for the socket/file
	 * @var resource
	 */
	var $streamHandle;

	/**
	 * Socket error number upon connect
	 * @var integer
	 */
	var $socketErrorNum;

	/**
	 * Socket error description upon connect
	 * @var string
	 */
	var $socketErrorMsg;

	/**
	 * Socket connection timeout in seconds
	 * @var integer
	 */
	var $socketTimeout = 10;

	/**
	 * Blocking mode for the socket
	 * It must be set to true for using timing conditions
	 * @var boolean
	 */
	var $socketBlocking = true;

	/**
	 * Debug level (bitmap)
	 * See constants VT100_DEBUG_* in the load() method
	 * @var integer
	 */
	var $debug = 0;

	/**
	 * Debug timing array
	 * If debug has VT100_DEBUG_TIMING enabled, then listen() will load the timing points into this array
	 * @var array
	 */
	var $DebugTiming;

	/**
	 * Negotiate telnet commands (AutoAnswers)
	 * If enabled, the emulator will automatically answer the telnet options sent by the server
	 * @var boolean
	 */
	var $enableTelnetNegotiate = true;

	/**
	 * Fill in the debug stream buffer
	 * If enabled, the emulator will continuously fill in the this->buffer property
	 * @var boolean
	 */
	var $enableBufferMain = false;

	/**
	 * Fill in the intermediate debug bufferListen
	 * If enabled, the emulator will fill in the this->bufferListen property upon each listen() cycle
	 * This is required if you use VT100_COND_REBUF conditions
	 * @var boolean
	 */
	var $enableBufferListen = true;

	/**
	 * Fill in the debug buffer
	 * If enabled, the emulator will fill in the this->bufferDebug property, this buffer is used for debug only,
	 * and it has escape sequences and telnet options translated into something readable
	 * This is required if you use VT100_COND_REBUF conditions
	 * @var boolean
	 */
	var $enableBufferDebug = false;

	/**
	 * Telnet Status Internal Flag
	 * Holds the status of the telnet command decode sequence
	 *
	 * ReadOnly
	 * @var integer
	 */
	var $telnetStatus = 0;

	/**
	 * Terminal Type declared to server
	 * This is used during the Telnet negotiation
	 *
	 * @var integer
	 */
	var $telnetTermType = 'VT100';


	/**
	 * Decode console escape secuences
	 * Actually if this is disabled, then you probably shouldn't be using this emulator at all
	 * @var boolean
	 */
	var $enableConsoleDecode = true;

	/**
	 * Escape Status Internal Flag
	 * Holds the status of the console escape secuence
	 * 0=Current chr is Data, 1=Current chr is escape secuence
	 *
	 * ReadOnly
	 * @var integer
	 */
	var $consoleStatus = 0;

	/**
	 * Current/Last Console Escape secuence
	 * ReadOnly
	 * @var string
	 */
	var $escapeSequence = '';

	/**
	 * PHP Line of the console command that closed the escape secuence
	 * Debug info only
	 * ReadOnly
	 * @var integer
	 */
	var $consoleLine = 0;

	/**
	 * Descriptive name of the console command that closed the escape secuence
	 * Debug info only
	 * ReadOnly
	 * @var string
	 */
	var $consoleDesc = '';

	/**
	 * Text style (Graphic Rendition) attribute of the current position
	 *
	 * @var integer
	 */
	var $consoleFont = 0;

	/**
	 * Current charset for the console
	 * See load() to view available options
	 * @var integer
	 */
	var $consoleCharset     = 0;

	/**
	 * Charset to be when in SI
	 * @var integer
	 */
	var $consoleShiftIn     = 0;

	/**
	 * Charset to be when in SO
	 * @var integer
	 */
	var $consoleShiftOut    = 0;

	/**
	 * Console Tab size
	 *
	 * @var integer
	 */
	var $consoleTabSize = 8;

	/**
	 * Text Wrap enabled
	 *
	 * @var boolean
	 */
	var $consoleWrap = true;


	/**
	 * Array that holds the Telnet Commands (NAME=>BINARY)
	 * ReadOnly
	 * @var array
	 */
	var $MapTelnetCommands = array();

	/**
	 * Array that holds the Telnet Options (NAME=>BINARY)
	 * ReadOnly
	 * @var array
	 */
	var $MapTelnetOptions = array();

	/**
	 * Array that holds the Misc Telnet Codes (NAME=>BINARY)
	 * ReadOnly
	 * @var array
	 */
	var $MapMisc = array();

	/**
	 * Array that holds the Character and Console Escape info (NAME=>BINARY)
	 * ReadOnly
	 * @var array
	 */

	var $MapConsole = array();
	/**
	 * Array that holds the Reverse of MapTelnetCommands (BINARY=>NAME)
	 * Used for decode procedures
	 *
	 * ReadOnly
	 * @var array
	 */

	var $MapTelnetCommandsRev = array();
	/**
	 * Array that holds the Reverse (BINARY=>NAME) of MapTelnetOptions used for decode procedures
	 *
	 * ReadOnly
	 * @var array
	 */

	var $MapTelnetOptionsRev = array();

// PROPERTIES: Conditions
//***********************

	/**
	 * Conditions to break the while loop in listen function
	 *
	 * @var array
	 */
	var $Conditions;

	/**
	 * Number of iterations between condition checking
	 * For performance reasons it may be desiderable to check conditions only once every N loop iterations
	 * but beware of the type of conditions you use, because some of them require to be checked in every listen() cycle
	 *
	 * @var integer
	 */
	var $conditionsInterval = 1;

	/**
	 * Internal flag containing the type of the condition that broke the while loop in listen()
	 * ReadOnly
	 * @var integer
	 */
	var $flag;

	/**
	 * Internal flag containing the number of the condition that broke the while loop in listen()
	 * ReadOnly
	 * @var integer
	 */
	var $flagCondition;

	/**
	 * Current number of while iterations during the listen()
	 * ReadOnly
	 * @var integer
	 */
	var $count;

	/**
	 * Current number or processed bytes during the listen()
	 * ReadOnly
	 * @var integer
	 */
	var $bytes;

// PROPERTIES: Timing
//*******************

	/**
	 * Microseconds to sleep when no char is received in listen() loop
	 * Analyze very well this value as it seriously affects performance, specially in heavy loaded systems
	 * @var integer
	 */
	var $usleep      = 500;

	/**
	 * Idle time in seconds since the last received character
	 * @var integer
	 */
	var $timeIdle = 0;


	/**
	 * Timestamp when the listen() call started
	 * Used to calculate total runing listen() time
	 * @var integer
	 */
	var $timeStart = 0;

	/**
	 * Time in seconds the listen() has been running
	 * @var integer
	 */
	var $time;

	/**
	 * Time in float seconds (from microtime) the listen() run
	 * @var integer
	 */
	var $timeMicro;

	/**
	 * Microtime timestamp when listen() started
	 * (unused)
	 * @var integer
	 */
	var $timeListenStart;

	/**
	 * Microtime timestamp when listen() ended
	 * @var integer
	 */
	var $timeListenEnd;

// PROPERTIES: SCREEN
//*******************

	/**
	 * Screen objects
	 * This array holds the extracted field values
	 * @var array
	 */
	var $ScreenObjects = array();

	/**
	 * Screen definitions
	 * This array holds the definitions of the screen fields, by coords
	 *
	 * @var array
	 */
	var $ScreenDefinition = array();

// PROPERTIES: DEBUG
//******************

	/**
	 * Debug Steps
	 * This is used by the ->showDebugStep() method
	 *
	 * @var integer
	 */
	var $debugSteps = 0;

	/**
	 * Step Number
	 * This is used by the ->showDebugStep() method
	 *
	 * @var integer
	 */
	var $stepNum = 0;


	/**
	 * Step description
	 * This is used by the ->showDebugStep() method
	 *
	 * @var string
	 */
	var $stepDesc = 0;

	/**
	 * The step dump of the buffer should be in hex
	 * @var boolean
	 */
	var $stepDumpHex = false;

/***************************************************************************************************************/
// METHODS
/***************************************************************************************************************/


    /**
     * Constructor (PHP4 style)
     * Executed the load() method
     */
	function VT100(){
		$this->load();
	}

    /**
     * Disconnect the socket
     */
	function disconnect(){
		if ($this->streamType == VT100_STREAM_SOCKET && $this->streamHandle){
			return fclose($this->streamHandle);
		}
		return false;
	}

    /**
     * Connecto to server
	 * @param string $host Server IP or hostname (optional, false by default)
	 * @param integer $port Server port (optional, false by default)
	 * @param boolean $bSocketBlocking Socket blocking mode (optional, '' by default)
	 * @param integer $nSocketTimeout Connection timeout in seconds (optional, false by default)
	 * @return resource Socket resource
     */
	function connect($host = false, $port = false, $bSocketBlocking = '', $nSocketTimeout = false){
		if ($host !== false){
			$this->host = $host;
		}
		if ($port !== false){
			$this->port = $port;
		}
		if ($bSocketBlocking !== ''){
			$this->socketBlocking = $bSocketBlocking;
		}
		if ($nSocketTimeout !== false){
			$this->socketTimeout = $nSocketTimeout;
		}

		if (!is_array($this->Screen)){
			$this->createScreen();
		}

		$this->streamType = VT100_STREAM_SOCKET;

		// Execute the connection!
		$this->streamHandle = fsockopen($this->host, $this->port, $this->socketErrorNum, $this->socketErrorMsg, $this->socketTimeout);

		// Set the socket options
		if ($this->streamHandle){
			stream_set_blocking($this->streamHandle, $this->socketBlocking);
			stream_set_timeout($this->streamHandle, 1);
		}
		return $this->streamHandle ;
	}

    /**
     * Send a string to the Server
     * The string can have embedded Telnet commands or Console escape sequences in the form <SEQUENCE>
     * See the supported commands in the method load()
     *
	 * @param string $sText String to send
	 * @param boolean $bEncode Should we encode the tags into their binary values? (Optional, false by default)
	 * @return integer Number of bytes sent
     */
	function send($sText, $bEncode = false){
		if ($this->streamType != VT100_STREAM_SOCKET){
			return false;
		}
		if ($bEncode){
			$sText = $this->encode($sText);
		}
		return fwrite($this->streamHandle, $sText, strlen($sText));
	}

    /**
     * Listen to server, optionally sending a string first
     * The string to be sent can have embedded Telnet commands or Console escape sequences in the form <SEQUENCE>
     * See the supported commands in the method load()
     * This method will enter a loop, which will only exit if a condition from ->Conditions is met
     *
	 * @param string $sText String to send (Optional, false by default)
	 * @param boolean $bEncode Should we encode the tags into their binary values? (Optional, false by default)
	 * @return integer Number of bytes sent
     */
	function listen($sText = false, $bEncode = false){

		//*T*/if($this->debug & 0x08){ echo "-- TIMING STARTED -------------------------------------------\n";}

		// Lets initiate properties
		$this->timeListenStart = $this->getTimestamp();
		$this->timeStart = time();
		$this->time  = 0;
		$this->flag  = 0;
		$this->flagCondition = false;
		$this->count = 0;
		$this->bytes = 0;
		$this->timeIdle = 0;
		$this->bufferListen    = '';

		// Encode the string if needed
		if ($bEncode){
			$sText = $this->encode($sText);
		}
		// Send the string if needed
		if ($sText!== false && $this->streamType == VT100_STREAM_SOCKET){
			fwrite($this->streamHandle, $sText, strlen($sText));
		}

		// MAIN LISTEN LOOP
		while (!$this->flag) {
			//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('START',__LINE__,$this->getTimestamp());}
			//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('MTIME',__LINE__,$this->getTimestamp());}


			// Get a character from the stream
			if ($this->streamType == VT100_STREAM_SOCKET){
				$sChar = fgetc($this->streamHandle);
			}
			elseif ($this->streamType == VT100_STREAM_FILE){
				$sChar = fgetc($this->streamHandle);
			}
			elseif($this->streamType == VT100_STREAM_STRING){
				if ($this->streamPtr >= strlen($this->streamString)){
					$sChar = false;
				}
				else{
					$sChar = $this->streamString[$this->streamPtr];
					$this->streamPtr++;
				}
			}
			//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('FGETC',__LINE__,$this->getTimestamp());}

			if ($sChar === false){
				usleep($this->usleep);
			}
			else{
				//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('CHR',__LINE__,$this->getTimestamp());}
				$this->timeIdle = time() - $this->timeStart;
				if ($this->enableBufferMain)   $this->buffer       .= $sChar;
				if ($this->enableBufferListen) $this->bufferListen .= $sChar;

				//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('INIT',__LINE__,$this->getTimestamp());}

				/* START Process Telnet Commands */
				if ($this->telnetStatus == 0 && $sChar == $this->MapTelnetCommands['IAC']){
					// 0->1 Telnet Escape, command start
					$this->telnetStatus = 1;       // Expecting Command Byte
					$this->bufferRawCmd = $sChar;  //Clear and assign buffer cmd
					$this->bufferCmd    = '<IAC>';
					if ($this->enableBufferDebug) $this->bufferDebug .= '<IAC>';
				}

				elseif ($this->telnetStatus >  0){

					// 1 Expecting Command Byte
					if ($this->telnetStatus == 1){

						// Chr 255 was escaped (<IAC><IAC>)
						if ($sChar == $this->MapTelnetCommands['IAC']){
							$this->telnetStatus = 0;
							if ($this->enableBufferDebug) $this->bufferDebug .= '<IAC>';
							$this->bufferCmd   .= '<IAC>';
							if ($this->consoleStatus == 0){
								$this->Screen[$this->row][$this->col] = $sChar;
								$this->col++;
								if ($this->col >= $this->cols){
									$this->row++;
									$this->col = 0;
								}
								if ($this->row >= $this->rows){
									$this->row = $this->rows - 1;
								}
							}
							else{
								$this->escapeSequence .= $sChar;
							}
						}

						// Subnegociation End
						elseif ($sChar == $this->MapTelnetCommands['SE']){
							$this->telnetStatus = 3; // Expecting Option Byte for negociation
							if ($this->enableBufferDebug) $this->bufferDebug .= '<SE>';
							$this->bufferCmd   .= '<SE>';
						}

						// Subnegociation Start
						elseif ($sChar == $this->MapTelnetCommands['SB']){
							$this->telnetStatus = 3; // Expecting Option Byte for negociation
							if ($this->enableBufferDebug) $this->bufferDebug .= '<SB>';
							$this->bufferCmd   .= '<SB>';
						}

						// Other command, in listed commands
						elseif (isset($this->MapTelnetCommandsRev[$sChar])){
							if ($this->enableBufferDebug) $this->bufferDebug .= '<'.$this->MapTelnetCommandsRev[$sChar].'>';
							$this->bufferCmd   .= '<'.$this->MapTelnetCommandsRev[$sChar].'>';
							$this->telnetStatus = 2; // Expecting Option Byte
						}

						// Unknown command
						else {
							if ($this->enableBufferDebug) $this->bufferDebug .= '<'.ord($sChar).'>';
							$this->bufferCmd   .= '<'.ord($sChar).'>';
							$this->telnetStatus = 2;  // Expecting Option Byte
						}

						$this->bufferRawCmd .= $sChar;
					}

					// 2 Expecting Option Byte
					elseif ($this->telnetStatus == 2){

						// Known Option
						if (isset($this->MapTelnetOptionsRev[$sChar])){
							if ($this->enableBufferDebug) $this->bufferDebug .= '<OPT_'.$this->MapTelnetOptionsRev[$sChar].'>';
							$this->bufferCmd   .= '<OPT_'.$this->MapTelnetOptionsRev[$sChar].'>';
							$this->telnetStatus = 0;
						}

						// Unknown Option
						else {
							if ($this->enableBufferDebug) $this->bufferDebug .= '<OPT_'.ord($sChar).'>';
							$this->bufferCmd   .= '<OPT_'.ord($sChar).'>';
							$this->telnetStatus = 0;  // End of Command
						}

						$this->bufferRawCmd .= $sChar;
					}

					// 3 Expecting Option for Subnegociation
					elseif ($this->telnetStatus == 3){
						if ($sChar == $this->MapTelnetCommands['SE']){
							$this->telnetStatus = 0;
							if ($this->enableBufferDebug) $this->bufferDebug .= '<SE>';
							$this->bufferCmd   .= '<SE>';
						}
						elseif ($sChar == $this->MapTelnetCommands['IAC']){
							$this->telnetStatus = 3;
							if ($this->enableBufferDebug) $this->bufferDebug .= '<IAC>';
							$this->bufferCmd   .= '<IAC>';
						}
						elseif ($sChar == $this->MapMisc['IS']){
							$this->telnetStatus = 4;
							if ($this->enableBufferDebug) $this->bufferDebug .= '<IS>';
							$this->bufferCmd   .= '<IS>';
						}
						elseif ($sChar == $this->MapMisc['SEND']){
							$this->telnetStatus = 4;
							if ($this->enableBufferDebug) $this->bufferDebug .= '<SEND>';
							$this->bufferCmd   .= '<SEND>';
						}
						elseif (isset($this->MapTelnetOptionsRev[$sChar])){
							// Known Option
							if ($this->enableBufferDebug) $this->bufferDebug .= '<OPT_'.$this->MapTelnetOptionsRev[$sChar].'>';
							$this->bufferCmd   .= '<OPT_'.$this->MapTelnetOptionsRev[$sChar].'>';
							$this->telnetStatus = 3;
						}
						else {
							// Unknown Option
							if ($this->enableBufferDebug) $this->bufferDebug .= '<OPT_'.ord($sChar).'>';
							$this->bufferCmd   .= '<OPT_'.ord($sChar).'>';
							$this->telnetStatus = 3;
						}
						$this->bufferRawCmd .= $sChar;
					}

					// 4 Expecting subnegociation data
					elseif ($this->telnetStatus == 4){
						if     ($sChar == $this->MapTelnetCommands['SE']){
							$this->telnetStatus = 0;
							if ($this->enableBufferDebug) $this->bufferDebug .= '<SE>';
							$this->bufferCmd   .= '<SE>';
						}
						elseif ($sChar == $this->MapTelnetCommands['IAC']){
							$this->telnetStatus = 4;
							if ($this->enableBufferDebug) $this->bufferDebug .= '<IAC>';
							$this->bufferCmd   .= '<IAC>';
						}
						elseif ($sChar == $this->MapMisc['IS']){
							$this->telnetStatus = 4;
							if ($this->enableBufferDebug) $this->bufferDebug .= '<IS>';
							$this->bufferCmd   .= '<IS>';
						}
						elseif ($sChar == $this->MapMisc['SEND']){
							$this->telnetStatus = 4;
							if ($this->enableBufferDebug) $this->bufferDebug .= '<SEND>';
							$this->bufferCmd   .= '<SEND>';
						}
						else{
							if ($this->enableBufferDebug) $this->bufferDebug .= $sChar;
							$this->bufferCmd   .= $sChar;
						}
						$this->bufferRawCmd .= $sChar;
					}
					//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('TELNETEND',__LINE__,$this->getTimestamp());}

					// End Telnet command decode, now reply.
					if ($this->telnetStatus == 0 && $this->enableTelnetNegotiate){
						if ($this->debug && $this->debug & 0x10) echo 'S:'.htmlentities($this->bufferCmd."\n");

						$sAnswer = false;

						switch ($this->bufferCmd){
							case '<IAC><DO><OPT_TERMTYPE>':
								$sAnswer = '<IAC><WILL><OPT_TERMTYPE><IAC><SB><OPT_TERMTYPE><IS>'.$this->telnetTermType.'<IAC><SE>';
								break;
							case '<IAC><DO><OPT_NAWS>':
								$sAnswer = '<IAC><WILL><OPT_NAWS><IAC><SB><OPT_NAWS><NUL>'.chr($this->cols).'<NUL>'.chr($this->rows).'<IAC><SE>';
								break;
							case '<IAC><WILL><OPT_STATUS>':
								$sAnswer = '<IAC><DONT><OPT_STATUS>';
								break;
							case '<IAC><WILL><OPT_SGA>':
								$sAnswer = '<IAC><DO><OPT_SGA>';
								break;
							case '<IAC><DONT><OPT_ECHO>':
								$sAnswer = '<IAC><WONT><OPT_ECHO>';
								break;
							case '<IAC><WILL><OPT_ECHO>':
								$sAnswer = '<IAC><DO><OPT_ECHO>';
								break;
							case '<IAC><DO><OPT_NEWENV>':
								$sAnswer = '<IAC><WONT><OPT_NEWENV>';
								break;
							case '<IAC><DONT><OPT_TSPEED>':
								$sAnswer = '<IAC><WONT><OPT_TSPEED>';
								break;
							case '<IAC><SB><OPT_TERMTYPE><SEND><IAC><SE>':
								$sAnswer = '<IAC><SB><OPT_TERMTYPE><IS>'.$this->telnetTermType.'<IAC><SE>';
								break;
							case '<IAC><SB><OPT_NAWS><SEND><IAC><SE>':
								$sAnswer = '<IAC><SB><OPT_NAWS><NUL>'.chr($this->cols).'<NUL>'.chr($this->rows).'<IAC><SE>';
								break;
							default:
								// Unsupported options
								if ($this->bufferRawCmd[0] == $this->MapTelnetCommands['IAC'] && $this->bufferRawCmd[1] == $this->MapTelnetCommands['DO']){
									$sAnswer = '<IAC><WONT>'.$this->bufferRawCmd[2];
								}
								elseif ($this->bufferRawCmd[0] == $this->MapTelnetCommands['IAC'] && $this->bufferRawCmd[1] == $this->MapTelnetCommands['DONT']){
									$sAnswer = '<IAC><WONT>'.$this->bufferRawCmd[2];
								}
						}

						if ($sAnswer && $this->streamType == VT100_STREAM_SOCKET){
							if ($this->debug && $this->debug & 0x20) echo 'C:'.htmlentities($sAnswer)."\n";
							fwrite($this->streamHandle, $this->encode($sAnswer));
						}
						//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('ANSWEREND',__LINE__,$this->getTimestamp());}
					}
				}
				/* END Process Telnet Commands */


				// TELNET STATUS 0: Telnet data (not command)
				elseif ($this->telnetStatus == 0){

					if ($this->enableBufferDebug) $this->bufferDebug .= $sChar;

					/* START Process Console Commands */

					// CONSOLE STATUS 0: Console data (not escaped)
					if($this->consoleStatus == 0 && $this->enableConsoleDecode){
						$this->consoleDesc    = '';
                        $this->consoleLine    = 0;
                        $this->escapeSequence = '';


						/* Data (Not escaped by console) */

						// NULL
						if     ($sChar == $this->MapConsole['NUL']) {
							// Do nothing
						}
						// BELL
						elseif ($sChar == $this->MapConsole['BELL']) {
							// Do not do anything, as it is an alarm and must not be inserted into the screen
							// We should have a bell handler here perhaps?
						}
						// TAB
						elseif ($sChar == $this->MapConsole['TAB']) {
							// Mnually move the cursor to the next TAB
							$this->col = $this->col - ($this->col % $this->consoleTabSize) + $this->consoleTabSize;
							if ($this->col >= $this->cols){ $this->col = $this->cols - 1;}
						}
						// SHIFT IN: http://en.wikipedia.org/wiki/C0_and_C1_control_codes and http://en.wikipedia.org/wiki/Shift_Out_and_Shift_In_characters
						elseif (ord($sChar) == 0x0f) {
							$this->consoleCharset = $this->consoleShiftIn;
						}
						// SHIFT OUT: http://en.wikipedia.org/wiki/C0_and_C1_control_codes and http://en.wikipedia.org/wiki/Shift_Out_and_Shift_In_characters
						elseif (ord($sChar) == 0x0e) {
							$this->consoleCharset = $this->consoleShiftOut;
						}
						// BS: BackSpace
						elseif ($sChar == $this->MapConsole['BS']) {
							$this->col--;
							if ($this->col < 0){
								$this->row--;
								$this->col = $this->cols - 1;
							}
							$this->Screen[$this->row][$this->col] = ' ';
						}
						// LF: Line Feed
						elseif ($sChar == $this->MapConsole['LF']) {
							$this->row++;
						}
						// CR: Carriage return
						elseif ($sChar == $this->MapConsole['CR']) {
							$this->col = 0;
						}
						elseif ($sChar == $this->MapConsole['ESC']){
							$this->escapeSequence = '';
							$this->consoleStatus  = 1;
							$this->consoleLine    = 0;
						}
						else {
	/*
							// Mark Bold Spaces (Experimental)
							if ($this->consoleFont == 1 && $sChar == ' ') $sChar = '#';
							if ($this->consoleFont == 4 && $sChar == ' ') $sChar = '_';
	*/

							if ($this->consoleCharset == VT100_CHARSET_ASCII){
								$this->Screen[$this->row][$this->col] = $sChar;
							}
							else{
								$this->Screen[$this->row][$this->col] = '�';
							}

							// Move the cursor
							$this->col++;
							if ($this->consoleWrap){
								if ($this->col >= $this->cols){ $this->row++; $this->col = 0;}
								if ($this->row >= $this->rows){ $this->row = $this->rows - 1;}
							}
							else{
								if ($this->col >= $this->cols){
									$this->col = $this->cols - 1;
								}
							}

							/* IMPORTANT: No scrolling supported yet */
						}
					}

					// CONSOLE STATUS 1: Escaped secuence, expecting command
					elseif($this->consoleStatus == 1){

						$this->escapeSequence .= $sChar;

						if (is_numeric($sChar) || $sChar == ';' || $sChar == '[' ||  $sChar == '?' || $sChar == ']'){
							// DO NOTHING
						}
						else{
							$this->consoleStatus=0;

							// START Processing escape sequence
							switch ($sChar){

								// CUP  Cursor Position & VT52 Cursor HOME
								case 'H':
									if ($this->escapeSequence == 'H'){
										//VT52 Cursor HOME
										$this->col = 0;
										$this->row = 0;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'Home';
									}
									elseif ($this->escapeSequence == '[H'){
										// CUP  Cursor Position (Default position)
										$this->col = 0;
										$this->row = 0;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'Home';
									}
									elseif (preg_match('/^\[(\d+);(\d+)H$/',$this->escapeSequence,$aMatches)){
										// CUP  Cursor Position
										$this->row = (int) $aMatches[1] -1;
										$this->col = (int) $aMatches[2] -1;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'Home';
									}
								break;

								// CUP  Cursor Position
								case 'f':
									if (preg_match('/^\[(\d+);(\d+)f$/',$this->escapeSequence,$aMatches)){
										$this->row = (int) $aMatches[1] -1;
										$this->col = (int) $aMatches[2] -1;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'CursorPosition';
									}
								break;

								//SGR  Select Graphic Rendition (Text style)
								case 'm':
									if (preg_match('/^\[(\d+)?m$/',$this->escapeSequence,$aMatches)){
										$this->consoleFont = @$aMatches[1];
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'SGR';
									}
									elseif (preg_match('/^\[(\d+)(;(\d+))+m$/',$this->escapeSequence,$aMatches)){
										$this->consoleFont    = $aMatches[(count($aMatches)-1)]; // Currently not supported multi styles
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'SelectGraphicRendition';
									}
								break;

								//EL � Erase In Line http://www.vt100.net/docs/vt100-ug/chapter3.html#EL
								case 'K':
									if (preg_match('/^\[(\d?)K$/',$this->escapeSequence,$aMatches)){
										if     (!isset($aMatches[1])){
											$this->clearScreen($this->row, $this->col, $this->row, ($this->cols -1));
										}
										elseif ($aMatches[1] == 0)   {
											$this->clearScreen($this->row, $this->col, $this->row, ($this->cols -1));
										}
										elseif ($aMatches[1] == 1)   {
											$this->clearScreen($this->row, 0, $this->row, $this->col);
										}
										elseif ($aMatches[1] == 2)   {
											$this->clearScreen($this->row, 0, $this->row, ($this->cols -1));
										}
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'EraseLine';
									}
								break;

								//ED � Erase In Display http://www.vt100.net/docs/vt100-ug/chapter3.html#ED
								case 'J':
									if (preg_match('/^\[(\d?)J$/',$this->escapeSequence,$aMatches)){
										if     (!isset($aMatches[1])){
											$this->clearScreen($this->row, $this->col, ($this->rows -1), ($this->cols -1));
										}
										elseif ($aMatches[1] == 0){
											$this->clearScreen($this->row, $this->col, ($this->rows -1), ($this->cols -1));
										}
										elseif ($aMatches[1] == 1){
											$this->clearScreen(0, 0, $this->row, $this->col);
										}
										elseif ($aMatches[1] == 2){
											$this->clearScreen(0, 0, ($this->rows -1), ($this->cols -1));
										}
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'EraseDisplay';
									}
									break;

								// VT52 & ANSI Cursor UP
								case 'A':
									if ($this->escapeSequence == 'A'){
										// VT52 Cursor UP
										$this->row--;
										if ($this->row < 0) $this->row = 0;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'Up';
									}
									elseif (preg_match('/^\[(\d*)A$/',$this->escapeSequence,$aMatches)){
										// ANSI Cursor UP
										if   (!isset($aMatches[1])){ $this->row--;}
										else                  { $this->row = $this->row - $aMatches[1];}
										if ($this->row < 0) $this->row = 0;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'Up';
									}

								break;

								// VT52 & ANSI Cursor DOWN
								case 'B':
									if ($this->escapeSequence == 'B'){
										// VT52 Cursor DOWN
										$this->row++;
										if ($this->row >= $this->rows) $this->row = $this->rows -1;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'Down';
									}
									elseif (preg_match('/^\[(\d*)B$/',$this->escapeSequence,$aMatches)){
										// ANSI Cursor DOWN
										if   (!isset($aMatches[1])){ $this->row++;}
										else                  { $this->row = $this->row + $aMatches[1];}
										if ($this->row >= $this->rows) $this->row = $this->rows -1;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'Down';
									}
								break;

								// VT52 & ANSI Cursor RIGHT
								case 'C':
									if ($this->escapeSequence == 'C'){
										// VT52 Cursor RIGHT
										$this->col++;
										if ($this->col >= $this->cols) $this->col = $this->cols -1;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'Right';
									}
									elseif (preg_match('/^\[(\d*)C$/',$this->escapeSequence,$aMatches)){
										// CUF � Cursor Forward � Host to VT100 and VT100 to Host
										// http://www.vt100.net/docs/vt100-ug/chapter3.html#CUF
										if   (!isset($aMatches[1]) || $aMatches[1] == '0'){
											$this->col++;
										}
										else {
											$this->col = $this->col + $aMatches[1];
										}
										if ($this->col >= $this->cols) $this->col = $this->cols -1;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'Right';
									}
								break;

								// VT52 & ANSI Cursor LEFT
								case 'D':
									if ($this->escapeSequence == 'D'){
										// VT52 Cursor LEFT
										$this->col--;
										if ($this->col < 0) $this->col = 0;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'Left';
									}
									if (preg_match('/^\[(\d*)D$/',$this->escapeSequence,$aMatches)){
										// ANSI Cursor LEFT
										if   (!isset($aMatches[1])){ $this->col--;}
										else                  { $this->col = $this->col - $aMatches[1];}
										if ($this->col < 0) $this->col = 0;
										// Note, we are not updating row if < 0, this might cause troubles
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'Left';
									}
								break;

								// VT52 Cursor Reverse Line Feed
								case 'I':
									if ($this->escapeSequence == 'I'){
										$this->row--;
										if ($this->row < 0) $this->row = 0;
										$this->consoleLine = __LINE__;
										$this->consoleDesc = 'RevLineFeed';
									}
								break;

								//VT52 Other commands not supported
								case '=':
									$this->consoleStatus = 0;
									$this->consoleLine   = __LINE__;
									$this->consoleDesc   = 'UNSUPPORTED Enter Alternate Keypad Mode';
								break;

								case '>':
									$this->consoleStatus = 0;
									$this->consoleLine   = __LINE__;
									$this->consoleDesc   = 'UNSUPPORTED Exit Alternate Keypad Mode';
								break;


								case '<':
									$this->consoleStatus = 0;
									$this->consoleLine   = __LINE__;
									$this->consoleDesc   = 'UNSUPPORTED Enter ANSI Mode';
								break;


								// Some ODD commands that require PCRE
								case 'Y':
								case '+':
								case '#':
								case '(':
								case ')':
									$this->consoleStatus = 2;
									$this->consoleLine   = __LINE__;
									$this->consoleDesc   = 'pcre';
								break;

								default:
									// Unknown command
									$this->consoleStatus = 0;
									$this->consoleLine   = __LINE__;
									$this->consoleDesc   = 'UNKNOWN';
							}
							/* End of Escape sequence switch */
						}
					}

					// CONSOLE STATUS 2: Certain unusual escape sequences recognized by a PCRE
					elseif($this->consoleStatus == 2){
						$this->escapeSequence .= $sChar;

						if (preg_match('/^Y(.)(.)$/',$this->escapeSequence, $aMatches)){
							//VT52 Direct Cursor Address
							$this->row = ord($aMatches[1]) - 32 -1;
							$this->col = ord($aMatches[2]) - 32 -1;
							$this->consoleStatus = 0;
							$this->consoleLine   = __LINE__;
							$this->consoleDesc = 'CursorPosition';
						}
						elseif (preg_match('/^\+>(.)$/',$this->escapeSequence, $aMatches)){
							// Unknown
							$this->consoleStatus = 0;
							$this->consoleLine   = __LINE__;
							$this->consoleDesc   = 'UNKNOWN';
						}
						elseif (preg_match('/^\#(\d)$/',$this->escapeSequence, $aMatches)){
							// Unknown
							$this->consoleStatus = 0;
							$this->consoleLine   = __LINE__;
							$this->consoleDesc   = 'UNKNOWN';
						}
						// SCS � Select Character Set Escape sequence for G0
						elseif (preg_match('/^\((\w)$/',$this->escapeSequence, $aMatches)){
							$this->consoleShiftIn  = $this->identifyCharset($aMatches[1]);
							$this->consoleStatus = 0;
							$this->consoleLine   = __LINE__;
							$this->consoleDesc   = 'CHARSET SI '.$this->consoleShiftIn;
						}
						// SCS � Select Character Set Escape sequence for G1
						elseif (preg_match('/^\)(\w)$/',$this->escapeSequence, $aMatches)){
							$this->consoleShiftOut = $this->identifyCharset($aMatches[1]);
							$this->consoleStatus = 0;
							$this->consoleLine   = __LINE__;
							$this->consoleDesc   = 'CHARSET SO '.$this->consoleShiftOut;
						}
					}

					//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('ESCAPEEND',__LINE__,$this->getTimestamp());}
					/* END Process Console Commands */
				}

				if ($this->debug && $this->debug & 0x40 && ~$this->debug & 0x80) $this->showDebugLine($sChar);
				$this->bytes++;

			} /* end of if chr*/


			if ($this->debug && $this->debug & 0x80) $this->showDebugLine($sChar);
			//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('CHREND',__LINE__,$this->getTimestamp());}

			// Calculate total runing time

			$this->time = time() - $this->timeStart;
			// Increment iterations count
			$this->count++;
			//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('OUTSTART',__LINE__,$this->getTimestamp());}

			if ($this->streamType == VT100_STREAM_SOCKET && feof($this->streamHandle)) {
				$this->flag = 1;
			}
			elseif ($this->streamType == VT100_STREAM_FILE && feof($this->streamHandle)) {
				$this->flag = 1;
			}
			elseif ($this->streamType == VT100_STREAM_STRING && $sChar === false){
				$this->flag = 1;
			}
			elseif(($this->count % $this->conditionsInterval) == 0){

				// Loop the conditions to break the listen loop
				foreach ($this->Conditions as $n => $aC){
					switch ($aC[0]){
						// Listen runing time in seconds exceeds Arg1
						case VT100_COND_TIME:
							if ($this->time >= $aC[1])  { $this->flag = $aC[0]; }
							break;
						// Number of iterations exceeds Arg1
						case VT100_COND_COUNTER:
							if ($this->count >= $aC[1]) { $this->flag = $aC[0]; }
							break;
						// Number of bytes processed exceeds Arg1
						case VT100_COND_BYTES:
							if ($this->bytes >= $aC[1]) { $this->flag = $aC[0]; }
							break;
						// The buffers has a match with the PCRE of Arg1
						case VT100_COND_PCREBUF:
							if (preg_match($aC[1], $this->bufferListen)) {$this->flag = $aC[0];}
							break;
						// The screen substring defined by coords (Arg2, Arg3) to (Arg4, Arg5) is equal Arg1
						case VT100_COND_EQSCREEN:
							if ($this->getScreenString($aC[2], $aC[3], $aC[4], $aC[5]) == $aC[1]) {$this->flag = $aC[0];}
							break;
						// The screen substring defined by coords (Arg2, Arg3) to (Arg4, Arg5) matches the PCRE Arg1
						case VT100_COND_PCRESCREEN:
							if (preg_match($aC[1],$this->getScreenString($aC[2], $aC[3], $aC[4], $aC[5]))) {$this->flag = $aC[0];}
							break;
						// The current active position is (Arg1, Arg2) (Note: conditionsInterval must be 1)
						case VT100_COND_POS:
							if ($this->row == $aC[1] && $this->col == $aC[2]) {$this->flag = $aC[0];}
							break;
						// Idle time (time without receiving any data) exceeds Arg1
						case VT100_COND_IDLE:
							if ($this->time - $this->timeIdle >= $aC[1]) {$this->flag = $aC[0];}
							break;
						// The string Arg1 is found within the listen buffer (Similar to VT100_COND_PCREBUF, but without PCRE)
						case VT100_COND_STRING:
							if (strstr($this->bufferListen, $aC[1])) {$this->flag = $aC[0];}
							break;
					}
					if ($this->flag > 0){
						$this->flagCondition = $n;
						break;
					}
				}

				//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('OUTEND',__LINE__,$this->getTimestamp());}
			}
			//*T*/if($this->debug & 0x08){$this->DebugTiming[] = array('ENDWHILE',__LINE__,$this->getTimestamp());}
			//*T*/if($this->debug & 0x08){for ($i=0;$i<count($this->DebugTiming);$i++){printf("%10s %0.6f %0.6f %d\n",$this->DebugTiming[$i][0],($this->DebugTiming[$i][2] - @$this->DebugTiming[$i-1][2]),($this->DebugTiming[$i][2] - @$this->DebugTiming[0][2]),$this->DebugTiming[$i][1]);} $this->DebugTiming = array();}
		}

		//*T*/if($this->debug & 0x08){ echo "-- TIMING ENDED -------------------------------------------\n";}
		$this->timeListenEnd = $this->getTimestamp();
		$this->timeMicro = $this->timeListenEnd - $this->timeListenStart;
		return $this->flag;
	}

    /**
     * Add a condition to the Conditions array
     *
	 * @param integer $nType Type of condition
	 * @param mixed $xArg1 This varies according to nType
	 * @param mixed $xArg2 This varies according to nType
	 * @param mixed $xArg3 This varies according to nType
	 * @param mixed $xArg4 This varies according to nType
	 * @param mixed $xArg5 This varies according to nType
     */
	function addCondition($nType, $xArg1 = false, $xArg2 = false, $xArg3 = false, $xArg4 = false, $xArg5 = false){
		// Note: this method does not validate the entries, it simply adds them
		$this->Conditions[] = array($nType, $xArg1, $xArg2, $xArg3, $xArg4, $xArg5);
	}

    /**
     * Flush the Conditions array, and optionally add a condition at the top
     *
	 * @param integer $nType Type of condition
	 * @param mixed $xArg1 This varies according to nType
	 * @param mixed $xArg2 This varies according to nType
	 * @param mixed $xArg3 This varies according to nType
	 * @param mixed $xArg4 This varies according to nType
	 * @param mixed $xArg5 This varies according to nType
     */
	function flushConditions($nType = false, $xArg1 = false, $xArg2 = false, $xArg3 = false, $xArg4 = false, $xArg5 = false){
		$this->Conditions = array();
		if ($nType !== false){
			// Note: this method does not validate the entries, it simply adds them
			$this->Conditions[] = array($nType, $xArg1, $xArg2, $xArg3, $xArg4, $xArg5);
		}
 	}

     /**
     * Create an empty virtual screen
     * This is automatically done upon connect if you haven't done so yet
     *
	 * @param integer $nRows Number of rows (defaults to ->rows)
	 * @param integer $nCols Number of columns (defaults to ->cols)
	 * @param integer $sCharacter Fill character, default is space
     */
	function createScreen($nRows = false, $nCols = false, $sCharacter = ' '){
		if ($nRows !== false && $nRows > 0){
			$this->rows = $nRows;
		}
		if ($nCols !== false && $nCols > 0){
			$this->cols = $nCols;
		}
		// Create the array
		$this->Screen = array();
		// Fill the array
		for ($nRow = 0; $nRow < $this->rows; $nRow++){
			$this->Screen[$nRow] = array();
			for ($nCol = 0; $nCol < $this->cols; $nCol++){
				$this->Screen[$nRow][$nCol] = $sCharacter;
			}
		}
		return true;
	}

     /**
     * Obtain a substring by coordinates from the Virtual Screen
     * Note: If StartRow > EndRow then, the middle rows, will be the full rows, if you want a vertical block instead, then you should use the getScreenBlock method
     * Note: If EndRow < StartRow, then the method automatically swaps them
     * Note: If nStartRow == nEndRow && EndCol < StartCol, then the method automatically swaps the columns
     * Note: The method doesn't check if the coords are within range
     *
	 * @param integer $nStartRow Row where the substr should start (default is 0)
	 * @param integer $nStartCol Column where the substr should start (default is 0)
	 * @param integer $nEndRow   Row where the substr should end (default is nStartRow)
	 * @param integer $nEndCol   Column where the substr should end (default is last column)
	 * @param string  $sCrLf     String to use as CrLf (default is "\n")
	 * @return string The string from the screen
     */
	function getScreenString($nStartRow = 0, $nStartCol = 0, $nEndRow = false, $nEndCol = false, $sCrLf = "\n"){
		if ($nEndRow === false){
			$nEndRow = $nStartRow;
		}
		elseif ($nEndRow < $nStartRow){
			// Swap the end & start row if required
			$nTemp     = $nStartRow;
			$nStartRow = $nEndRow;
			$nEndRow   = $nTemp;
		}

		if ($nEndCol === false){
			$nEndCol = count($this->Screen[$nEndRow]) - 1; // Last column for last row
		}
		elseif($nStartRow == $nEndRow && $nEndCol < $nStartCol){
			// Swap the end & start cols if required
			// Note that if StartRow != EndRow, then it is perfectly valid to have an endcol < startcol
			$nTemp     = $nStartCol;
			$nStartCol = $nEndCol;
			$nEndCol   = $nTemp;
		}

		$sString = '';
		if ($nStartRow == $nEndRow){
			// In this case it is a much more efficient for
			$nForStart = $nStartCol;
			$nForEnd   = $nEndCol;
		}
		else{
			$nForStart = 0;
			$nForEnd   = $this->cols - 1;
		}
		// Loop and concat the string accordingly
		for ($y = $nStartRow; $y <= $nEndRow; $y++){
			for ($x = $nForStart; $x <= $nForEnd; $x++){
				if ($nStartRow == $nEndRow){
					if ($x >= $nStartCol && $x <= $nEndCol) $sString .= $this->Screen[$y][$x];
				}
				else {
					if ($y == $nStartRow){
						if ($x >= $nStartCol){
							$sString .= $this->Screen[$y][$x];
						}
					}
					elseif ($y == $nEndRow){
						if ($x <= $nEndCol){
							$sString .= $this->Screen[$y][$x];
						}
					}
					else {
						$sString .= $this->Screen[$y][$x];
					}
				}
			}
			if ($nStartRow != $nEndRow && $y != $nEndRow) $sString .= $sCrLf;
		}
		return $sString;
	}

    /**
     * Obtain a block by coordinates from the Virtual Screen
     * This is usefull to obtain data displayed as columns from the screen
     * Note: If EndRow < StartRow, then the method will loop in inverse order
     * Note: If EndCol < StartCol, then the method automatically swaps the columns
     * Note: The method doesn't check if the coords are within range
     *
	 * @param integer $nStartRow Row where the substr should start (default is 0)
	 * @param integer $nStartCol Column where the substr should start (default is 0)
	 * @param integer $nEndRow   Row where the substr should end (default is nStartRow)
	 * @param integer $nEndCol   Column where the substr should end (default is last column)
	 * @param string  $sCrLf     String to use as CrLf (default is "\n")
	 * @return string The string block rom the screen
     */
	function getScreenBlock($nStartRow = 0, $nStartCol = 0, $nEndRow = false, $nEndCol = false, $sCrLf = "\n"){
		if ($nEndRow === false){
			$nEndRow = $nStartRow;
		}

		if ($nEndCol === false){
			$nEndCol = count($this->Screen[$nEndRow]) - 1; // Last column for last row
		}
		elseif($nEndCol < $nStartCol){
			// Swap the end & start cols if required
			$nTemp     = $nStartCol;
			$nStartCol = $nEndCol;
			$nEndCol   = $nTemp;
		}

		$sString = '';
		if ($nEndRow == $nStartRow){
			for ($x = $nStartCol;$x <= $nEndCol; $x++){
				$sString .= $this->Screen[$nStartRow][$x];
			}
		}
		elseif ($nEndRow > $nStartRow){
			// Incrementally loop and concat the string accordingly
			for ($y = $nStartRow; $y <= $nEndRow; $y++){
				for ($x = $nStartCol;$x <= $nEndCol; $x++){
					$sString .= $this->Screen[$y][$x];
				}
				if ($y != $nEndRow) $sString .= $sCrLf;
			}
		}
		else{
			// Decrementally loop and concat the string accordingly
			for ($y = $nStartRow; $y >= $nEndRow; $y--){
				for ($x = $nStartCol;$x <= $nEndCol; $x++){
					$sString .= $this->Screen[$y][$x];
				}
				if ($y != $nEndRow) $sString .= $sCrLf;
			}
		}
		return $sString;
	}

     /**
     * Clear a substring by coordinates from the Virtual Screen
     * Note: If StartRow > EndRow then, the middle rows, will be the full rows, if you want to clear a vertical block instead, then you should use the clearScreenBlock method
     * Note: If EndRow < StartRow, then the method automatically swaps them
     * Note: If nStartRow == nEndRow && EndCol < StartCol, then the method automatically swaps the columns
     * Note: The method doesn't check if the coords are within range
     *
	 * @param integer $nStartRow Row where the substr should start (default is 0)
	 * @param integer $nStartCol Column where the substr should start (default is 0)
	 * @param integer $nEndRow   Row where the substr should end (default is nStartRow)
	 * @param integer $nEndCol   Column where the substr should end (default is last column)
	 * @param string  $sClear    String to fill into the screen (default is space)
     */
	function clearScreen($nStartRow = 0, $nStartCol = 0, $nEndRow = false, $nEndCol = false, $sClear = ' '){
		if ($nEndRow === false){
			$nEndRow = $nStartRow;
		}
		elseif ($nEndRow < $nStartRow){
			// Swap the end & start row if required
			$nTemp     = $nStartRow;
			$nStartRow = $nEndRow;
			$nEndRow   = $nTemp;
		}

		if ($nEndCol === false){
			$nEndCol = count($this->Screen[$nEndRow]) - 1; // Last column for last row
		}
		elseif($nStartRow == $nEndRow && $nEndCol < $nStartCol){
			// Swap the end & start cols if required
			// Note that if StartRow != EndRow, then it is perfectly valid to have an endcol < startcol
			$nTemp     = $nStartCol;
			$nStartCol = $nEndCol;
			$nEndCol   = $nTemp;
		}

		if ($nStartRow == $nEndRow){
			// In this case it is a much more efficient for
			$nForStart = $nStartCol;
			$nForEnd   = $nEndCol;
		}
		else{
			$nForStart = 0;
			$nForEnd   = $this->cols - 1;
		}
		// Loop and concat the string accordingly
		for ($y = $nStartRow; $y <= $nEndRow; $y++){
			for ($x = $nForStart; $x <= $nForEnd; $x++){
				if ($nStartRow == $nEndRow){
					if ($x >= $nStartCol && $x <= $nEndCol) $this->Screen[$y][$x] = $sClear;
				}
				else {
					if ($y == $nStartRow){
						if ($x >= $nStartCol){
							$this->Screen[$y][$x] = $sClear;
						}
					}
					elseif ($y == $nEndRow){
						if ($x <= $nEndCol){
							$this->Screen[$y][$x] = $sClear;
						}
					}
					else {
						$this->Screen[$y][$x] = $sClear;
					}
				}
			}
		}
		return true;
	}


    /**
     * Clear a block by coordinates from the Virtual Screen
	 * Note: If EndRow < StartRow, then the method automatically swaps them
     * Note: If EndCol < StartCol, then the method automatically swaps the columns
     * Note: The method doesn't check if the coords are within range
     *
	 * @param integer $nStartRow Row where the substr should start (default is 0)
	 * @param integer $nStartCol Column where the substr should start (default is 0)
	 * @param integer $nEndRow   Row where the substr should end (default is nStartRow)
	 * @param integer $nEndCol   Column where the substr should end (default is last column)
	 * @param string  $sClear    String to fill into the screen (default is space)
     */
	function clearScreenBlock($nStartRow = 0, $nStartCol = 0, $nEndRow = false, $nEndCol = false, $sClear = ' '){
		if ($nEndRow === false){
			$nEndRow = $nStartRow;
		}
		elseif ($nEndRow < $nStartRow){
			// Swap the end & start row if required
			$nTemp     = $nStartRow;
			$nStartRow = $nEndRow;
			$nEndRow   = $nTemp;
		}

		if ($nEndCol === false){
			$nEndCol = count($this->Screen[$nEndRow]) - 1; // Last column for last row
		}
		elseif($nEndCol < $nStartCol){
			// Swap the end & start cols if required
			$nTemp     = $nStartCol;
			$nStartCol = $nEndCol;
			$nEndCol   = $nTemp;
		}

		// Loop and clear
		for ($y = $nStartRow; $y <= $nEndRow; $y++){
			for ($x = $nStartCol;$x <= $nEndCol; $x++){
				$this->Screen[$y][$x] = $sClear;
			}
		}

		return true;
	}

    /**
     * Get the full Virtual Screen as a string
	 * @param string  $sCrLf   String to use as CrLf (default is "\n")
	 * @param boolean $bRulers Show very basic column/row rulers (default is false)
     */
 	function getScreenFull($sCrLf = "\n", $bRulers = false){
 		if ($sCrLf === false){
 			$sCrLf = "\n";
 		}
 		$sScreen = '';
 		// Having everything together would mean many unnecessary ifs when bRules = false, so better to have things separated
 		if ($bRulers){
 			// SHOW RULERS
 			$sScreen  = '  |';
 			$nSkip    = 0;
 			for ($nCol = 0; $nCol < $this->cols; $nCol++){
 				if ($nCol % 10 == 0){
 					$sScreen .= (string) intval($nCol / 10);
 					$nSkip    = strlen((string) intval($nCol / 10)) - 1;
 				}
 				elseif($nSkip > 0){
 					$nSkip--;
 				}
 				else{
 					$sScreen .= ' ';
 				}
 			}
 			$sScreen .= '|  '.$sCrLf;
 			$sScreen .= '  |'. str_pad ('', $this->cols, '0123456789').'|  '.$sCrLf;
 			$sScreen .= '--|'. str_pad ('', $this->cols, '+---------').'|--'.$sCrLf;

	 		for ($nRow = 0; $nRow < $this->rows; $nRow++){
				$sScreen .= sprintf('% 2d', ($nRow % 100));
 				$sScreen .= ($nRow % 10 == 0) ? '+' : '|';
	 			for ($nCol = 0; $nCol < $this->cols; $nCol++){
	 				$sScreen .= $this->Screen[$nRow][$nCol];
	 			}
 				$sScreen .= ($nRow % 10 == 0) ? '+' : '|';
 				$sScreen .= sprintf('% 2d', ($nRow % 100)) . $sCrLf;
	 		}
	 		$sScreen .= '--|'. str_pad ('', $this->cols, '+---------').'|--'.$sCrLf;
	 		$sScreen .= '  |'. str_pad ('', $this->cols, '0123456789').'|'.$sCrLf;

 		}
 		else{
 			// REGULAR OUTPUT
 			for ($nRow = 0; $nRow < $this->rows; $nRow++){
	 			for ($nCol = 0; $nCol < $this->cols; $nCol++){
 					$sScreen .= $this->Screen[$nRow][$nCol];
 				}
	 			if ($nRow != ($this->rows - 1)){
 					$sScreen .= $sCrLf;
 				}
 			}
 		}
		return $sScreen;
	}

    /**
     * Given a screen definition of the fields, extract the fields information by coords
     * NOTE: This function currently doesn't validate entries in the definitions
	 * @param array|string  $aScreenDefinition Either the Screen Definitions array of arrays, or a string key to one already loaded at ->ScreenDefinitions
	 * @param boolean  $bClear Clear the existing screen objects before starting (default true)
	 * @param boolean  $bTrim  Trim the obtained values (default true)
	 * @return boolean Success/Error
     */
	function getScreenObjects($xScreenDefinition, $bClear = true, $bTrim = true){
		if (is_array($xScreenDefinition)){
			$aScreenDefinition =& $xScreenDefinition;
		}
		elseif(is_string($xScreenDefinition) && isset($this->ScreenDefinitions[$xScreenDefinition])){
			$aScreenDefinition = $this->ScreenDefinitions[$xScreenDefinition];
		}
		else{
			// Unknown definitions
			return false;
		}

		if (!is_array($aScreenDefinition)){
			// Unknown definitions
			return false;
		}
		if ($bClear){
			$this->ScreenObjects = array();
		}


		foreach ($aScreenDefinition as $sObject => &$aDef){
			if (@$aDef['type'] == 'TABLE'){
				// Create the empty object and get a reference to make things easier
				$this->ScreenObjects[$sObject] = array();
				$aOut =& $this->ScreenObjects[$sObject];

				// Validate that you have the cols defined, which are always named
				if (isset($aDef['cols']) && is_array($aDef['cols'])){

					// Loop the cols
					foreach ($aDef['cols'] as $sCol => &$xCol){
						// Determine CS and CE
						if (is_array($xCol)){
							if (!isset($xCol['cs']) || !is_int($xCol['cs'])){
								continue;
							}
							$cs = $xCol['cs'];
							$ce = (isset($xCol['ce']) && is_int($xCol['ce'])) ? $xCol['ce'] : $cs;
						}
						elseif(is_int($xCol)){
							$cs = $ce = $xCol;
						}
						else{
							continue;
						}

						// If required, process unnamed rows
						if (isset($aDef['rowStart']) && is_int($aDef['rowStart'])){
							$rs = $aDef['rowStart'];
							$re = (isset($aDef['rowEnd']) && is_int($aDef['rowEnd'])) ? $aDef['rowEnd'] : $rs;

							// Loop the rows
							$n = 0;
							for ($r = $rs; $r <= $re; $r++){
								// Get the value and trim it if required
								$sValue = $this->getScreenBlock($r, $cs, $r, $ce);
								if ($bTrim){
									$sValue = trim($sValue);
								}

								// VERTICAL tables are those whose records are presented as columns
								if (@$aDef['orientation'] == 'VERTICAL'){
									$aOut[$sCol][$n] = $sValue;
								}
								// HORIZONTAL tables are those whose records are presented as rows
								else{
									$aOut[$n][$sCol] = $sValue;
								}
								$n++;
							}
						}

						// If required, process named rows
						if (isset($aDef['rows']) && is_array($aDef['rows']) && count($aDef['rows'])){
							// Loop rows
							foreach ($aDef['rows'] as $sRow => &$xRow){
								if (is_array($xRow)){
									$rs = @$xRow['rs'];
									$re = (isset($xRow['re']) && is_int($xRow['re'])) ? $xRow['re'] : $rs;
								}
								elseif(is_int($xRow)){
									$rs = $re = $xRow;
								}
								else{
									continue;
								}

								// Get the value and trim it if required
								$sValue = $this->getScreenBlock($rs, $cs, $rs, $ce);
								if ($bTrim){
									$sValue = trim($sValue);
								}

								// VERTICAL tables are those whose records are presented as columns
								if (@$aDef['orientation'] == 'VERTICAL'){
									$aOut[$sCol][$sRow] = $sValue;
								}
								// HORIZONTAL tables are those whose records are presented as rows
								else{
									$aOut[$sRow][$sCol] = $sValue;
								}
							}
						}
					}
				}
			}
			// STRING OBJECT
			elseif (@$aDef['type'] == 'STRING'){
				if (isset($aDef['rs']) && is_int($aDef['rs']) && isset($aDef['cs']) && is_int($aDef['cs']) ){
					$rs = $aDef['rs'];
					$re = (isset($aDef['re']) && is_int($aDef['re']))? $aDef['re'] : $rs;
					$cs = $aDef['cs'];
					$ce = (isset($aDef['ce']) && is_int($aDef['ce']))? $aDef['ce'] : $cs;
					$this->ScreenObjects[$sObject] = $this->getScreenString($rs, $cs, $re, $ce);
					if ($bTrim){
						$this->ScreenObjects[$sObject] = trim($this->ScreenObjects[$sObject]);
					}
				}
			}
			// BLOCK OBJECT
			elseif (@$aDef['type'] == 'BLOCK'){
				if (isset($aDef['rs']) && is_int($aDef['rs']) && isset($aDef['cs']) && is_int($aDef['cs']) ){
					$rs = $aDef['rs'];
					$re = (isset($aDef['re']) && is_int($aDef['re']))? $aDef['re'] : $rs;
					$cs = $aDef['cs'];
					$ce = (isset($aDef['ce']) && is_int($aDef['ce']))? $aDef['ce'] : $cs;
					$this->ScreenObjects[$sObject] = $this->getScreenBlock($rs, $cs, $re, $ce);
					if ($bTrim){
						$this->ScreenObjects[$sObject] = trim($this->ScreenObjects[$sObject]);
					}
				}
			}
		}
		return true;
	}

    /**
     * Get the current timestamp in microseconds
	 * @return float Timestamp in us
     */
	function getTimestamp(){
		// this uses the microtime() without parameters as it used to be before PHP 5.0
		list($usec, $sec) = explode(' ', microtime());
		return ((float) $usec + (float) $sec);
	}

    /**
     * Identify the charset according to SCS � Select Character Set Escape sequence
	 * @param string Parameter from SCS
	 * @return integer Id of the charset (see constants at load method)
     */
	function identifyCharset($sCharset){
		switch ($sCharset){
			case 'A': return VT100_CHARSET_UK;
			case 'B': return VT100_CHARSET_ASCII;
			case '0': return VT100_CHARSET_VT100_G0;
			case '1': return VT100_CHARSET_VT100_G1;
			case '2': return VT100_CHARSET_VT100_G2;
			default:
			          return VT100_CHARSET_ASCII;
		}
	}

    /**
     * Encode (translate) a string with inline commands like <COMMAND> into their binary form
     * @param string $sString The string to be encoded
	 * @return string The binary string with command tags replaced
     */
	function encode($sString){
		return str_replace($this->MapEncodeFrom, $this->MapEncodeTo, $sString);
	}

    /**
     * Display a line with full information
     * This function is internally used during listen() to make deep debugs
	 * @param string The character that the listen is currently processing
     */
	function showDebugLine($sChar){
		static $nLastTelnetStatus  = 0;
		static $nLastConsoleStatus = 0;

		$sColor = false;

		// Get the numeric value of char
		$nOrd   = ord($sChar);
		// Prepare something more readable
		switch($sChar){
			case "\0": $sCharOut = '<b>NUL</b>'; break;
			case "\n": $sCharOut = '<b>LF</b> '; break;
			case "\r": $sCharOut = '<b>CR</b> '; break;
			case "\t": $sCharOut = '<b>TAB</b>'; break;
			default:   $sCharOut = '['.htmlentities($sChar).']';
		}

		// Prepare the baseline
		$sLine = sprintf("B:%04d T:%02d I:%02d C:%04d RC:%02d/%03d TS:%d CS:%d Chs:%d Chr:%s %03d ",
			$this->bytes,
			$this->time,
			$this->timeIdle,
			$this->count,
			$this->row,
			$this->col,
			$this->telnetStatus,
			$this->consoleStatus,
			$this->consoleCharset,
			$sCharOut,
			$nOrd);

		// React to Telnet commands
		if ($this->telnetStatus > 0){
			$sColor = '0000FF';
			$sLine .= sprintf('TELNET [%s]', htmlentities($this->bufferCmd));
		}
		elseif($nLastTelnetStatus){
			$sColor = 'FF0000';
			$sLine .= sprintf('TELNET [%s]', htmlentities($this->bufferCmd));
		}

		// React to Console sequences
		if ($this->consoleStatus > 0){
			$sColor = '0000FF';
			$sLine .= sprintf('CONSOLE [%s]', htmlentities($this->escapeSequence));
		}
		elseif($nLastConsoleStatus){
			$sColor = 'FF0000';
			$sLine .= sprintf('CONSOLE [%s] %d - %s', htmlentities($this->escapeSequence), $this->consoleLine, $this->consoleDesc);
		}

		print ($sColor) ? '<font color="#'.$sColor.'">'.$sLine.'</font>'."\n"   : $sLine."\n";

		$nLastConsoleStatus = $this->consoleStatus;
		$nLastTelnetStatus  = $this->telnetStatus;

	}

    /**
     * Print a step debug dump
     * Note: you should call this within a <pre></pre> otherwise you'll see odd stuff
     *
	 * @param string $sStep Description of the current step
	 * @param string $nLine Line where called, usually __LINE__
     */
	function showDebugStep($sStep='', $nLine=0){
		$this->stepNum++;
		$this->stepDesc = $sStep;
		if ($this->debugSteps > 0){
			printf("%s Step %03d %-25s Line: %04d Flag:%02d Time:%02d MTime: %0.4f Idle:%02d Byte:%04d Count:%04d\n",
				date('H:i:s'),
				$this->stepNum++,
				$sStep,
				$nLine,
				$this->flag,
				$this->time,
				$this->timeMicro,
				$this->timeIdle,
				$this->bytes,
				$this->count
			);
			if ($this->debugSteps > 1) print "<hr/>\n".htmlentities($this->getScreenFull(false, true))."<hr/>\n";
			if ($this->debugSteps > 3){
				if ($this->stepDumpHex){
					print bin2hex($this->bufferListen)."\n<hr/>";
				}
				else{
					print htmlentities($this->bufferListen)."\n<hr/>";
				}
			}
		}
		return true;
	}


    /**
     * Load the constants and information arrays needed
	 * @return boolean True
     */
 	function load(){
		if (!defined('VT100_LOADED')){
			define('VT100_LOADED',   true);
			define('VT100_DEBUG_TIMING',        0x08); //Timing points
			define('VT100_DEBUG_CMDIN',         0x10); //Telnet RECEIVED command completed
			define('VT100_DEBUG_CMDOUT',        0x20); //Telnet command autoanswer SENT
			define('VT100_DEBUG_ESCCHR',        0x40); //End of escape secuence (Only when chr is received)
			define('VT100_DEBUG_ALWAYS',        0x80); //End of escape secuence (Always - Non Blocking)

			define('VT100_COND_EOF',            0x01); //End of File (Socket Closed)
			define('VT100_COND_TIME',           0x02); //Listen Running Time
			define('VT100_COND_COUNTER',        0x03); //Number of iterations
			define('VT100_COND_BYTES',          0x04); //Number of bytes processed
			define('VT100_COND_PCREBUF',        0x05); //PCRE on Listen Buffer
			define('VT100_COND_EQSCREEN',       0x06); //Screen SubString EQ
			define('VT100_COND_PCRESCREEN',     0x07); //PCRE on Screen SubString
			define('VT100_COND_POS',            0x08); //Active Position
			define('VT100_COND_IDLE',           0x09); //Idle Time (Running time without new data)
			define('VT100_COND_STRING',         0x0A); //String in String in Midbuffer

			define('VT100_STREAM_SOCKET',       0x00); // Type of stream: Socket
			define('VT100_STREAM_STRING',       0x01); // Type of stream: String
			define('VT100_STREAM_FILE',         0x02); // Type of stream: File

			define('VT100_CHARSET_ASCII',       0x00); // Charset: ASCII
			define('VT100_CHARSET_UK',          0x01); // Charset: UK standard
			define('VT100_CHARSET_ANSI',        0x02); // Charset: ANSI
			define('VT100_CHARSET_VT100_G0',    0x03); // Charset: VT100 - 0 Special Graphics Alternate Character ROM Standard Character Set
			define('VT100_CHARSET_VT100_G1',    0x04); // Charset: VT100 - 1 Alternate Character ROM Standard Character Set
			define('VT100_CHARSET_VT100_G2',    0x05); // Charset: VT100 - 2 Alternate Character ROM Special Graphics

			define('VT100_PCRE_UP',        '/\e\[1?A/'); // UP escape sequence (usefull with VT100_COND_PCREBUF)
			define('VT100_PCRE_DOWN',      '/\e\[1?B/'); // DOWN escape sequence (usefull with VT100_COND_PCREBUF)
		}

		// Telnet commands
		$this->MapTelnetCommands = array(
		 'SE' =>  chr(0xF0),
		 'SB'=>   chr(0xFA),
		 'WILL'=> chr(0xFB),
		 'WONT'=> chr(0xFC),
		 'DO'=>   chr(0xFD),
		 'DONT'=> chr(0xFE),
		 'IAC'=>  chr(0xFF)
		);

		// Telnet Options
		$this->MapTelnetOptions = array(
		 'BINARY'=>       chr(0x00),
		 'ECHO'=>         chr(0x01),
		 'SGA'=>          chr(0x03),
		 'STATUS'=>       chr(0x05),
		 'DET'=>          chr(0x18),
		 'TERMTYPE'=>     chr(0x18),
		 'NAWS'=>         chr(0x1F),
		 'TSPEED'=>       chr(0x20),
		 'REMOTEFLOW'=>   chr(0x21),
		 'LINEMODE'=>     chr(0x22),
		 'ENVIRON'=>      chr(0x24),
		 'NEWENV'=>       chr(0x27),
		 'EXOPL'=>        chr(0xFF)
		);

		// Telnet misc
		$this->MapMisc = array(
		 'NUL'=>          chr(0x00),
		 'IS'=>           chr(0x00),
		 'SEND'=>         chr(0x01)
		);

		// Console escape sequences and odd characters used in stream translations
		$this->MapConsole = array(
		 // VT100 escape sequences
		 'UP'    => chr(0x1B).'[A',
		 'DOWN'  => chr(0x1B).'[B',
		 'RIGHT' => chr(0x1B).'[C',
		 'LEFT'  => chr(0x1B).'[D',
		 'HOME'  => chr(0x1B).'[1~',
		 'INS'   => chr(0x1B).'[2~',
		 'DEL'   => chr(0x1B).'[3~',
		 'END'   => chr(0x1B).'[4~',
		 'PGUP'  => chr(0x1B).'[5~',
		 'PGDN'  => chr(0x1B).'[6~',
		 // ANSI ARROWS
		 'ANSI_UP'    => chr(0x1B).'OA',
		 'ANSI_DOWN'  => chr(0x1B).'OB',
		 'ANSI_RIGHT' => chr(0x1B).'OC',
		 'ANSI_LEFT'  => chr(0x1B).'OD',
		 /* ANSI F1 - F20 */
		 'F1'    => chr(0x1B).'[11~',
		 'F2'    => chr(0x1B).'[12~',
		 'F3'    => chr(0x1B).'[13~',
		 'F4'    => chr(0x1B).'[14~',
		 'F5'    => chr(0x1B).'[15~',
		 'F6'    => chr(0x1B).'[17~',
		 'F7'    => chr(0x1B).'[18~',
		 'F8'    => chr(0x1B).'[19~',
		 'F9'    => chr(0x1B).'[20~',
		 'F10'   => chr(0x1B).'[21~',
		 'F11'   => chr(0x1B).'[23~',
		 'F12'   => chr(0x1B).'[24~',
		 'F13'   => chr(0x1B).'[25~',
		 'F14'   => chr(0x1B).'[26~',
		 'F15'   => chr(0x1B).'[28~',
		 'F16'   => chr(0x1B).'[29~',
		 'F17'   => chr(0x1B).'[31~',
		 'F18'   => chr(0x1B).'[32~',
		 'F19'   => chr(0x1B).'[33~',
		 'F20'   => chr(0x1B).'[34~',
		 // these F20-24 really don't work
		 'F21'   => chr(0x1B).'[35~',
		 'F22'   => chr(0x1B).'[36~',
		 'F23'   => chr(0x1B).'[37~',
		 'F24'   => chr(0x1B).'[38~',
		 // Regular ASCII characters
		 'ALT'   => chr(0x01),
		 'NUL'   => chr(0x00),
		 'BS'    => chr(0x08),
		 'BELL'  => chr(0x07),
		 'TAB'   => chr(0x09),
		 'LF'    => chr(0x0A),
		 'CR'    => chr(0x0D),
		 'ESC'   => chr(0x1B),
		);

		// Fill in the inverse of the Telnet Commands, these are used mainly for debug
		$this->MapTelnetCommandsRev = array();
		foreach ($this->MapTelnetCommands as $key => $val){
			$this->MapTelnetCommandsRev[$val] = $key;
		}
		// Fill in the inverse of the Telnet Options, these are used mainly for debug
		$this->MapTelnetOptionsRev = array();
		foreach ($this->MapTelnetOptions as $key => $val){
			$this->MapTelnetOptionsRev[$val] = $key;
		}

		// These special arrays are used by encode() to handle everything at once
		$this->MapEncodeFrom = array();
		$this->MapEncodeTo   = array();
		foreach ($this->MapTelnetCommands as $sKey => $sVal){
			$this->MapEncodeFrom[] = '<'.$sKey.'>';
			$this->MapEncodeTo[]   = $sVal;
		}
		foreach ($this->MapTelnetOptions as $sKey => $sVal){
			$this->MapEncodeFrom[] = '<OPT_'.$sKey.'>';
			$this->MapEncodeTo[]   = $sVal;
		}
		foreach ($this->MapMisc as $sKey => $sVal){
			$this->MapEncodeFrom[] = '<'.$sKey.'>';
			$this->MapEncodeTo[]   = $sVal;
		}
		foreach ($this->MapConsole as $sKey => $sVal){
			$this->MapEncodeFrom[] = '<'.$sKey.'>';
			$this->MapEncodeTo[]   = $sVal;
		}
		return true;
	}
}

/****************** ESCAPE CODES *************************
VT100+
HOME <ESC> h
END <ESC> k
INSERT <ESC> +
DELETE <ESC> -
PAGE UP <ESC> ?
PAGE DOWN <ESC> /
ALT <ESC>^A
CTRL <ESC>^C
F1 <ESC> 1
F2 <ESC> 2
F3 <ESC> 3
F4 <ESC> 4
F5 <ESC> 5
F6 <ESC> 6
F7 <ESC> 7
F8 <ESC> 8
F9 <ESC> 9
F10 <ESC> 0
F11 <ESC> !
F12 <ESC> @

ANSI
  F1	    Esc	 [ 1 1 ~
  F2	    Esc	 [ 1 2 ~
  F3	    Esc	 [ 1 3 ~
  F4	    Esc	 [ 1 4 ~
  F5	    Esc	 [ 1 5 ~
  F6	    Esc	 [ 1 7 ~
  F7	    Esc	 [ 1 8 ~
  F8	    Esc	 [ 1 9 ~
  F9	    Esc	 [ 2 0 ~
  F10	    Esc	 [ 2 1 ~
  F11	    Esc	 [ 2 3 ~
  F12	    Esc	 [ 2 4 ~
  F13	    Esc	 [ 2 5 ~
  F14	    Esc	 [ 2 6 ~
  F15	    Esc	 [ 2 8 ~
  F16	    Esc	 [ 2 9 ~
  F17	    Esc	 [ 3 1 ~
  F18	    Esc	 [ 3 2 ~
  F19	    Esc	 [ 3 3 ~
  F20	    Esc	 [ 3 4 ~
  Help	    Esc	 [ 2 8 ~
  Menu	    Esc	 [ 2 9 ~
  Find	    Esc	 [ 1 ~
  Insert    Esc	 [ 2 ~
  Delete    Esc	 [ 3 ~
  Remove    Esc	 [ 3 ~
  Select    Esc	 [ 4 ~
  Prior	    Esc	 [ 5 ~
  Next	    Esc	 [ 6 ~

************************************************************/
