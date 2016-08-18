<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=IBM437"/>
</head>
<body>
<pre>
<?php


// IMPORTANT: Note on charsets: The charset in which the server will provide information, varies according to the server implementation.
// On windows systems, this usually is IBM437 (http://en.wikipedia.org/wiki/Code_page_437)
// That's why on top of this HTML, you have this charset specified in the META tag.
// Whatever charset it is, you will have to deal with this by yourself. See iconv php library, which might help you.

// We will use these variables
$sUsername   = 'foo';
$sPassword   = 'bar';
$sServerHost = '127.0.0.1';
$sServerPort = 23;

// We want to see every single PHP error/notice while we debug
error_reporting(E_ALL);

// We require the library
require_once('lib.vt100.php');

// Create the object
$oVT100 = new VT100();

// Create the virtual screen specifying 24 rows x 80 cols (You might need to alter this based on your system)
$oVT100->createScreen(24, 80);

// Set the terminal type, you usually don't touch this
$oVT100->telnetTermType = 'VT100';


// You might not even need this if your case is simple.
// In this example, the screen definitions used to extract complex data by coords from the screen, is simply defined
// into an array in the include and then set into the property
// Read its content to see about it
include('inc.definitions.php');
$oVT100->ScreenDefinitions = &$aDefs;

$bRv = $oVT100->connect($sServerHost, $sServerPort, false, 5);
if ($bRv === false) {
	print "Error: Could not connect\n";
	print "Socket error {$oVT100->socketErrorNum}: {$oVT100->socketErrorMsg}\n";
	exit;
}

/*

   Conditions:
   -----------
   From now on, you will have send listen cycles
   To achieve this, before entering a listen() you setup the conditions upon which the listen will stop.
   You have the following condition types available:

      VT100_COND_EOF:        Disconnection, this one is always evaluated, you don't need to add it
      VT100_COND_TIME:       Listen runing time in seconds exceeds Arg1
      VT100_COND_COUNTER:    Number of iterations exceeds Arg1
      VT100_COND_BYTES:      Number of bytes processed exceeds Arg1
      VT100_COND_PCREBUF:    The buffers has a match with the PCRE of Arg1
      VT100_COND_EQSCREEN:   The screen substring defined by coords (Arg2, Arg3) to (Arg4, Arg5) is equal Arg1
      VT100_COND_PCRESCREEN: The screen substring defined by coords (Arg2, Arg3) to (Arg4, Arg5) matches the PCRE Arg1
      VT100_COND_POS:        The current active position is (Arg1, Arg2) (Note: conditionsInterval must be 1)
      VT100_COND_IDLE:       Idle time (time without receiving any data) exceeds Arg1
      VT100_COND_STRING:     The string Arg1 is found within the listen buffer (Similar to VT100_COND_PCREBUF, but without PCRE)

	EOF (disconnection) will always be detected, setting the ->flag to 1

	The main problem with most systems which don't have a prompt, is to detect when they are waiting for you, as this varies from system to system.
	Sometimes you simply don't have a string, cursor position, anything that you can use to detect that the server is done, and it is waiting you.
	In those cases, you have to rely on VT100_COND_IDLE conditions. These are integer values, in seconds. But you cannot set it to less than 2,
	because in some circumstances, like when rolling a from one second to another, the idle counter may be showing 1 sec idle, when in reality it was much less than that

	So to make a long story short.... you must always avoid relying in timeouts... better find a good string to identify when thescreen is done.
	Also consider that VT100_COND_STRING is much faster than VT100_COND_PCREBUF, and that these are usually faster than VT100_COND_PCRESCREEN or VT100_COND_EQSCREEN

	Usage of ->showDebugSteps and showDebugStep():
	------------------------------------------
	When setting up the systems interactions, is always good to use after every listen() the showDebugStep() method whose output is controlled by the ->showDebugSteps property.
	This will prove very usefull information, down to the stream level.

	Usage of ->debug property:
	--------------------------
	The main purpose of the ->debug property, is to debug the Console parser (the low level), so you normally don't need to change it.

	Initial handshaking:
	--------------------

	When a Telnet connection is established, there is a process of handshaking between server and client, which normally is totally hidden.
	The emulator, can successfully autonegotiate with the server, that means it understands the server requests, and automatically replies to it.
	However, sometimes, according to the server implementation, the server might be waiting for the client to initiate with some sequence. In those
	cases you have to, upon connection, immediately start by sending a handshaking sequence like the one provided in this example.
	It is advisable to simple use it, because it will normally do no harm.

*/


// 1 - Telnet Handshaking and initial screen load
$sHandshaking = '<IAC><WILL><OPT_BINARY><IAC><DO><OPT_BINARY><IAC><WILL><OPT_TSPEED><IAC><SB><OPT_TSPEED><IS>38400,38400<IAC><SE>';

$oVT100->flushConditions(VT100_COND_TIME, 10);
$oVT100->addCondition(VT100_COND_IDLE, 5);
$oVT100->addCondition(VT100_COND_STRING, 'COPYRIGHT'); // when this loads we know the full screen was parsed
$oVT100->listen($sHandshaking, true);
$oVT100->showDebugStep('Connect', __LINE__);

// Here you have an example of how to obtain a piece of the the screen
$sSystemID = trim($oVT100->getScreenBlock(0, 15, 0, 18));


// 2- Send user & pass
$oVT100->flushConditions(VT100_COND_TIME,30);
$oVT100->addCondition(VT100_COND_IDLE,25);
$oVT100->addCondition(VT100_COND_STRING,   'SELECT OPTION'); // Logged in OK!
$oVT100->addCondition(VT100_COND_PCREBUF,  '/ERROR 5\d+/'); // Oops, failed login (this is condition 3)
$oVT100->addCondition(VT100_COND_EQSCREEN, 'Press <ENTER> to continue', 21, 24, 21, 48); // Waiting for an enter
$oVT100->listen("$sUsername<TAB>$sPassword<CR>", true); // remember that RETURN KEY is \r not \n
$oVT100->showDebugStep('User & Pass', __LINE__);

if ($oVT100->flagCondition == 3){
	$nErrorCode = trim($oVT100->getScreenBlock(23, 0, 23, 6));
	$sErrorDesc = trim($oVT100->getScreenBlock(23, 8, 23, 80));
	print "Error: Login failed with error $nErrorCode: $sErrorDesc";
	exit;
}

if ($oVT100->flagCondition == 4){
	// 2.b The bloody thing is waiting for an enter! (look above in line 119 for condition 4)
	$oVT100->flushConditions(VT100_COND_TIME,30);
	$oVT100->addCondition(VT100_COND_IDLE,25);
	$oVT100->addCondition(VT100_COND_STRING,   'SELECT OPTION'); // Logged in OK!
	$oVT100->addCondition(VT100_COND_PCREBUF,  '/ERROR 5\d+/'); // Oops, failed login (this is condition 3)
	$oVT100->listen("\r"); // remember that RETURN KEY is \r not \n

	$oVT100->showDebugStep('Sent ENTER', __LINE__);
}

if ($oVT100->flagCondition != 2){
	print "Error: Unexpected screen";
	exit;
}

// 3 - Select option 3 from the menu
// Note that I am reusing the same conditions from now on, this really depends on your flow, so I am not adding conditions or flushing them
// Note that I am using special tags to send keystrokes or escape sequences (arrows in this case)
$oVT100->listen('<F3><DOWN><DOWN><RIGHT><RIGHT>3<CR>', true);
$oVT100->showDebugStep('Option 3', __LINE__);
if ($oVT100->flagCondition != 2){
	print "Error: Unexpected screen";
	exit;
}


// 4 - Search for a given record
$oVT100->listen("123456790\r"); // Search for this record
$oVT100->showDebugStep('Search', __LINE__);
if ($oVT100->flagCondition != 2){
	print "Error: Unexpected screen";
	exit;
}

// Give a look at the file inc.definitions.php
$oVT100->getScreenObjects('Report'); // I'll get the objects defined in the screen "Report" in ScreenDefinitions

// 5 - Logout
$oVT100->addCondition(VT100_COND_STRING,   'bye bye');
$oVT100->listen("quit\r"); // Exit...
$oVT100->showDebugStep('Quit', __LINE__);
$oVT100->disconnect();

// This is a simple field
print $oVT100->ScreenObjects['username']."\n";
// This is a table
foreach ($oVT100->ScreenObjects['results'] as $n => $aRecord){
	print $aRecord['split'] .' '. $aRecord['acd_calls'] ."\n";
}

?>
</pre>
</body>
</html>
