<?php

class templateclass extends ModelBase
{
	public static	$DESC		=	[
		'numsuids'					=>	1,
		'flags'							=>	DB::SECURE | DB::TRACK_CHANGES,
		'fields'						=>	[
			'title'						=>	['SINGLETEXT NOT NULL'],
			'modified'				=>	['DATETIME NOT NULL'],
			'size'						=>	['INT64 NOT NULL'],
			'flags'						=>	['INT NOT NULL'],
		],
	];
}
