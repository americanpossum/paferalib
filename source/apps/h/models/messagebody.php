<?php

class templateclass extends ModelBase
{
	public static	$DESC		=	[
		'numsuids'					=>	1,
		'flags'							=>	DB::SECURE | DB::TRACK_CHANGES,
		'fields'						=>	[
			'title'						=>	['SINGLETEXT NOT NULL'],
			'message'					=>	['MULTITEXT NOT NULL'],
			'senttime'				=>	['DATETIME NOT NULL'],
		],
	];
}
