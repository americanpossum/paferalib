<?php

// ====================================================================
// code point to UTF-8 string
function unichr($i) 
{
	return iconv('UCS-4LE', 'UTF-8', pack('V', $i));
}

// ====================================================================
// UTF-8 string to code point
function uniord($s) 
{
	return unpack('V', iconv('UTF-8', 'UCS-4LE', $s))[1];
}

# ====================================================================
# Makes a statistical guess of what language $s is.
#
# Returns a IETF language code such as en or ru.
function GuessLanguage($s)
{
	$latin			= 0;
	$han				= 0;
	$cyrillic	= 0;

	// First perform character set analysis
	for ($i = 0, $l = count($s); $i < $l; $i++)
	{
		$c	= uniord($s[i]);
	
		if (65 <= $c && $c <= 122)
		{
			$latin++;
		} else if (
			(0x400 <= $c && $c <= 0x52f)
			|| (0x2de0 <= $c && $c <= 0x2dff)
			|| (0xa640 <= $c && $c <= 0xa69f)
			|| (0x1c80 <= $c && $c <= 0x1c8f)
		)
		{
			$cyrillic++;
		} else if (0x4e00 <= $c && $c <= 0x9fff)
		{
			$han++;
		}
	}

	$sets	= [
		['latin'			=> $latin],
		['han'				=> $han],
		['cyrillic' 	=> $cyrillic],
	];

	$sets	= array_sort(
		function($a, $b) 
		{
			return $b[1] - $a[1];
		}
	);
	
	switch ($sets[0][0])
	{
		case 'latin':
			return 'en';
		case 'cyrillic':
			return 'ru';
	};	
	
	return 'zh';
}

