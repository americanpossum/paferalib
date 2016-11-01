<?php

class templateclass extends ModelBase
{
	const FIREFOX	=	1;
	const	IE			=	2;
	const	CHROME	=	3;
	const	SAFARI	=	4;
	const	OPERA		=	5;
	const OTHER		= 255;

	public static $DESC	=	[
		'uniqueids'		=>	['useragent'],
		'numsuids'		=>	1,
		'fields'			=>	[
			'useragent'			=>	['MULTITEXT NOT NULL'],
			'browser'				=>	['INT NOT NULL'],
		],
	];
}

