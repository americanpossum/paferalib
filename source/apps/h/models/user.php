<?php

class templateclass extends ModelBase
{
	public static	$DESC		=	[
		'flags'			=>	DB::SECURE | DB::TRACK_CHANGES,
		'numsuids'	=>	1,
		'uniqueids'	=>	['phonenumber', 'place'],
		'fields'		=>	[
			'phonenumber'		=>	['SINGLETEXT NOT NULL'],
			'place'					=>	['SINGLETEXT NOT NULL'],
			'password'			=>	['PASSWORD PRIVATE NOT NULL'],
			'username'			=>	['TRANSLATION NOT NULL'],
			'email'					=>	['SINGLETEXT NOT NULL'],
			'address'				=>	['TRANSLATION NOT NULL'],
			'city'					=>	['TRANSLATION NOT NULL'],
			'state'					=>	['TRANSLATION NOT NULL'],
			'country'				=>	['TRANSLATION NOT NULL'],
			'postalcode'		=>	['SINGLETEXT NOT NULL'],
			'realname'			=>	['TRANSLATION NOT NULL'],
			'latitude'			=>	['SINGLETEXT NOT NULL'],
			'longitude'			=>	['SINGLETEXT NOT NULL'],
			'orientation'		=>	['SINGLETEXT NOT NULL'],
			'wallpaper'			=>	['SINGLETEXT NOT NULL'],
			'texttheme'			=>	['SINGLETEXT NOT NULL'],
			'homepage'			=>	['SINGLETEXT NOT NULL'],
			'language'			=>	['INT NOT NULL'],
			'quota'					=>	['INT NOT NULL'],
			'balance'				=>	['INT NOT NULL'],
			'flags'					=>	['INT NOT NULL'],
		],
	];

	const	DISABLED							=	0x01;
	const	MUST_CHANGE_PASSWORD	=	0x02;

	// ------------------------------------------------------------------
	public function HashPassword($password, $salt = '')
	{
		if (!$salt)
		{
			$salt = '$2a$08$';

			for ($i = 0; $i < 22; $i++)
				$salt .= ALNUM[mt_rand(0, 61)];
		}

		return crypt($password, $salt) . $salt;
	}

	// ------------------------------------------------------------------
	function CheckPassword($password)
	{
		$salt		=	substr($this->password, 60);
		$hashed	=	crypt($password, $salt) . $salt;
		
		return $hashed == $this->password;
	}

	// ------------------------------------------------------------------
	public function Set($values)
	{
		if (V($values, 'password'))
		{
			$values['password']	=	$this->HashPassword($values['password']);
		} else
		{
			unset($values['password']);
		}

		parent::Set($values);
	}

	// ------------------------------------------------------------------
	public function Groups()
	{
		$ls	=	[];

		foreach ($this->Linked('h_group') as $g)
			$ls[$g->suid1]	=	$g->groupname;

		return $ls;
	}

	// ------------------------------------------------------------------
	public function Name()
	{
		if ($this->username_translated)
			return $this->username_translated;
		
		if ($this->realname_translated)
			return $this->realname_translated;
		
		return '*' . substr($this->phonenumber, -6) . '*';
	}
	
	// ------------------------------------------------------------------
	public static function HeadshotFile($userid)
	{
		$shortcode	=	ToShortCode($userid);
		return 'data/h/headshots/' . substr($shortcode, 0, 3) . '/' . substr($shortcode, 3) . '.jpg';
	}
	
	// ------------------------------------------------------------------
	public static function HeadshotURL($userid)
	{
		global $R;
		
		$headshotfile	=	self::HeadshotFile($userid);
		
		if (file_exists($headshotfile))
			return $R->baseurl . $headshotfile;
		
		return $R->IconURL('user', 'h');
	}
}
