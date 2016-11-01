<?php

class templateclass extends ModelBase
{
	public static	$DESC		=	[
		'flags'			=>	0,
		'uniqueids'	=>	['phonenumber', 'place'],
		'fields'		=>	[
			'phonenumber'		=>	['TEXT NOT NULL'],
			'place'					=>	['TEXT NOT NULL'],
			'timestamp'			=>	['INT32 NOT NULL'],
			'ipaddress'			=>	['INT32 NOT NULL'],
			'flags'					=>	['INT32 NOT NULL'],
		],
	];
}
