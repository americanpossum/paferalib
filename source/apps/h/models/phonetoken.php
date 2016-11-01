<?php

class templateclass extends ModelBase
{
	public static	$DESC		=	[
		'flags'							=>	DB::SECURE | DB::TRACK_CHANGES,
		'uniqueids'					=>	['phoneid'],
		'fields'						=>	[
			'phoneid'				=>	['SINGLETEXT NOT NULL'],
			'model'					=>	['SINGLETEXT NOT NULL'],
			'product'				=>	['SINGLETEXT NOT NULL'],
			'userid'				=>	['INT NOT NULL'],
			'expires'				=>	['INT NOT NULL'],
			'flags'					=>	['INT NOT NULL'],
		],
	];
}
