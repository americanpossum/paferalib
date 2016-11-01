<?php

/**********************************************************************
Class for storing access control lists.

Note that acl is actually JSON, but typed as SINGLETEXT to take 
advantage of text indexing and to avoid automatic decoding. It should
only be decoded by the DB::GetACL() function.

It's possible to have the same ACL represented as multiple different
JSON encodings because the order of the elements are not the same.
This has been accepted as a necessary evil to avoid the overhead of
multidimensional array comparisons.
**********************************************************************/
class templateclass extends ModelBase
{
	public static $DESC		=	[
		'flags'				=>	DB::TRACK_CHANGES,
		'uniqueids'		=>	['acl'],
		'numsuids'		=>	1,
		'fields'			=>	[
			'acl'				=>	['SINGLETEXT'],
		],
	];
}
