<?php

/**********************************************************************
 * Easy and quick translatable FAQ class, but the implementation 
 * is a little quirky in order to save space and have flexibility.
 *
 * Since topic itself is a translation, you need to first look up 
 * the topic that you want in the translation table to get its textid,
 * and then use that textid to find the rest of the entries.
 *
 * Section can be whatever you want, but I normally use the 
 * dot format as in 1.2.13 or so forth. This is also a translation
 * since different areas have different standards as to organization.
 *
 * Whatever the first section number is, that will be used as the 
 * title and description of the FAQ.
 **********************************************************************/
class templateclass extends ModelBase
{
	public static	$DESC		=	[
		'flags'			=>	DB::SECURE | DB::TRACK_CHANGES,
		'uniqueids'	=>	['topic', 'section'],
		'fields'		=>	[
			'topic'				=>	['TRANSLATION NOT NULL'],
			'section'			=>	['TRANSLATION NOT NULL'],
			'question'		=>	['TRANSLATION NOT NULL'],
			'answer'			=>	['TRANSLATION NOT NULL'],
			'viewed'			=>	['INT NOT NULL'],
			'flags'				=>	['INT NOT NULL'],
		],
	];
	
}
