<?php

class templateclass extends ModelBase implements SessionHandlerInterface
{
	public static $DESC		=	[
		'flags'					=>	0,
		'fields'				=>	[
			'id'							=>	['VARCHAR(255) PRIMARY KEY'],
			'userid'					=>	['INT NOT NULL'],
			'useragentid'			=>	['INT NOT NULL'],
			'ip'							=>	['SINGLETEXT NOT NULL'],
			'data'						=>	['MULTITEXT NOT NULL'],
			'start'						=>	['INT NOT NULL'],
			'lastvisited'			=>	['INT NOT NULL'],
			'numpagesvisited'	=>	['INT16 NOT NULL'],
			'length'					=>	['INT NOT NULL'],
			'flags'						=>	['INT NOT NULL'],
		],
	];

	const	ACTIVE	=	0x01;
	const	EXPIRED	=	0x02;

	// ------------------------------------------------------------------
	public function __construct($db = null)
	{
		$this->msg				=	'';
		$this->firstview	=	1;
		$this->needsave		= 0;

		if ($db)
		{
			$this->_db	=	$db;

			foreach (self::$DESC['fields'] as $k => $v)
				$this->$k	=	null;

			session_set_save_handler($this, true);
			session_start();
		}
	}

	// ------------------------------------------------------------------
	public function open($path, $sessionname)
	{
		return true;
	}

	// ------------------------------------------------------------------
	public function close()
	{
		return true;
	}

	// ------------------------------------------------------------------
	public function read($id)
	{
		$t	=	time();

		try
		{
			$this->_db->Load(get_class($this), $id, '*', $this);

			$timeout	=	$this->_db['sessiontimeout'] 
				? $this->_db['sessiontimeout'] 
				: 60 * 60 * 24 * 7;

			if ($this->lastvisited + $timeout < $t || $this->flags & self::EXPIRED)
			{
				$this->flags	=	self::EXPIRED;
				return '';
			} else if ($t > $this->lastvisited + 60)
			{
				$this->needsave	=	1;
				$this->Set(['lastvisited'	=>	$t]);
			}
			
			$this->firstview	=	0;

			return $this->data;
		} catch (Exception $e)
		{
			// Probably failed becaused it's a new session. Uncomment to see other errors.
			#echo 'Teach_Problem loading session: ' . $e;
		}

		$useragent			=	$_SERVER['HTTP_USER_AGENT'];
		$useragentid		=	0;

		$results	=	$this->_db->Query("
			SELECT suid1 
			FROM h_useragents 
			WHERE useragent = ?", 
			$useragent
		)->fetch();

		if ($results)
		{
			$useragentid	=	$results['suid1'];
		} else
		{
			$useragentid	=	$this->_db->Create('h_useragent')
				->Set([
					'useragent'		=>	$useragent,
				])
				->Insert()->suid1;
		}

    if (V($_SERVER, 'HTTP_CLIENT_IP'))   //check ip from share internet
    {
      $ip	=	$_SERVER['HTTP_CLIENT_IP'];
    } elseif (V($_SERVER, 'HTTP_X_FORWARDED_FOR'))   //to check ip is pass from proxy
    {
      $ip	=	$_SERVER['HTTP_X_FORWARDED_FOR'];
    } else
    {
      $ip	=	$_SERVER['REMOTE_ADDR'];
    }

		$this->Set([
			'userid'					=>	0,
			'useragentid'			=>	$useragentid,
			'ip'							=>	$ip,
			'start'						=>	$t,
			'lastvisited'			=>	$t,
			'length'					=>	0,
			'flags'						=>	self::ACTIVE,
		]);

		return '';
	}

	// ------------------------------------------------------------------
	public function write($id, $data)
	{
		if ($data == $this->data && !$this->needsave)
			return true;
	
		$this->_db->Begin();	
		$this->Set([
			'id'		=>	$id,
			'data'	=>	$data
		])->Replace();
		$this->_db->Commit();
		return true;
	}

	// ------------------------------------------------------------------
	public function destroy($id)
	{
		$params = session_get_cookie_params();
		setcookie(
			session_name(),
			'',
			1,
			$params["path"],
			$params["domain"],
			$params["secure"],
			$params["httponly"]
		);
		$model	=	get_class($this);
		$this->_db->Query("UPDATE {$model::$DESC['table']} SET flags = ?, length = ? WHERE id = ?",
			[self::EXPIRED, $this->lastvisited - $this->start, $id]
		);
		$this->flags	=	self::EXPIRED;
		return true;
	}

	// ------------------------------------------------------------------
	public function gc($maxlifetime)
	{
		$table	=	self::$DESC['table'];

		// Get rid of sessions older than three months
		$this->_db->Query("DELETE FROM {$table} WHERE lastvisited < ?",
			time() - (60 * 60 * 24 * 30 * 3)
		);
		
		$timeout	=	$this->_db['sessiontimeout'] ? $this->_db['sessiontimeout'] : 1200;
		
		$this->_db->Query("UPDATE {$table} SET flags = ?, length = lastvisited - start WHERE lastvisited < ?",
			[self::EXPIRED, time() - $timeout]
		);
		
		// Get rid of old uploads
		try
		{
			list($dirs, $files)	=	FileDir('uploads')->Scan('', PFDir::ONLY_FILES);
			$cachelimit	=	time() - (60 * 60 * 24);
			
			foreach ($files as $f)
			{
				$fullpath	=	'uploads/' . $f;
				$mtime		= filemtime($fullpath);
				
				if ($mtime < $cachelimit)
					@unlink($fullpath);
			}
		} catch (Exception $e)
		{
		}
		return true;
	}
}
