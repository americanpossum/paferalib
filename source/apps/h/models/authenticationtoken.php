<?php

class templateclass extends ModelBase
{
	public static	$DESC		=	[
		'flags'							=>	DB::SECURE | DB::TRACK_CHANGES,
		'uniqueids'					=>	['token'],
		'fields'						=>	[
			'token'					=>	['SINGLETEXT NOT NULL'],
			'userid'				=>	['INT NOT NULL'],
			'expires'				=>	['INT NOT NULL'],
			'flags'					=>	['INT NOT NULL'],
		],
	];

	const	SINGLE_USE	=	0x01;

	// ------------------------------------------------------------------
	public function OnSave($db, $fields)
	{
		if (!V($fields, 'token'))
			$fields['token']	=	uuid4();
	}
}
