<?php

/* *******************************************************************
 * Base class for website messages and forums.
 *
 * Provides for sending one message with many translations, 
 * threaded conversations, and SMS conversations with the Android app
 * installed.
 *********************************************************************/
class templateclass extends ModelBase
{
	public static	$DESC		=	[
		'numsuids'					=>	1,
		'flags'							=>	DB::SECURE | DB::TRACK_CHANGES,
		'fields'						=>	[
			'uri'							=>	['SINGLETEXT NOT NULL'],
			'threadid'				=>	['INT NOT NULL'],
			'parentid'				=>	['INT NOT NULL'],
			'fromid'					=>	['INT NOT NULL'],
			'toid'						=>	['INT NOT NULL'],
			'bodyid'					=>	['INT NOT NULL'],
			'readtime'				=>	['DATETIME NOT NULL'],
			'ups'							=>	['INT NOT NULL'],
			'downs'						=>	['INT NOT NULL'],
			'bounty'					=>	['INT NOT NULL'],
			'bountyids'				=>	['INT NOT NULL'],
			'replies'					=>	['INT NOT NULL'],
			'views'						=>	['INT NOT NULL'],
			'flags'						=>	['INT NOT NULL'],
		],
	];

	const	SEND_SMS			=	0x1;
	const	SENT_SMS			=	0x2;
	const	UNREAD				=	0x4;
	const	DRAFT					=	0x8;
	const	IMPORTANT			=	0x10;
}
