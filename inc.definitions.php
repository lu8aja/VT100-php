<?php

/*******************************************************************************************************************
version 1.0 2009-04-23

  SCREEN DEFINITIONS
  ==================

In this array you can setup all the Screen Definitions of your system.
A Screen Definition, is basically, coordinates information that define how to extract complex information from screens which have many fields.
Within a screen you will define Objects, basically fields or tables
You can define many different Screens, so you can have all the information of your system in a single place, and later use this information
within your application by referencing a speciffic Screen.
You will put all these definitions in the property ScreenDefinitions of the VT100 object, and later within your code, you would use
getScreenObjects(ScreenName) to get the data. The data will then be saved into the ScreenObjects objects and be ready for you.

Keep in mind that there is no real validation when you call getScreenObjects(), so you must:

                                           MAKE SURE YOU ARE IN THE SCREEN YOU THINK YOU ARE

otherwise you will get trash data...
For example, you can check a screen title using getScreenBlock(coords) and if it matches what you expect, then proceed with getScreenObjects()
Or.. you might want to simply add the title as an Object to the Screen definitions, and if after getScreenObjects() it doesn't match,
then simply ignore the results.

OBJECTS:
	There are basically 3 types of Objects up to date.

	TABLE   A bidimensional table holding the data in rows and columns
	BLOCK   A square string block, in which when you have multiple rows, you have a string starting at (rs,cs) and ending at (re,ce) and
	        the middle rows will be taken only from column cs to ce. Just like this:
		        ··················
		        ·······OOOO·······
		        ·······OOOO·······
		        ·······OOOO·······
		        ··················

	STRING	A continuous string, in which when you have multiple rows, you have a continuous string starting at (rs,cs) and ending at (re,ce) and
			the middle rows will be complete and not restricted by column. Just like this:
		        ··················
		        ·······OOOOOOOOOOO
		        OOOOOOOOOOOOOOOOOO
		        OOOOOOOOOOO·······
		        ··················

STRUCTURE:
    1- First dimension of the array, is the name of the Screen. You set this name to whatever you wish, to
       diferenciate the screen, so you can have many screens (all what is needed from a system) in a single file
       easy to manipulate when they change your coords.
       Note: this name is not part of the protocol, so YOU set it up here, and use it in your code. However in many systems,
             you have in a corner of the screen a unique name which identifies which screen you are viewing, so you can use that if you wish.
    2- Second dimension is the name of the Object you are defining, you can use whatever name you wish
    3- Third dimension is the parameters for the Object, according to the type, you will need different parameters
    4- Fourth dimension (only for parameters 'cols' and 'rows'), is the name of the Named Cols or Rows, you can use whatever you wish.
    5- Fifth dimension (only for parameters 'cols' and 'rows'), is the parameters for the col/row (usually either cs, ce or rs, re)

	IMPORTANT: KNOW WELL WHICH ONES ARE NAMES, AND WHICH ONES ARE PARAMETERS

	Example:
		$aDefs['MyScreen']['MyField']['cols']['MyCol']['cs'] = 1;

FINAL NOTES:

	1- Remember to put this Screen Definitions in the ScreenDefinitions property of the VT100 object.
	2- Keep in mind that this is an example, and that as long as you have this structure you can save it in whatever fashion you like,
	for example:
		a JSON string and then assign $oVT00->ScreenDefinition = json_decode($sMyDefs);
		a php serialized with serialize() string.... or some XML serialization.
		a more compact php definition like array('MyScreen'=>array('MyField'=>array('type'=>'STRING', 'cs'=>1 ....)))
		etc etc etc
	3- When you have a string (not a table) you wish to obtain, which is in a single row, then using BLOCK is better than using STRING.
	4- When you have 'rows' which start and end in the same row coord, you don't need to set up 're', actually you don't need 'rs'
	   and 're' at all, you may simple set the row number and that's it, which produces a more compact definition
	5- When you make the assigment to the property ScreenDefinitions it is better either to do it byref
		$oVT100->ScreenDefinitions =& $aDefs;
	   Or obviously to not use an intermediate $aDefs and create everything directly in the property.
	6- When dealing with coordinates, remember that the origin is 0,0 because we use php arrays as Virtual Screen
	7- rs,re,cs,ce are INCLUSIVE, so if you use cs=8 and ce=10, the string will be 3 characters long, that is cols 8, 9 and 10.

*******************************************************************************************************************/


$aDefs = array();

// This is the definition for a given Screen, you can have many Screens defined all at once, and then only use the one you wish
$aDefs['Report'] = array();

// Object Type: TABLE
//   This produces a bidimensional table holding the data
//   This requires at minimum the 'cols' parameter and either 'rows' or 'rowStart' and 'rowEnd'
$aDefs['Report']['results'] = array();
$aDefs['Report']['results']['type']        = 'TABLE';
	// Table Orientation: VERTICAL/HORIZONTAL (If ommited it is HORIZONTAL)
	//   VERTICAL   records are presented as columns
	//   HORIZONTAL records are presented as rows
	$aDefs['Report']['results']['orientation'] = 'VERTICAL';
	// Named columns (Required)
	//   for each named column (the name can also be an int index if you wish)
	//   either an integer specifying the single column
	//   or an array with parameters:
	//     cs Column Start (required)
	//     ce Column End   (optional)
	$aDefs['Report']['results']['cols']      = array();
	$aDefs['Report']['results']['cols'][0]   = array('cs'=>30, 'ce'=>36);
	$aDefs['Report']['results']['cols'][1]   = array('cs'=>38, 'ce'=>44);
	$aDefs['Report']['results']['cols'][2]   = array('cs'=>46, 'ce'=>52);
	$aDefs['Report']['results']['cols'][3]   = array('cs'=>54, 'ce'=>60);
	$aDefs['Report']['results']['cols'][4]   = array('cs'=>62, 'ce'=>68);
	// Named rows
	//   for each named row (the name can also be an int index if you wish)
	//   either an integer specifying the row
	//   or an array with parameters:
	//     rs Row Start (required)
	//     re Row End   (optional)
	$aDefs['Report']['results']['rows']      = array();
	$aDefs['Report']['results']['rows']['split']                = 5;
	$aDefs['Report']['results']['rows']['wait_calls']           = 7;
	$aDefs['Report']['results']['rows']['wait_oldest']          = 8;
	$aDefs['Report']['results']['rows']['avg_answer_speed']     = 9;
	$aDefs['Report']['results']['rows']['acd_calls']            = 11;
	$aDefs['Report']['results']['rows']['acd_avg_talk']         = 12;
	$aDefs['Report']['results']['rows']['acd_abandoned']        = 13;
	$aDefs['Report']['results']['rows']['acd_avg_time2abandon'] = 14;
	$aDefs['Report']['results']['rows']['agents_available']     = 16;
	$aDefs['Report']['results']['rows']['agents_ringing']       = 17;
	$aDefs['Report']['results']['rows']['agents_acd_calls']     = 18;
	$aDefs['Report']['results']['rows']['agents_after_call']    = 19;
	$aDefs['Report']['results']['rows']['agents_other']         = 20;
	$aDefs['Report']['results']['rows']['agents_aux']           = 21;
	$aDefs['Report']['results']['rows']['agents_staffed']       = 22;

// Object Type: BLOCK
//   This produces a string block
//   When you have multiple rows, you have a string starting at (rs,cs) and ending at (re,ce) and
//   the middle rows will be taken only from column cs to ce.
//   Note: If you have a single row, the result is exactly the same as type=STRING, but this type is preferred because it is less CPU intensive
//   Note: When you need a single string, you almost always need BLOCK instead of STRING
$aDefs['Report']['username'] = array();
$aDefs['Report']['username']['type'] = 'BLOCK';
	// rs Row Start (Required)
	$aDefs['Report']['username']['rs']   = 4;
	// re Row End (Optional, if ommited or non integer rs is assumed instead)
	// Note: re < rs is indication of the order in which the string is processed, so it would flip the rows vertically
	$aDefs['Report']['username']['re']   = 4;
	// cs Column Start (Required)
	$aDefs['Report']['username']['cs']   = 70;
	// ce Column End (Optional, if ommited or non integer cs is assumed instead)
	// Note: ce < cs is unacceptable, so the params get automatically reversed
	$aDefs['Report']['username']['ce']   = 90;

// Object Type: STRING
//   This produces a continuous string
//   When you have multiple rows, you have a continuous string starting at (rs,cs) and ending at (re,ce) and
//   the middle rows will be complete and not restricted by column.
$aDefs['Report']['example'] = array();
$aDefs['Report']['example']['type'] = 'STRING';
	// rs Row Start (Required)
	$aDefs['Report']['example']['rs']   = 5;
	// re Row End (Optional, if ommited or non integer rs is assumed instead)
	// Note: re < rs is indication of the order in which the string is processed, so it would flip the rows vertically
	$aDefs['Report']['example']['re']   = 19;
	// cs Column Start (Required)
	$aDefs['Report']['example']['cs']   = 30;
	// ce Column End (Optional, if ommited or non integer cs is assumed instead)
	// Note: ce < cs is acceptable
	$aDefs['Report']['example']['ce']   = 20;
