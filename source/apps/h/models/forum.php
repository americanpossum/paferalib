<?php

/* *******************************************************************
 * 
 *********************************************************************/
class templateclass extends ModelBase
{
	public static	$DESC		=	[
		'numsuids'					=>	1,
		'flags'							=>	DB::SECURE | DB::TRACK_CHANGES,
		'fields'						=>	[
			'uri'							=>	['SINGLETEXT NOT NULL'],
			'title'						=>	['SINGLETEXT NOT NULL'],
			'description'			=>	['SINGLETEXT NOT NULL'],
			'parentid'				=>	['INT NOT NULL'],
			'threads'					=>	['INT NOT NULL'],
			'posts'						=>	['INT NOT NULL'],
			'flags'						=>	['INT NOT NULL'],
		],
	];
}
