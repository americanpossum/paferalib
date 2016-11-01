<?php

class templateclass extends ModelBase
{
	public static $DESC		=	[
		'flags'					=>	DB::SECURE | DB::TRACK_CHANGES,
		'uniqueids'			=>	['groupname'],
		'fields'				=>	[
			'groupname'		=>	['SINGLETEXT NOT NULL'],
			'translated'	=>	['TRANSLATION NOT NULL'],
			'flags'				=>	['INT NOT NULL'],
		],
		'numsuids'			=>	1,
	];

	// ------------------------------------------------------------------
	public function Users()
	{
		$ls	=	[];
		
		foreach ($this->db->Linked($this, 'User') as $u)
			$ls[$u->id]	=	$u->username;

		return $ls;
	}

	// ------------------------------------------------------------------
	public function Name()
	{
		if ($this->translated)
			return $this->translated;
			
		return $this->groupname;
	}
}
