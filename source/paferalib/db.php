<?php

include_once('paferalib/modelbase.php');
include_once('paferalib/dbresult.php');

/* ********************************************************************
 * Bootstrap class for the database.
 */
class h_dbtype extends ModelBase
{
	public static $DESC		=	[
		'flags'				=>	DB::TRACK_CHANGES,
		'uniqueids'		=>	['typename'],
		'numsuids'		=>	1,
		'fields'			=>	[
			'typename'		=>	['TEXT NOT NULL'],
			'access'			=>	['INT NOT NULL'],
			'acl'					=>	['JSON NOT NULL'],
			'flags'				=>	['INT NOT NULL'],
		],
	];
}

/* ********************************************************************
 * Bootstrap class for the database.
 */
class h_config extends ModelBase
{
	public static $DESC		=	[
		'flags'				=>	DB::TRACK_CHANGES,
		'id'					=>	'dbkey',
		'fields'			=>	[
			'dbkey'				=>	['TEXT NOT NULL'],
			'dbvalue'			=>	['SINGLETEXT'],
		],
	];
}

/* ********************************************************************
 * Bootstrap class for the database.
 */
class h_changelog extends ModelBase
{
	const CREATED		=	1;
	const MODIFIED	=	2;
	const DELETED		=	3;
	const RESTORED	=	4;
	const	ACCESSED	=	5;

	public static $DESC	=	[
		'fields'				=>	[
			'id'					=>	['INTEGER PRIMARY KEY'],
			'objtype'			=>	['INT NOT NULL'],
			'eventtype'		=>	['ENUM NOT NULL',
				'',
				[
					'Created'		=>	h_changelog::CREATED,
					'Modified'	=>	h_changelog::MODIFIED,
					'Deleted'		=>	h_changelog::DELETED,
					'Restored'	=>	h_changelog::RESTORED,
					'Accessed'	=>	h_changelog::ACCESSED,
				]
			],
			'ids'					=>	['JSON NOT NULL'],
			'userid'			=>	['INT NOT NULL'],
			'eventtime'		=>	['TEXT NOT NULL'],
			'changed'			=>	['JSON NOT NULL'],
		],
		'indexes'				=>	[
			['INDEX', 'ids'],
		],
	];
}

/* ********************************************************************
 * Bootstrap class for the database.
 */
class h_translation extends ModelBase
{
	public static $DESC	=	[
		'flags'				=> 0,
		'numsuids'		=> 1,
		'fields'			=> [
			'translations'	=>	['JSON NOT NULL'],
		],
	];
	public static $INDEXES	=	[
		['INDEX', 'translations'],
	];
}

/* ********************************************************************
 * Bootstrap class for the database.
 */
class h_tag extends ModelBase
{
	public static $DESC	=	[
		'flags'				=>	0,
		'uniqueids'		=>	['tagname', 'languageid'],
		'numsuids'			=>	1,
		'fields'			=>	[
			'tagname'			=>	['SINGLETEXT NOT NULL'],
			'languageid'	=>	['INT NOT NULL'],
		],
	];
}

/* ********************************************************************
*/
class DB implements ArrayAccess
{
	// Security access constants
	const CAN_CREATE			=	0x1;
	const CANNOT_CREATE		=	0x2;
	const CAN_CHANGE			=	0x4;
	const CANNOT_CHANGE		=	0x8;
	const CAN_DELETE			=	0x10;
	const CANNOT_DELETE		=	0x20;
	const CAN_VIEW				=	0x40;
	const CANNOT_VIEW			=	0x80;
	const	CAN_LINK				=	0x100;
	const	CANNOT_LINK			=	0x200;
	const	CAN_SECURE			=	0x400;
	const	CANNOT_SECURE		=	0x800;
	
	const	CAN_VIEW_PROTECTED		=	0x1000;
	const	CANNOT_VIEW_PROTECTED	=	0x2000;

	const CAN_ALL				=	0x555;
	const CANNOT_ALL				=	0x555;
	
	const DEBUG					=	0x01;
	const	TRACK_CHANGES	=	0x02;
	const	TRACK_VALUES	=	0x04;
	const	TRACK_VIEW		=	0x08;
	const SECURE				=	0x10;
	const PRODUCTION		=	0x20;

	const IDMODEL				=	0x01;
	const UNIQUEMODEL		=	0x02;
	const SUIDMODEL			=	0x03;
	const PLAINMODEL		=	0x04;

	// ------------------------------------------------------------------
	public function __construct(
		$dbtype,
		$dbname,
		$flags	=	DB::SECURE,
		$username	=	'',
		$password	=	'',
		$dbhost = '127.0.0.1',
		$port = 3306
	)
	{
		$this->flags					=	$flags;
		$this->userid					=	1;
		$this->sudoids				=	[];
		$this->groups					=	[1];
		$this->dbaccess				=	self::CAN_CREATE | self::CAN_CHANGE | self::CAN_DELETE | self::CAN_VIEW;
		$this->language				=	1;
		$this->langcode				=	'';
		$this->types					=	[];
		$this->translations		=	[];
		$this->acls						=	[];
		$this->values					=	[];

		$this->transactionlevel	=	0;

		switch ($dbtype)
		{
			case 'sqlite':
				$this->dbconn	=	new PDO('sqlite:' . $dbname);
				break;
			case 'mysql':
/*				$this->dbconn	=	new PDO(
					"mysql:host=localhost;port=3306;dbname=ptccnf_possumenglish;charset=utf8",
					'ptccnf_ptccnf',
					'LetMeIn232629'
				);*/
				$this->dbconn	=	new PDO(
					"mysql:host={$dbhost};port={$port};dbname={$dbname};charset=utf8",
					$username,
					$password
				);
				$this->dbconn->exec('SET collation_connection = utf8_general_ci');
				$this->dbconn->exec('SET character_set_client = utf8');
				$this->dbconn->exec('SET NAMES UTF8');
				#$this->dbconn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
				break;
			default:
				throw new Exception("Unsupported database type {$dbtype}");
		};

		$this->dbtype	=	$dbtype;
		$this->dbconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		try
		{
			foreach ($this->Query("SELECT * FROM h_dbtypes") as $r)
			{
				$this->types[$r['typename']]	=	$r;
			}
		} catch (Exception $e)
		{
		}
		
		$this->Register('h_dbtype');
		$this->Register('h_changelog');
		$this->Register('h_config');
		$this->Register('h_translation');
		$this->Register('h_tag');
	}

	// ------------------------------------------------------------------
	public function InitDesc($model)
	{
		if (!class_exists($model))
			throw new Exception($model . ' has not been registered!');
	
		try
		{
			if (!array_key_exists('table', $model::$DESC))
				$model::$DESC['table']	=	strtolower($model) . 's';
		} catch (Exception $e)
		{
			return;
		}

		if (!array_key_exists('id', $model::$DESC))
		{
			if (array_key_exists('id', $model::$DESC['fields']))
			{
				$model::$DESC['id']	=	'id';
			} else
			{
				$model::$DESC['id']	=	'';
			}
		}

		if (!array_key_exists('uniqueids', $model::$DESC))
			$model::$DESC['uniqueids']	=	[];

		if (!array_key_exists('numsuids', $model::$DESC))
			$model::$DESC['numsuids']	=	0;

		if (!array_key_exists('flags', $model::$DESC))
			$model::$DESC['flags']	=	0;
	}

	// ------------------------------------------------------------------
	public function Sudo($id = 1)
	{
		if (!$id)
			$id	=	1;
	
		array_push($this->sudoids, $this->userid);
		$this->userid	=	$id;
		return $this;
	}

	// ------------------------------------------------------------------
	public function UnSudo()
	{
		$this->userid	=	array_pop($this->sudoids);
		return $this;
	}

	// ------------------------------------------------------------------
	// Return the name of the model given its ID
	public function FindType($typeid)
	{
		foreach ($this->types as $model => $row)
		{
			if ($row['suid1'] == $typeid)
				return $mode;
		}
	
		return 0;
	}

	// ------------------------------------------------------------------
	// Return the name of the model given its ID
	public function IsRegistered($model)
	{
		return in_array($model, array_keys($this->types));
	}

	// ------------------------------------------------------------------
	// Outputs debugging information for all queries
	public function Debug($b = true)
	{
		$this->flags	=	$b ? $this->flags | self::DEBUG : $this->flags & (~self::DEBUG);
		return $this;
	}

	// ------------------------------------------------------------------
	public function IsProduction()
	{
		return $this->flags & self::PRODUCTION;
	}
	
	// ------------------------------------------------------------------
	// Keep track of all changes made to flagged models
	public function TrackChanges($b = 1)
	{
		$this->flags	=	$b ? $this->flags | self::TRACK_CHANGES : $this->flags & (~self::TRACK_CHANGES);
		return $this;
	}

	// ------------------------------------------------------------------
	// Keep track of all of the values that changed in the flagged models
	public function TrackValues($b = 1)
	{
		$this->flags	=	$b ? $this->flags | self::TRACK_VALUES : $this->flags & (~self::TRACK_VALUES);
		return $this;
	}

	// ------------------------------------------------------------------
	// Keep track of everyone who access the flagged models
	// Note: The changelog can get large *very* quickly, so use with caution and
	// only with the most important models!
	public function TrackAccess($b = 1)
	{
		$this->flags	=	$b ? $this->flags | self::TRACK_VIEW : $this->flags & (~self::TRACK_VIEW);
		return $this;
	}

	// ------------------------------------------------------------------
	// Enable basic access permissions and ACLs for flagged models
	public function UseSecurity($b = 1)
	{
		$this->flags	=	$b ? $this->flags | self::SECURE : $this->flags & (~self::SECURE);
		return $this;
	}

	// ------------------------------------------------------------------
	public function GetPermissions($o)
	{
		$acls		=	$this->GetACL($o->aclid);
		
		$users	=	[];
		$groups	=	[];
		
		if ($acls)
		{
			$users	=	V($acls, 'users') ? $acls['users'] : [];
			$groups	=	V($acls, 'groups') ? $acls['groups'] : [];
		} 
	
		return [
			'owner'		=>	$o->dbowner ? $o->dbowner : 1,
			'access'	=>	$o->dbaccess,
			'users'		=>	$users,
			'groups'	=>	$groups,
		];
	}
	
	// ------------------------------------------------------------------
	public function SetPermissions($o, $owner, $access, $users, $groups)
	{
		$o->dbowner		=	$owner ? $owner : 1;		
		$o->dbaccess	=	$access;
		$o->aclid			=	0;
		
		if ($users || $groups)
		{
			$acl	=	[
				'users'		=>	$users,
				'groups'	=>	$groups,
			];
			
			foreach ($this->acls as $aclid => $desc)
			{
				if ($acl == $desc[0])
				{
					$o->aclid	=	$aclid;
					break;
				}
			}
			
			if (!$o->aclid)
			{
				$encoded	=	json_encode($acl);
				
				$results	=	$this->Query('
					SELECT suid1
					FROM h_acls
					WHERE acl = ?',
					$encoded
				)->fetch();
				
				$o->aclid	=	$results
					? $results['suid1']
					: $this->Create('h_acl')->Set(['acl' => $encoded])->Insert()->suid1;
					
				$this->acls[$o->aclid]	=	[$encoded, $acl];
			}
		}
		
		$o->OnSetPermissions($this, $owner, $access, $o->aclid);
		
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function Query($query, $params = [])
	{
		if ($this->flags & self::DEBUG)
		{
			echo 'Query: ' . var_export($query, True) . "\nParams: " . var_export($params, True) . "\n";
		}

		if (!is_array($params))
		{
			$params	=	$params ? [$params] : [];
		}

		$s	=	$this->dbconn->prepare($query);
		$s->setFetchMode(PDO::FETCH_ASSOC);

		foreach (array_values($params) as $p)
		{
			if (is_array($p))
			{
				debug_print_backtrace();
				throw new Exception('DB.Query: Param is array!');
			}
		}
		
		try
		{
			$s->execute(array_values($params));
		} catch (Exception $e)
		{
			ob_start();
			debug_print_backtrace();
			$trace	=	ob_get_clean();
			$msg		=	$e->getMessage();
			$params	=	var_export(array_values($params), true);
			throw new Exception("Problem executing query
			[{$query}]
			with params
			[{$params}]
			{$msg}
			{$trace}
			");
		}
		
		return $s;
	}

	// ------------------------------------------------------------------
	public function Begin()
	{
		$this->transactionlevel++;

		if ($this->flags & self::DEBUG)
			echo "<pre>BEGIN db{$this->transactionlevel}</pre>";

		if ($this->transactionlevel == 1)
		{
			$this->dbconn->beginTransaction();
			
			if ($this->dbtype == 'mysql')
				$this->dbconn->exec("SET autocommit = 0");
		} else
		{/*
			switch ($this->dbtype)
			{
				case 'sqlite':
				case 'mysql':
					$this->dbconn->exec("SAVEPOINT db{$this->transactionlevel}");
					break;
				default:
					$this->dbconn->beginTransaction();
			};*/
		}
		return $this;
	}

	// ------------------------------------------------------------------
	public function Commit()
	{
		if ($this->flags & self::DEBUG)
			echo "<pre>COMMIT db{$this->transactionlevel}</pre>";

		if ($this->transactionlevel == 1)
		{
			$this->dbconn->commit();
		} else
		{
			/*switch ($this->dbtype)
			{
				case 'sqlite':
					$this->dbconn->exec("RELEASE");
					break;
				case 'mysql':
					$this->dbconn->exec("RELEASE SAVEPOINT db{$this->transactionlevel}");
					break;
				default:
					$this->dbconn->commit();
			};*/
		}

		$this->transactionlevel--;
		return $this;
	}

	// ------------------------------------------------------------------
	public function Rollback()
	{
		if ($this->flags & self::DEBUG)
			echo "<pre>ROLLBACK db{$this->transactionlevel}</pre>";

		if ($this->transactionlevel == 1)
		{
			$this->dbconn->rollBack();
		} else
		{
			/*switch ($this->dbtype)
			{
				case 'sqlite':
				case 'mysql':
					$this->dbconn->exec("ROLLBACK TO db{$this->transactionlevel}");
					break;
				default:
					$this->dbconn->rollBack();
			};*/
		}

		$this->transactionlevel--;
		return $this;
	}

	// ------------------------------------------------------------------
	public static function Date($timestamp	=	0)
	{
		if ($timestamp)
			return date(DATE_ISO8601, $timestamp);

		return date(DATE_ISO8601);
	}

	// ------------------------------------------------------------------
	public static function Timestamp($date = '')
	{
		if ($date)
			return strtotime($date);

		return time();
	}
	
	// ------------------------------------------------------------------
	public static function ApplyAccessRule($access, $rule)
	{
		if ($rule & self::CAN_CREATE)
		{
			$access	=	($access & ~self::CANNOT_CREATE) | self::CAN_CREATE;
		} else if ($rule & self::CANNOT_CREATE)
		{
			$access	=	($access & ~self::CAN_CREATE) | self::CANNOT_CREATE;
		}

		if ($rule & self::CAN_CHANGE)
		{
			$access	=	($access & ~self::CANNOT_CHANGE) | self::CAN_CHANGE;
		} else if ($rule & self::CANNOT_CHANGE)
		{
			$access	=	($access & ~self::CAN_CHANGE) | self::CANNOT_CHANGE;
		}

		if ($rule & self::CAN_DELETE)
		{
			$access	=	($access & ~self::CANNOT_DELETE) | self::CAN_DELETE;
		} else if ($rule & self::CANNOT_DELETE)
		{
			$access	=	($access & ~self::CAN_DELETE) | self::CANNOT_DELETE;
		}

		if ($rule & self::CAN_VIEW)
		{
			$access	=	($access & ~self::CANNOT_VIEW) | self::CAN_VIEW;
		} else if ($rule & self::CANNOT_VIEW)
		{
			$access	=	($access & ~self::CAN_VIEW) | self::CANNOT_VIEW;
		}

		if ($rule & self::CAN_VIEW_PROTECTED)
		{
			$access	=	($access & ~self::CANNOT_VIEW_PROTECTED) | self::CAN_VIEW_PROTECTED;
		} else if ($rule & self::CANNOT_VIEW_PROTECTED)
		{
			$access	=	($access & ~self::CAN_VIEW_PROTECTED) | self::CANNOT_VIEW_PROTECTED;
		}

		return $access;
	}

	// ------------------------------------------------------------------
	public function ApplyAccessRules($access, $rules)
	{
		if (is_array($rules))
		{
			if (V($rules, 'groups'))
			{
				$groupaccess	=	0;

				foreach ($rules['groups'] as $groupid => $rule)
				{
					if (in_array($groupid, array_keys($this->groups)))
						$groupaccess	=	$groupaccess | $rule;
				}

				$access	=	self::ApplyAccessRule($access, $groupaccess);
			}

			if (V($rules, 'users'))
			{
				$userrules	=	$rules['users'];

				if (in_array($this->userid, array_keys($userrules)))
				{
					$access	=	self::ApplyAccessRule($access, $userrules[$this->userid]);
				}
			}
		}
		return $access;
	}

	// ------------------------------------------------------------------
	public function GetACL($aclid)
	{
		if (!$aclid)
			return '';
	
		if (array_key_exists($aclid, $this->acls))
			return $this->acls[$aclid];
			
		$r	=	$this->Query('SELECT acl
			FROM h_acls
			WHERE suid1 = ?',
			$aclid
		)->fetch();
		
		if ($r)
		{
			$acl	= json_decode($r['acl'], 1);
			$this->acls[$aclid]	=	[$r['acl'], $acl];
			return $acl;
		}
		
		return '';
	}
	
	// ------------------------------------------------------------------
	public function Access($model, $obj)
	{
		if ($this->userid == 1 
			|| (isset($_SESSION) ? in_array('admins', ArrayV($_SESSION, 'groups')) : 0)
			|| (is_object($obj) && V($obj, 'dbowner') == $this->userid)
		)
		{
			return DB::CAN_ALL;
		}
		
		$access		=	$this->dbaccess;
		
		if (in_array($model, array_keys($this->types)))
		{
			$access	=	self::ApplyAccessRule($access, $this->types[$model]['access']);
			$acl		=	$this->types[$model]['acl'];
			$access	=	$this->ApplyAccessRules($access, $acl);
		}

		if (!$obj)
			return $access;

		if (!is_array($obj))
			$obj	=	(array)$obj;

		if (array_key_exists('dbaccess', $obj))
		{
			$access	=	$this->ApplyAccessRule($access, $obj['dbaccess']);
			$acl		=	$this->GetACL($obj['aclid']);
			$access	=	$this->ApplyAccessRules($access, $acl);
		}

		return $access;
	}

	// ------------------------------------------------------------------
	public function offsetExists($key)
	{
		return in_array($key, $this->values);
	}
	
	// ------------------------------------------------------------------
	public function offsetGet($key)
	{
		if (in_array($key, $this->values))
			return $this->values[$key];
	
		$r	=	$this->Query("SELECT dbvalue FROM h_configs WHERE dbkey = ?", $key)->fetch();

		if ($r)
		{
			$value	=	$r['value'];
			$this->values[$key]	=	$value;
			return $value;
		}

		return null;
	}

	// ------------------------------------------------------------------
	public function offsetSet($key, $value)
	{
		$this->Query("INSERT OR REPLACE INTO h_configs(dbkey, dbvalue)
			VALUES(?, ?)",
			[$key, $value]
		);
		
		$this->values[$key]	=	$value;
	}

	// ------------------------------------------------------------------
	public function offsetUnset($key)
	{
		unset($this->values[$key]);
		$this->Query("DELETE FROM h_configs WHERE dbkey = ?", $key);
	}
	
	// ------------------------------------------------------------------
	public function InitData()
	{
		global $T;

		try
		{
			if ($this->Query("SELECT suid1 FROM h_users LIMIT 1")->fetch())
				return;
		} catch (Exception $e)
		{
			#echo 'Problem initializing data: ' . $e . '<br>';
		}

		$this->Begin();

		foreach ([
			'h_acl', 
			'h_authenticationtoken',
			'h_group',
			'h_message',
			'h_page',
			'h_session',
			'h_user',
			'h_useragent',
		] as $model)
		{
			$this->Register($model);
		}

		$root	=	$this->Create('h_user');
		$root->Set([
			'suid1'				=>	1,
			'phonenumber'	=>	'1',
			'username_1' 	=>	'admin',
			'place'				=>	'pafera',
			'password' 		=>	'admin',
			'wallpaper'		=>	'beach',
			'texttheme'		=>	'dark',
		]);
		$root->Insert();

		$admins	=	$this->Create('h_group')
		->Set([
			'suid1'					=>	1,
			'groupname' 		=> 'admins',
			'translated_1'	=>	'admins',
		])
		->Insert();

		$root->Link($admins);

		$translators	=	$this->Create('h_group')
		->Set([
			'groupname'			=>	'translators',
			'translated_1'	=>	'translators',
		])
		->Insert();
		
		$this->Commit();
	}

	// ------------------------------------------------------------------
	protected function MapFieldType($def)
	{
		if ($this->dbtype == 'mysql')
		{
			$def	=	str_replace(
				[	'INTEGER PRIMARY KEY',
					'INT64',
					'INT32',
					'INT16',
					'INT8',
				],
				[	'INTEGER PRIMARY KEY AUTO_INCREMENT',
					'BIGINT',
					'INT',
					'SMALLINT',
					'TINYINT',
				],
				$def
			);
		}

		$def	=	str_replace(
			[	'ENUM',
				'BITFLAGS',
				'JSON',
				'SINGLETEXT',
				'MULTITEXT',
				'DATETIME',
				'DATE',
				'TIME',
				'PASSWORD',
				'PROTECTED',
				'PRIVATE',
				'TRANSLATION',
			],
			[	'INT',
				'INT',
				'TEXT',
				'TEXT',
				'TEXT',
				'TEXT',
				'TEXT',
				'TEXT',
				'TEXT',
				'',
				'',
				'INT',
			],
			$def
		);

		return $def;
	}

	// ------------------------------------------------------------------
	public function Register($model)
	{
		$this->InitDesc($model);

		try
		{
			// If model table exists and has already been registered
			if (in_array($model, array_keys($this->types)))
			{
				$model::$DESC['type']	=	$this->types[$model]['suid1'];
				return;
			}

			// If model table exists and has not already been registered
			if (V($model::$DESC, 'table') && $model::$DESC['table'] != 'h_dbtype')
			{
				$table	=	$model::$DESC['table'];
				$this->Query("SELECT * FROM {$table} LIMIT 1");
				$this->InsertNewType($model);
				return;
			}
		} catch (Exception $e)
		{
			//echo "Error: " . $e;
		}

		// If model table does not exist at all
		$fields			=	[];
		$desc				=	$model::$DESC;
		$table			=	$desc['table'];
		$flags			=	$desc['flags'];
		$modelid		=	$desc['id'];
		$uniqueids	=	$desc['uniqueids'];
		$numsuids		=	$desc['numsuids'];

		foreach ($model::$DESC['fields'] as $fieldname => $fielddef)
		{
			$fields[]	=	$fieldname . ' ' . $this->MapFieldType($fielddef[0]);
		}

		if (($this->flags & self::SECURE) && ($flags & self::SECURE))
		{
			$fields[]	=	'dbowner INT NOT NULL';
			$fields[]	=	'dbaccess INT NOT NULL';
			$fields[]	=	'aclid INT NOT NULL';
		}

		if ($numsuids)
		{
			for ($i = 1; $i <= $numsuids; $i++)
			{
				$fields[]	=	"suid{$i} INT NOT NULL";
			}
		}

		if ($uniqueids && !$modelid)
		{
			$keynames	=	[];

			foreach ($uniqueids as $idname)
			{
				if ($idname == 'dbowner')
				{
					$keynames[]	=	$idname;
					continue;
				}

				if ($this->dbtype == 'mysql')
				{
					if (strpos($model::$DESC['fields'][$idname][0], 'TEXT') !== false
						|| strpos($model::$DESC['fields'][$idname][0], 'DATE') !== false)
					{
						$keynames[]	=	$idname . '(255)';
					} else
					{
						$keynames[]	=	$idname;
					}
				} else
				{
					$keynames[]	=	$idname;
				}
			}

			$fields[]	=	'PRIMARY KEY(' . join(', ', $keynames) . ')';
		}

		$fields	=	join(',', $fields);

		switch ($this->dbtype)
		{
			case 'mysql':
				$this->Query("CREATE TABLE {$table}({$fields}) CHARACTER SET utf8
COLLATE utf8_unicode_ci;");
				break;
			default:
				$this->Query("CREATE TABLE {$table}({$fields})");
		};

		if ($uniqueids && $modelid)
		{
			switch ($this->dbtype)
			{
				case 'mysql':
					$columns	=	[];

					foreach ($uniqueids as $idname)
					{
						if (strpos($model::$DESC['fields'][$idname][0], 'TEXT') !== false
							|| strpos($model::$DESC['fields'][$idname][0], 'DATE') !== false)
						{
							$columns[]	=	$idname . '(255)';
						} else
						{
							$columns[]	=	$idname;
						}
					}
					$columns	=	join(', ', $columns);
					break;
				default:
					$columns	=	join(', ', $uniqueids);
			};

			if ($this->dbtype == 'mysql')
			{
				$this->Query("ALTER TABLE {$table} ADD UNIQUE INDEX {$table}_uniqueids({$columns})");
			} else
			{
				$this->Query("CREATE UNIQUE INDEX {$table}_uniqueids ON {$table}({$columns})");
			}
		}
		
		if ($numsuids)
		{
			$suids	=	[];
		
			for ($i = 1; $i <= $numsuids; $i++)
			{
				$suids[]	=	'suid' . $i;
			}
			
			$suids	=	join(', ', $suids);
			
			if ($this->dbtype == 'mysql')
			{
				$this->Query("ALTER TABLE {$table} ADD INDEX {$table}_suid{$i}({$suids})");
			} else
			{
				$this->Query("CREATE UNIQUE INDEX {$table}_suid{$i} ON {$table}({$suids})");
			}
		}

		if (V($desc, 'indexes'))
		{
			$indexnum	=	1;

			foreach ($desc['indexes'] as $index)
			{
				$indextype		=	$index[0];
				$indexcolumns	=	$index[1];
				
				if (!is_array($indexcolumns))
					$indexcolumns	=	[$indexcolumns];
			
				switch ($this->dbtype)
				{
					case 'mysql':
						$columns	=	[];

						foreach ($indexcolumns as $idname)
						{
							if (strpos($desc['fields'][$idname][0], 'TEXT') !== false
								|| strpos($desc['fields'][$idname][0], 'DATE') !== false)
							{
								$columns[]	=	$idname . '(255)';
							} else
							{
								$columns[]	=	$idname;
							}
						}
						$columns	=	join(', ', $columns);
						break;
					default:
						$columns	=	join(', ', $indexcolumns);
				};

				$this->Query("CREATE {$indextype} {$table}_index{$indexnum} ON {$table}({$columns})");
				$indexnum++;
			}
		}

		$this->InsertNewType($model);
	}

	// ------------------------------------------------------------------
	public function InsertNewType($model)
	{
		$this->Sudo();
	
		$newtype	=	$this->Create('h_dbtype')
		->Set([
			'typename'	=> $model,
			'access'		=> DB::CANNOT_DELETE 
				| DB::CANNOT_CHANGE
				| DB::CANNOT_LINK
				| DB::CANNOT_SECURE
				| DB::CANNOT_VIEW_PROTECTED,
			'acl'				=> [],
		])
		->Insert();

		$this->UnSudo();
		
		$model::$DESC['type']	=	$newtype->suid1;

		$this->types[$model]	=	$newtype->ToArray();
		$this->InitDesc($model);
	}

	// ------------------------------------------------------------------
	public function LastID()
	{
		return $this->dbconn->lastInsertId();
	}

	// ------------------------------------------------------------------
	public function Count($model, $cond = '', $params = [])
	{
		class_exists($model);
		
		$desc				=	$model::$DESC;
		$table			=	$desc['table'];
		
		if ($this->flags & self::SECURE && $desc['flags'] & DB::SECURE)
		{
			$count	=	0;
			
			foreach ($this->Find(
					$model, 
					$cond, 
					$params, 
					[
						'fields'		=>	array_keys($desc['fields'])[0],
						'start'			=>	0,
						'limit'			=>	10000,
					]
				) as $r
			)
			{
				$count++;
			}
		
			$results	=	[
				'COUNT(*)'	=> $count,
			];
		} else
		{
			$results	=	$this->Query("SELECT COUNT(*) FROM {$table} {$cond}", $params)->fetch();
		}
		
		if ($results)
			return $results['COUNT(*)'];

		return 0;
	}

	# ------------------------------------------------------------------
	# Returns the requested translations object
	#
	# For efficiency, translation objects are locally cached, meaning
	# that you won't have to make a database request for each translation
	public function LoadTranslations($textid)
	{
		if ($textid && V($this->translations, $textid))
			return $this->translations[$textid];
	
		$translations	= 0;
				
		if ($textid)
		{
			try
			{
				$translations	=	$this->Load(
					'h_translation',
					$textid
				);
			} catch (Exception $e)
			{
			}
		}
		
		if (!$translations)
			$translations	= $this->Create('h_translation');
		
		if (!is_array($translations->translations))
			$translations->translations = [];
		
		$this->translations[$textid]	= $translations;
		
		return $translations;
	}
	
	// ------------------------------------------------------------------
	public function ImportFields($model, $obj, $fields)
	{
		$desc						=	$model::$DESC;
		$fieldstocheck	=	$desc['fields'];
		$table					=	$desc['table'];
		$flags					=	$desc['flags'];
		$modelid				=	$desc['id'];
		$uniqueids			=	$desc['uniqueids'];
		$numsuids				=	$desc['numsuids'];

		if (($this->flags & DB::SECURE) && ($flags & DB::SECURE))
		{
			$fieldstocheck['dbowner']		=	['INT NOT NULL'];
			$fieldstocheck['dbaccess']	=	['INT NOT NULL'];
			$fieldstocheck['aclid']			=	['INT NOT NULL'];
		}

		if ($numsuids)
		{
			for ($i = 1; $i <= $numsuids; $i++)
			{
				$fieldstocheck['suid' . $i]	=	['INT NOT NULL'];
			}
		}

		$appname	=	explode('__', $model)[0];

		foreach ($fieldstocheck as $k => $v)
		{
			$hasfield	=	array_key_exists($k, $fields);

			if (FALSE !== strpos($v[0], 'TRANSLATION'))
			{
				$varname	= $k . 's';
				
				$obj->$varname =	V($fields, $k) 
					? $this->LoadTranslations($fields[$k])->translations 
					: [];
			} else if (FALSE !== strpos($v[0], 'INT'))
			{
				$obj->$k	=	$hasfield ? intval($fields[$k]) : 0;
			} else if (
				FALSE !== strpos($v[0], 'FLOAT')
				|| FALSE !== strpos($v[0], 'REAL')
			)
			{
				$obj->$k	=	$hasfield ? floatval($fields[$k]) : 0.0;
			} else if (FALSE !== strpos($v[0], 'JSON'))
			{
				if ($hasfield)
				{
					try
					{
						$obj->$k	=	json_decode($fields[$k], True);

						if (!$obj->$k)
							$obj->$k	=	'';
					} catch (Exception $e)
					{
						$obj->$k	=	'';
					}
				} else
				{
					$obj->$k	=	'';
				}
			} else
			{
				$obj->$k	=	$hasfield ? $fields[$k] : NULL;
			}
		}

		$obj->_changed	=	[];
		return $obj;
	}

	// ------------------------------------------------------------------
	public function Create($model)
	{
		if (!class_exists($model))
			throw new Exception('DB.Create: Missing ' . $model);
		
		if ($model != 'h_dbtype' && !array_key_exists($model, $this->types))
			throw new Exception("DB.Create: Model {$model} has not been registered yet.");

		$obj			=	new $model();
		$obj->_db	=	$this;

		$desc						=	$model::$DESC;
		$fields					=	$desc['fields'];
		$flags					=	$desc['flags'];
		$numsuids				=	$desc['numsuids'];

		// Initialize fields to type defaults
		foreach ($fields as $k => $v)
		{
			if (FALSE !== strpos($v[0], 'INT')
				|| FALSE !== strpos($v[0], 'FLOAT')
				|| FALSE !== strpos($v[0], 'REAL')
			)
			{
				$obj->$k	=	0;
			} else if (FALSE !== strpos($v[0], 'KEY'))
			{
				$obj->$k	=	null;
			} else
			{
				$obj->$k	=	'';
			}
		}

		if (($this->flags & DB::SECURE) && ($flags & DB::SECURE))
		{
			$obj->dbowner		=	$this->userid ? $this->userid : 1;
			$obj->dbaccess	=	0;
			$obj->aclid			=	'';
		}

		if ($numsuids)
		{
			for ($i = 1; $i <= $numsuids; $i++)
			{
				$suidname				=	'suid' . $i;
				$obj->$suidname	=	0;
			}
		}

		return $obj;
	}

	// ------------------------------------------------------------------
	public function Load($model, $id, $fieldstoload = '*', $obj = null)
	{
		global $T_SYSTEM, $_SESSION;

		if (!$id)
			throw new Exception("DB.Load: No ID provided for {$model}.");

		if (!array_key_exists($model, $this->types))
			throw new Exception("DB.Load: Model {$model} has not been registered yet.");

		$desc						=	$model::$DESC;
		$fields					=	$desc['fields'];
		$table					=	$desc['table'];
		$flags					=	$desc['flags'];
		$modelid				=	$desc['id'];
		$uniqueids			=	$desc['uniqueids'];
		$numsuids				=	$desc['numsuids'];

		$isadmin			=	isset($_SESSION) ? in_array('admins', ArrayV($_SESSION, 'groups')) : 0;
		$usesecurity	=	($this->flags & DB::SECURE) && ($flags & DB::SECURE);
		$trackaccess	=	($this->flags & DB::TRACK_VIEW) && ($flags & DB::TRACK_VIEW);
		$trackvalues	=	($this->flags & DB::TRACK_VALUES) && ($flags & DB::TRACK_VALUES);

		if ($fieldstoload != '*')
		{
			if ($usesecurity && false === strpos($fieldstoload, 'dbaccess'))
				$fieldstoload	.=	', dbowner, dbaccess, aclid';

			if ($numsuids)
			{
				for ($i = 1; $i <= $numsuids; $i++)
					$fieldstoload	.=	', suid' . $i;
			}
		}

		$idquery	=	'';
		$params		=	[];

		if (is_array($id))
		{
			$found	=	false;

			if ($uniqueids)
			{
				foreach ($uniqueids as $idfield)
				{
					if (!V($id, $idfield))
					{
						$found	=	true;
						break;
					}

					$idquery[]	=	"{$idfield} = ?";
					$params[]		=	$id[$idfield];
				}

				if ($found)
				{
					$found	=	false;
				} else
				{
					$idquery	=	join(' AND ', $idquery);
					$found		=	true;
				}
			}

			if (!$found && $numsuids && V($id, 'suid1'))
			{
				$idquery	=	[];

				for ($i = 1; $i <= $numsuids; $i++)
				{
					$suidname		=	"suid{$i}";
					$idquery[]	=	$suidname . ' = ?';
					$params[]		=	$id[$suidname];
				}

				$idquery	=	join(' AND ', $idquery);
				$found		=	true;
			}

			if (!$found && $modelid && V($id, $modelid))
			{
				$id	=	$id[$modelid];
				$idquery	=	"{$modelid} = ?";
				$params[]	=	$id;
			}
		} else
		{
			if ($numsuids == 1)
			{
				$idquery	=	"suid1 = ?";
				$params[]	=	$id;
			} else
			{
				$idquery	=	"{$modelid} = ?";
				$params[]	=	$id;
			}
		}
		
		if (is_array($fieldstoload))
			throw new Exception('DB.Load: Fields is array!');
			
		if (!$idquery)
			throw new Exception("DB.Load: No ID provided for {$model}: " . var_export($id));

		$r	=	$this->Query("SELECT {$fieldstoload} FROM {$table} WHERE " . $idquery, $params)->fetch();

		if ($r)
		{
			if ($usesecurity && !$isadmin && $r['dbowner'] != $this->userid)
			{
				if (!($this->Access($model, $r) & DB::CAN_VIEW))
					throw new Exception("DB.Load: Cannot view {$model}({$id})");
			}

			if (!$obj)
				$obj	=	new $model();

			if ($usesecurity)
			{
				$obj->dbowner		=	$this->userid ? $this->userid : 1;
				$obj->dbaccess	=	0;
				$obj->aclid			=	'';
			}

			$obj->_db	=	$this;

			if ($trackaccess)
				$this->AddChangeLog($model, $obj, h_changelog::ACCESSED, '');

			return $this->ImportFields($model, $obj, $r);
		} 
		
		if (is_array($id))
			$id	=	var_export($id, true);

		throw new Exception($model . '(' . $id . ') was not found.');
	}

	// ------------------------------------------------------------------
	public function LoadMany($model, $ids, $fields = '*')
	{
		return array_map(
			function($id) use ($model, $fields)
			{
				return $this->Load($model, $id, $fields);
			},
			$ids
		);
	}

	// ------------------------------------------------------------------
	public function Find($model, $cond = '', $params = [], $options = [])
	{
		global $T, $_SESSION;
		
		if ($model != 'h_dbtype' 
			&& (!class_exists($model) 
			|| !array_key_exists($model, $this->types))
		)
		{
				throw new Exception("DB.Find: Model {$model} has not been registered yet.");
		}
		
		$desc						=	$model::$DESC;
		$fields					=	$desc['fields'];
		$table					=	$desc['table'];
		$flags					=	$desc['flags'];
		$modelid				=	$desc['id'];
		$uniqueids			=	$desc['uniqueids'];
		$numsuids				=	$desc['numsuids'];
		
		$fieldstoget		=	StrV($options, 'fields', '*');
		$start					=	IntV($options, 'start', 0, 0, 2147483647);
		$limit					=	IntV($options, 'limit', 1000, 20, 2147483647);
		$chunksize			=	IntV($options, 'chunksize', 1000, 0, 50000);
		$access					=	IntV($options, 'access', DB::CAN_ALL);
		
		$isadmin			=	isset($_SESSION) ? in_array('admins', ArrayV($_SESSION, 'groups')) : false;
		$usesecurity	=	($this->flags & DB::SECURE) && ($flags & DB::SECURE);
		$trackaccess	=	($this->flags & DB::TRACK_VIEW) && ($flags & DB::TRACK_VIEW);
		$trackvalues	=	($this->flags & DB::TRACK_VALUES) && ($flags & DB::TRACK_VALUES);

		$ls	=	[];

		if (!is_array($params))
			$params	=	$params ? [$params] : [];

		if ($fieldstoget != '*')
		{
			if ($usesecurity && false === strpos($fieldstoget, 'dbaccess'))
				$fieldstoget	.=	(strpos($cond, 'JOIN') !== false)
					? ", {$table}.dbowner, {$table}.dbaccess, {$table}.aclid"
					: ', dbowner, dbaccess, aclid';

			if ($numsuids && false === strpos($fieldstoget, 'suid'))
			{
				for ($i = 1; $i <= $numsuids; $i++)
					$fieldstoget	.=	', suid' . $i;
			}
		}

		return new DBResult(
			$this,
			$table,
			"SELECT {$fieldstoget} FROM {$table} {$cond}", 
			"SELECT COUNT(*) FROM {$table} {$cond}", 
			$params,
			[
				'usesecurity'		=> $usesecurity,
				'isadmin'				=> $isadmin,
				'trackaccess'		=> $trackaccess,
				'trackvalues'		=> $trackvalues,
				'model'					=> $model,
				'start'					=> $start,
				'limit'					=> $limit,
				'cachesize'			=> IntV($options, 'cachesize'),
				'access'				=> $access,
			]
		);
	}

	// ------------------------------------------------------------------
	public function AddChangeLog($model, $obj, $changetype, $changed)
	{
		// If we're in the database initialization process, skip
		// writing changelogs.
		if (!in_array('h_tag', array_keys($this->types)))
			return;

		$desc						=	$model::$DESC;
		$fields					=	$desc['fields'];
		$table					=	$desc['table'];
		$flags					=	$desc['flags'];
		$modelid				=	$desc['id'];
		$uniqueids			=	$desc['uniqueids'];
		$numsuids				=	$desc['numsuids'];
		$ids						= [];

		if (!($this->flags & self::TRACK_CHANGES) || !($flags & self::TRACK_CHANGES))
			return;

		if ($modelid)
		{
			$ids[$modelid]	= $obj->$modelid;
		} else
		{
			if ($numsuids)
			{
				for ($i = 1; $i < $numsuids; $i++)
				{
					$idname	=	'suid' . $i;
					$ids[$idname]	=	$obj->$idname;
				}
			} else
			{
				foreach ($uniqueids as $idname)
				{
					$ids[$idname]	=	$obj->$idname;
				}
			}
		}

		$this->Query(
			"INSERT INTO h_changelogs(id, objtype, eventtype, ids, userid, eventtime, changed)
			VALUES(?, ?, ?, ?, ?, ?, ?)",
			[
				null,
				$desc['type'],
				$changetype,
				json_encode($ids),
				$this->userid,
				self::Date(),
				(($this->flags & self::TRACK_VALUES) && ($flags & self::TRACK_VALUES)) ? $changed : '',
			]
		);
	}

	// ------------------------------------------------------------------
	public function UpdateObj($from, $to, $fields, $numsuids, $flags)
	{
		$fieldstocopy	=	[];
		
		if (($this->flags & DB::SECURE) && ($flags & DB::SECURE))
		{
			$fields['dbowner']	= 0;
			$fields['dbaccess']	=	0;
			$fields['aclid']		=	0;
		}

		if ($numsuids)
		{
			for ($i = 1; $i <= $numsuids; $i++)
			{
				$fields['suid' . $i]	=	0;
			}
		}
		
		foreach ($fields as $k => $v)
		{
			if ($from->$k != $to->$k && !$to->$k)
				$fieldstocopy[$k]	=	$from->$k;
		}
	
		$to->Set($fieldstocopy);
	}
	
	// ------------------------------------------------------------------
	public function Replace($obj)
	{
		if (is_array($obj))
		{
			$this->Begin();
			
			$ls	=	[];

			foreach ($obj as $o)
			{
				$ls[]	=	$this->Replace($o);
			}

			$this->Commit();
			return $ls;
		}

		$fake	=	[];
		$this->SaveTranslations($obj, $fake);
		
		$model					=	get_class($obj);
		$desc						=	$model::$DESC;
		$fields					=	$desc['fields'];
		$table					=	$desc['table'];
		$flags					=	$desc['flags'];
		$modelid				=	$desc['id'];
		$uniqueids			=	$desc['uniqueids'];
		$numsuids				=	$desc['numsuids'];
		$firstfield			=	array_keys($fields)[0];

		if ($uniqueids)
		{
			$foundid			=	true;
			$whereclause	=	[];
			$values				=	[];

			foreach ($uniqueids as $idname)
			{
				if (!$obj->$idname)
				{
					$foundid	=	false;
					break;
				}

				$whereclause[]	=	$idname . " = ?";
				$values[]				=	$obj->$idname;

				$foundid	=	true;
			}

			if ($foundid)
			{
				$oldobj	=	$this->Find($model, 'WHERE ' . join(' AND ', $whereclause), $values)->One();
				
				if ($oldobj)
				{
					$this->UpdateObj($oldobj, $obj, $fields, $numsuids, $flags);
					return $this->Save($obj, 0);
				}
			}
		}

		if ($numsuids)
		{
			$foundid			=	false;
			$whereclause	=	[];
			$values				=	[];

			for ($i = 1; $i <= $numsuids; $i++)
			{
				$suidname				=	'suid' . $i;

				if (!$obj->$suidname)
				{
					$foundid			=	false;
					break;
				}

				$whereclause[]	=	$suidname . ' = ?';
				$values[]				=	$obj->$suidname;
				$foundid				=	true;
			}

			if ($foundid)
			{
				$oldobj	=	$this->Find($model, 'WHERE ' . join(' AND ', $whereclause), $values)->One();
				
				if ($oldobj)
				{
					$this->UpdateObj($oldobj, $obj, $fields, $numsuids, $flags);
					return $this->Save($obj, 0, 'suid');
				}
			}
		}

		if ($modelid && $obj->$modelid)
		{
			$foundid			=	true;
			$whereclause	=	[];
			$values				=	[];

			$whereclause[]	=	"{$modelid} = ?";;
			$values[]				=	$obj->$modelid;

			if ($foundid)
			{
				$oldobj	=	$this->Find($model, 'WHERE ' . join(' AND ', $whereclause), $values)->One();
				
				if ($oldobj)
				{
					$this->UpdateObj($oldobj, $obj, $fields, $numsuids, $flags);
					return $this->Save($obj, 0, 'id');
				}
			}
		}

		return $this->Save($obj, 1);
	}

	// ------------------------------------------------------------------
	public function Insert($o, $idmethod = 0)
	{
		$this->Save($o, 1, $idmethod);
	}
	
	// ------------------------------------------------------------------
	public function Update($o, $idmethod = 0)
	{
		$this->Save($o, 0, $idmethod);
	}
	
	// ------------------------------------------------------------------
	public function SaveTranslations($o, &$fieldvalues)
	{
		$model	=	get_class($o);

		if ($model != 'h_dbtype' && !array_key_exists($model, $this->types))
			throw new Exception("DB.Save: Model {$model} has not been registered yet.");

		$desc						=	$model::$DESC;
		$fields					=	$desc['fields'];
		$table					=	$desc['table'];
		
		foreach ($fields as $name => $value)
		{
			if (strpos($value[0], 'TRANSLATION') !== false)
			{
				$translationid		= $o->$name;
				$varname					= $name . 's';
				$newtranslations	=	V($o, $varname);
				
				if (!$newtranslations)
					$newtranslations	 = [];
				
				$translationsobj	= $this->LoadTranslations($translationid);
				$origtranslations	= $translationsobj->translations;
				
				$needsave = count($newtranslations) != count($origtranslations)
					|| array_diff_assoc($newtranslations, $origtranslations);
				
				if ($needsave)
				{
					$translationsobj->Set(['translations' => $newtranslations]);
					
					if (!$translationsobj->suid1)
					{
						if (!V((array)$o, $name . '_nosearch'))
						{
							$translationsearch	= '';
							
							foreach ($newtranslations as $k => $v)
							{
								if ($v)
								{
									$translationsearch	= '"' . $k . '":"' . $v . '"';
									break;
								}
							}
					
							# If it's a new translation, see if we already have such a 
							# translation saved
							if ($translationsearch)
							{
								$r	= $this->Find(
									'h_translation',
									'WHERE translations LIKE ?',
									'%' . $translationsearch . '%'
								)->All();
								
								if ($r)
									$translationsobj	= $r[0];
							}
						}
						
						if (!$translationsobj->suid1)
							$translationsobj->Insert();
					} else
					{
						$translationsobj->Update();
					}
					
					$o->Set([$name =>	$translationsobj->suid1]);
					
					if ($fieldvalues)
						$fieldvalues[$name]	=	$translationsobj->suid1;
				}
			}
		}
	}

	// ------------------------------------------------------------------
	public function Save($o, $insert = 0, $idmethod = 0)
	{
		global $T, $_SESSION;

		if (is_array($o))
		{
			$ls	=	[];

			foreach ($o as $obj)
				$ls[]	=	$this->Save($obj, $insert);

			return $ls;
		}

		$model	=	get_class($o);

		if ($model != 'h_dbtype' && !array_key_exists($model, $this->types))
			throw new Exception("DB.Save: Model {$model} has not been registered yet.");

		$desc						=	$model::$DESC;
		$fields					=	$desc['fields'];
		$table					=	$desc['table'];
		$flags					=	$desc['flags'];
		$modelid				=	$desc['id'];
		$uniqueids			=	$desc['uniqueids'];
		$numsuids				=	$desc['numsuids'];

		$isadmin			=	isset($_SESSION) ? in_array('admins', ArrayV($_SESSION, 'groups')) : false;
		$trackvalues	=	($this->flags & self::TRACK_VALUES) && ($flags & self::TRACK_VALUES);
		$trackchanges	=	($this->flags & self::TRACK_CHANGES) && ($flags & self::TRACK_CHANGES);
		$usesecurity	=	($this->flags & self::SECURE) && ($flags & self::SECURE);

		if (!$insert && !$o->_changed)
			return;

		if ($usesecurity 
			&& !$isadmin 
			&& $o->dbowner != $this->userid 
			&& !($this->Access($model, $o) & DB::CAN_CHANGE)
		)
		{
				throw new Exception("You do not have permission to change {$model}");
		}

		// Update only changed fields or update all fields
		$source	=	$insert ? $fields : $o->_changed;

		$fieldnames		=	join(', ', array_keys($source));
		$fieldvalues	=	[];
		$placeholders	=	[];

		for ($i = 0; $i < count($source); $i++)
		{
			$placeholders[]	=	'?';
		}

		$placeholders	=	join(',', $placeholders);

		// Convert fields to proper format
		foreach ($source as $k => $v)
		{
			if (!isset($o->$k))
				$o->$k	=	NULL;
				
			if (!array_key_exists($k, $fields))
				continue;

			if (FALSE !== strpos($fields[$k][0], 'KEY'))
			{
				$v	=	$o->$k;

				if (!$v)
				{
					$fieldvalues[$k]	=	null;
				} else
				{
					$fieldvalues[$k]	=	$v;
				}
			} else if (FALSE !== strpos($fields[$k][0], 'JSON'))
			{
				$fieldvalues[$k]	=	json_encode($o->$k, True);
			} else if (FALSE !== strpos($fields[$k][0], 'DATETIME'))
			{
				$t	=	new DateTime($o->$k);
				$fieldvalues[$k]	=	$t->format(DATE_ISO8601);
			} else if (FALSE !== strpos($fields[$k][0], 'ENUM')
				|| FALSE !== strpos($fields[$k][0], 'INT')
				|| FALSE !== strpos($fields[$k][0], 'BITFLAGS')
			)
			{
				$fieldvalues[$k]	=	intval($o->$k);
			} else
			{
				$fieldvalues[$k]	=	$o->$k;
			}

			$validator	=	V($fields[$k], 1);
			
			if ($validator)
			{
				throw new Exception("Validator " . $validator);
				$validator($k, $fields[$k]);
			}
		}

		if ($usesecurity)
		{
			if (!property_exists($o, 'dbowner'))
				throw new Exception('Missing dbowner!');

			$fieldvalues['dbowner']				=	$o->dbowner;
			$fieldvalues['dbaccess']			=	$o->dbaccess;
			$fieldvalues['aclid']					=	$o->aclid;
		}
		
		$hasid	=	1;

		if ($numsuids)
		{
			for ($i = 1; $i <= $numsuids; $i++)
			{
				$suidname	=	'suid' . $i;
				$fieldvalues[$suidname]	=	$o->$suidname;
			}
			
			$hasid	=	0;
		} else if ($uniqueids)
		{
			foreach ($uniqueids as $id)
			{
				if (!($o->$id))
				{
					$hasid	=	0;
					break;
				}
			}
			
			if ($hasid)
			{
				foreach ($uniqueids as $id)
				{
					$fieldvalues[$id]	=	$o->$id;
				}
			}
		}
		
		if (!$hasid && $modelid && $o->$modelid)
			$fieldvalues[$modelid]	=	$o->$modelid;

		$this->SaveTranslations($o, $fieldvalues);
		$appname	=	explode('__', $model)[0];

		// Last chance for the object to modify any fields
		$o->OnSave($this, $fieldvalues);

		$changetype		=	$insert ? h_changelog::CREATED : h_changelog::MODIFIED;

		if ($changetype == h_changelog::CREATED && $numsuids)
		{
			$suidquery	=	[];
		
			for ($i = 1; $i <= $numsuids; $i++)
			{
				$suidname	=	'suid' . $i;
				$v				=	intval($o->$suidname);
				
				if ($v)
					$suidquery[]	=	$suidname . ' = ' . $v;
			}
			
			$suidquery	=	$suidquery ? ' AND ' . join(' AND ', $suidquery) : '';
			
			for ($times = 0;; $times++)
			{
				$suidstotry	=	[];
				$suidvalues	=	[];
			
				for ($i = 1; $i <= $numsuids; $i++)
				{
					$suidname	=	'suid' . $i;
					
					if ($o->$suidname)
						continue;
						
					$suidstotry[]						=	$suidname . ' = ?';
					$suidvalues[$suidname]	=	MakeSUID();
				}
				
				if (!$suidvalues)
					break;
				
				$query	=	"SELECT suid1 FROM {$table} WHERE " . join(', ', $suidstotry) . $suidquery;

				if (!$this->Query($query, array_values($suidvalues))->fetch())
				{
					foreach ($suidvalues as $k => $v)
					{
						$fieldvalues[$k]	=	$v;
						$o->$k						=	$v	;
					}
					break;
				}

				if ($times == 1000)
					throw new Exception("Unable to find new SUID for {$model}.");
			}
		}

		$fieldnames		=	array_keys($fieldvalues);
		$values				=	array_values($fieldvalues);

		if ($changetype == h_changelog::CREATED)
		{
			$tablevalues	=	[];

			foreach ($fieldnames as $f)
			{
				$tablevalues[]	=	'?';
			}

			$tablefields	=	join(',', $fieldnames);
			$tablevalues	=	join(',', $tablevalues);

			$this->Query("INSERT INTO {$table}({$tablefields}) VALUES({$tablevalues})",
				$values
			);

			if ($modelid)
			{
				if (!$o->$modelid)
					$o->$modelid	=	$this->LastID();
			}
		} else
		{
			$valueclause	=	[];
			$l	=	count($fieldnames);

			for ($i = 0; $i < $l; $i++)
			{
				$valueclause[]	=	"{$fieldnames[$i]} = ?";
			}

			$valueclause	=	join(', ', $valueclause);

			$whereclause	=	[];
			
			if ($idmethod && $idmethod == 'suid')
			{
				for ($i = 1; $i <= $numsuids; $i++)
				{
					$idname					=	'suid' . $i;
					$whereclause[]	=	$idname . ' = ?';
					$values[]				=	$o->$idname;
				}
			} else if ($idmethod && $idmethod == 'id')
			{
				$whereclause[]	=	"{$modelid} = ?";;
				$values[]				=	$o->$modelid;
			} else if ($uniqueids)
			{
				foreach ($uniqueids as $idname)
				{
					$whereclause[]	=	"{$idname} = ?";;
					$values[]				=	$o->$idname;
				}
			} else if ($numsuids)
			{
				for ($i = 1; $i <= $numsuids; $i++)
				{
					$idname					=	'suid' . $i;
					$whereclause[]	=	$idname . ' = ?';
					$values[]				=	$o->$idname;
				}
			} else if ($modelid)
			{
				$whereclause[]	=	"{$modelid} = ?";
				$values[]				=	$o->$modelid;
			} else
			{
				throw new Exception('DB.Save: No ID method found');
			}
			
			$whereclause	=	join(' AND ', $whereclause);
			
			$this->Query("UPDATE {$table}
				SET {$valueclause}
				WHERE {$whereclause}",
				$values
			);
		}

		if ($trackchanges)
		{
			$this->AddChangeLog(
				$model,
				$o,
				$changetype,
				$trackvalues ? json_encode($o->_changed) : '');
		}

		$o->_changed	=	[];

		if ($modelid)
			return $o->$modelid;

		// Let the object do any cleanup or adjustments
		$o->PostSave($this, $fieldvalues);
		return 0;
	}

	// ------------------------------------------------------------------
	public function Delete($model, $cond = '', $params = [])
	{
		global $T, $_SESSION;

		if (!isset($_SESSION))
			$_SESSION	=	[];

		if (is_array($model))
		{
			foreach ($model as $obj)
				$this->Delete($obj, $insert);

			return;
		}

		$isadmin			=	(V($_SESSION, 'userid') == 1) || in_array('admins', ArrayV($_SESSION, 'groups'));

		if (is_string($model))
		{
			if (!class_exists($model) || !array_key_exists($model, $this->types))
				throw new Exception("DB.Delete: Model {$model} has not been registered yet.");
			
			$desc						=	$model::$DESC;
			$fields					=	$desc['fields'];
			$table					=	$desc['table'];
			$flags					=	$desc['flags'];
			$modelid				=	$desc['id'];
			$uniqueids			=	$desc['uniqueids'];
			$numsuids				=	$desc['numsuids'];

			$usesecurity	=	($this->flags & self::SECURE) && ($flags & self::SECURE);

			if ($usesecurity
				&& !$isadmin
				&& !($this->Access($model, 0) & DB::CAN_DELETE)
			)
				throw new Exception("You do not have permission to delete {$model}.");

			if (!$cond)
			{
				if ($this->dbtype == 'mysql')
				{
					$this->Query("TRUNCATE TABLE ?", $table);
				} else
				{
					$this->Query("DELETE FROM TABLE ", $table);
					
					if ($this->dbtype == 'sqlite')
					{
						$this->Query("DELETE FROM SQLITE_SEQUENCE WHERE name=?", $table);
					}
				}
				$this->AddChangeLog($model, '', '', h_changelog::DELETED, '');
				return;
			}
		} else
		{
			$obj		=	$model;
			$model	=	get_class($obj);

			if (!class_exists($model) || !array_key_exists($model, $this->types))
				throw new Exception("DB.Delete: Model {$model} has not been registered yet.");
			
			$desc						=	$model::$DESC;
			$fields					=	$desc['fields'];
			$table					=	$desc['table'];
			$flags					=	$desc['flags'];
			$modelid				=	$desc['id'];
			$uniqueids			=	$desc['uniqueids'];
			$numsuids				=	$desc['numsuids'];

			$usesecurity	=	($this->flags & self::SECURE) && ($flags & self::SECURE);

			if (!array_key_exists($model, $this->types))
				throw new Exception("DB.Delete: Model {$model} has not been registered yet.");

			if ($usesecurity
				&& !$isadmin
				&& $obj->dbowner != $this->userid
				&& !($this->Access($model, $obj) & DB::CAN_DELETE)
			)
				throw new Exception("You do not have permission to delete object {$model}.");
		}

		if (!$cond)
		{
			$whereclause	=	[];
			$params				=	[];

			if ($modelid)
			{
				if (!$obj->$modelid)
					throw new Exception("Cannot delete {$model} without ID.");

				$whereclause[]	=	"{$modelid} = ?";;
				$params[]				=	$obj->$modelid;
			} else if ($numsuids && $obj->suid1)
			{
				for ($i = 1; $i <= $numsuids; $i++)
				{
					$suidname				=	"suid{$i}";
					$whereclause[]	=	$suidname . ' = ?';
					$params[]				=	$obj->$suidname;
				}
			} else if ($uniqueids)
			{
				foreach ($uniqueids as $idname)
				{
					$whereclause[]	=	"{$idname} = ?";;
					$params[]				=	$obj->$idname;
				}
			}

			$whereclause	=	join(' AND ', $whereclause);

			$obj->OnDelete($this);

			if (!$isadmin && !($this->Access($model, $obj) & DB::CAN_DELETE))
				throw new Exception("Cannot delete {$model}");

			$this->Query("DELETE FROM {$table} WHERE " . $whereclause, $params);
			$this->AddChangeLog($model, $obj, h_changelog::DELETED, '');
		} else
		{
			foreach ($this->Find($model, $cond, $params) as $obj)
			{
				$this->Delete($obj);
			}
		}
	}

	// ------------------------------------------------------------------
	protected function FieldSQLType($def)
	{
		return str_replace(
			[	'PRIMARY KEY',
				'FOREIGN KEY',
				'UNIQUE',
				'AUTOINCREMENT',
				'AUTO_INCREMENT',
				'UID',
			],
			[	'',
				'',
				'',
				'',
				'INT',
			],
			$this->MapFieldType($def)
		);
	}

	// ------------------------------------------------------------------
	public function GetModelType($model)
	{
		$desc	=	$model::$DESC;

		if ($desc['id'])
			return DB::IDMODEL;

		if ($desc['uniqueids'])
			return DB::UNIQUEMODEL;

		if ($desc['numsuids'])
			return DB::SUIDMODEL;

		return DB::PLAINMODEL;
	}

	// ------------------------------------------------------------------
	public function GetModelIDs($model, $preferint = false)
	{
		$desc		=	$model::$DESC;
		$fields	=	$desc['fields'];
		$ids		=	[];

		if (!$preferint)
		{
			if ($desc['uniqueids'])
			{
				foreach ($desc['uniqueids'] as $uid)
				{
					if ($uid == 'dbowner')
					{
						$ids['dbowner']	=	'INT';
					} else
					{
						$ids[$uid]	=	$this->FieldSQLType($fields[$uid][0]);
					}
				}
			} else if ($desc['numsuids'])
			{
				for ($i = 1; $i <= $desc['numsuids']; $i++)
				{
					$ids['suid' . $i]	=	'INT';
				}
			} else if ($desc['id'])
			{
				$ids[$desc['id']]	= $this->FieldSQLType($fields[$desc['id']][0]);
			} else
			{
				$ids	=	array_keys($fields);
			}
		} else
		{
			if ($desc['numsuids'])
			{
				for ($i = 1; $i <= $desc['numsuids']; $i++)
				{
					$ids['suid' . $i]	=	'INT';
				}
			} else if ($desc['id'])
			{
				$ids[$desc['id']]	= $this->FieldSQLType($fields[$desc['id']][0]);
			} else if ($desc['uniqueids'])
			{
				foreach ($desc['uniqueids'] as $uid)
				{
					if ($uid == 'dbowner')
					{
						$ids['dbowner']	=	'INT';
					} else
					{
						$ids[$uid]	=	$this->FieldSQLType($fields[$uid][0]);
					}
				}
			} else
			{
				foreach (array_keys($fields) as $k)
				{
					$ids[$k]	=	$fields[$k];
				}
			}
		}

		return $ids;
	}

	// ------------------------------------------------------------------
	public function GetModelFields($model)
	{
		$desc			=	$model::$DESC;
		$numsuids	=	$desc['numsuids'];

		$fields	=	[];

		foreach ($desc['fields'] as $k => $v)
		{
			if (FALSE !== strpos($v[0], 'INT'))
			{
				$fields[$k]	=	'number';
			} else if (FALSE !== strpos($v[0], 'FLOAT')
				|| FALSE !== strpos($v[0], 'REAL')
				|| FALSE !== strpos($v[0], 'NUMBER')
			)
			{
				$fields[$k]	=	'float';
			} else if (FALSE !== strpos($v[0], 'DATETIME'))
			{
				$fields[$k]	=	'datetime-local';
			} else if (FALSE !== strpos($v[0], 'DATE'))
			{
				$fields[$k]	=	'date placeholder="2015-04-03"';
			} else if (FALSE !== strpos($v[0], 'TIME'))
			{
				$fields[$k]	=	'text placeholder="09:38:42"';
			} else if (FALSE !== strpos($v[0], 'EMAIL'))
			{
				$fields[$k]	=	'email';
			} else if (FALSE !== strpos($v[0], 'URL'))
			{
				$fields[$k]	=	'url';
			} else if (FALSE !== strpos($v[0], 'PHONE'))
			{
				$fields[$k]	=	'phone';
			} else if (FALSE !== strpos($v[0], 'COLOR'))
			{
				$fields[$k]	=	'color';
			} else if (FALSE !== strpos($v[0], 'RANGE'))
			{
				$fields[$k]	=	'range,' . $v[2] . ',' . $v[3];
			} else if (FALSE !== strpos($v[0], 'ENUM'))
			{
				$fields[$k]	=	'enum,' . var_export($v[2], true);
			} else if (FALSE !== strpos($v[0], 'MULTITEXT'))
			{
				$fields[$k]	=	'multitext';
			} else if (FALSE !== strpos($v[0], 'PASSWORD'))
			{
				$fields[$k]	=	'password';
			} else if (FALSE !== strpos($v[0], 'TRANSLATION'))
			{
				$fields[$k]	=	'translation';
			} else
			{
				$fields[$k]	=	'text';
			}
		}

		if ($numsuids)
		{
			for ($i = 1; $i <= $numsuids; $i++)
			{
				$fields['suid' . $i]	=	'number';
			}
		}

		$ls	=	[];
		$ls['fields']			=	$fields;
		$ls['id']					=	$desc['id'];
		$ls['uniqueids']	=	$desc['uniqueids'];
		$ls['numsuids']		=	$desc['numsuids'];
		$ls['ids']				=	$this->GetModelIDs($model);

		return $ls;
	}

	// ------------------------------------------------------------------
	public function VerifyIDs($obj, $prefix = '')
	{
		$model		=	get_class($obj);
		$idnames	=	$this->GetModelIDs($model, true);

		$ls	=	[
			'idnames'	=>	$idnames,
		];

		foreach ($idnames as $idname => $idtype)
		{
			if (!$obj->$idname)
				throw new Exception($model . ' missing ID ' . $idname);

			$ls[$prefix . $idname]	=	$obj->$idname;
		}

		return $ls;
	}

	// ------------------------------------------------------------------
	public function Link($o1, $o2, $type = 0, $num = 0, $comment = '')
	{
		global $T;
		
		$v	=	[
			'ls2'	=>	null,
		];

		if (is_array($o2))
		{
			$v['ls2']	=	$o2;
			$o2				=	$v['ls2'][0];
		}

		$v['o1']			=	$o1;
		$v['o2']			=	$o2;
		$v['type']		=	$type;
		$v['num']			=	$num;
		$v['comment']	=	$comment;

		$v['model1']	=	get_class($o1);
		$v['model2']	=	get_class($o2);

		$v['table1']	=	$v['model1']::$DESC['table'];
		$v['table2']	=	$v['model2']::$DESC['table'];

		$v['idnames1']	=	$this->GetModelIDs($v['model1'], true);
		$v['idnames2']	=	$this->GetModelIDs($v['model2'], true);

		$v['tablename']	=	$v['table1'] . '__' . $v['table2'];

		try
		{
			$this->TryToLink($v);
		} catch (Exception $e)
		{
			// The first time two objects are linked, the linking table will not exist. Thus we create
			// the linking table and try to link again
			$idfields		=	[];
			$keyfields	=	[];

			foreach ($v['idnames1'] as $idname => $idtype)
			{
				$prefix			=	'a_' . $idname;
				$idfields[]	=	$prefix . ' ' . $idtype;

				if ($this->dbtype == 'mysql')
				{
					if (strpos($idname, 'suid') === false
						&& (strpos($v['model1']::$DESC['fields'][$idname][0], 'TEXT') !== false
							|| strpos($v['model1']::$DESC['fields'][$idname][0], 'DATE') !== false
						))
					{
						$keyfields[]	=	$prefix . '(255)';
					} else
					{
						$keyfields[]	=	$prefix;
					}
				} else
				{
					$keyfields[]	=	$prefix;
				}
			}

			foreach ($v['idnames2'] as $idname => $idtype)
			{
				$prefix			=	'b_' . $idname;
				$idfields[]	=	$prefix . ' ' . $idtype;

				if ($this->dbtype == 'mysql')
				{
					if (strpos($idname, 'suid') === false
						&& (strpos($v['model2']::$DESC['fields'][$idname][0], 'TEXT') !== false
							|| strpos($v['model2']::$DESC['fields'][$idname][0], 'DATE') !== false)
						)
					{
						$keyfields[]	=	$prefix . '(255)';
					} else
					{
						$keyfields[]	=	$prefix;
					}
				} else
				{
					$keyfields[]	=	$prefix;
				}
			}

			$idfields		=	join(",\n", $idfields);
			$keyfields	=	join(', ', $keyfields);

			$query = "CREATE TABLE IF NOT EXISTS {$v['tablename']}
				(
					{$idfields},
					linktype		INT NOT NULL,
					linknum			INT NOT NULL,
					linkcomment	TEXT NOT NULL,
					PRIMARY KEY({$keyfields})
				)
			";

			if ($this->dbtype == 'mysql')
				$query	.=	"CHARACTER SET utf8 COLLATE utf8_unicode_ci";

			$this->Query($query);

			$this->TryToLink($v);
		}
		
		return $o1;
	}

	// ------------------------------------------------------------------
	function ReallyLink($v, $o1, $o2, $type, $num, $comment)
	{
		$idquery	=	[];
		$params		=	[];
		$values		=	[];

		foreach ($v['idnames1'] as $idname => $value)
		{
			$idquery[]	=	'a_' . $idname;

			if (!$o1->$idname)
				throw new Exception("{$v['model1']} has not been saved.");

			$values[]		=	$o1->$idname;
			$params[]		=	'?';
		}

		foreach ($v['idnames2'] as $idname => $idtype)
		{
			$idquery[]	=	'b_' . $idname;

			if (!$o2->$idname)
				throw new Exception("{$v['model1']} has not been saved.");

			$values[]		=	$o2->$idname;
			$params[]		=	'?';
		}

		$values[]	=	$type;
		$values[]	=	$num;
		$values[]	=	$comment;

		$idquery	=	join(', ', $idquery);
		$params		=	join(', ', $params);

		$this->Query(
			"REPLACE INTO {$v['tablename']}
			({$idquery}, linktype, linknum, linkcomment) 
			VALUES({$params}, ?, ?, ?)",
			$values
		);
	}

	// ------------------------------------------------------------------
	function TryToLink($v)
	{
		if ($v['ls2'])
		{
			$num	=	1;

			$this->Begin();

			$this->UnlinkMany($v['o1'], $v['model2'], $v['type']);

			foreach ($v['ls2'] as $o2)
			{
				$this->ReallyLink($v, $v['o1'], $o2, $v['type'], $num, $v['comment']);
				$num++;
			}

			$this->Commit();
		} else
		{
			$this->ReallyLink($v, $v['o1'], $v['o2'], $v['type'], $v['num'], $v['comment']);
		}
	}

	// ------------------------------------------------------------------
	public function UnlinkMany($o1, $model2, $type = 0)
	{
		$model1	=	get_class($o1);

		$table1	=	$model1::$DESC['table'];
		$table2	=	$model2::$DESC['table'];

		$idnames1	=	$this->GetModelIDs($model1, true);

		if (!$idnames1)
			throw new Exception('Cannot unlink objects without ID fields: ' . $e);

		foreach ($idnames1 as $idname => $idtype)
		{
			if (!$o1->$idname)
				throw new Exception($model1 . ' missing ID ' . $idname);
		}

		$tablename	=	$table1 . '__' . $table2;

		$idquery	=	[];
		$values		=	[];

		foreach ($idnames1 as $idname => $idtype)
		{
			$idquery[]	=	'a_' . $idname . ' = ?';
			$values[]		=	$o1->$idname;
		}

		if ($type)
		{
			$idquery[]	=	'linktype = ?';
			$values[]		=	$type;
		}

		$idquery	=	join(' AND ', $idquery);

		// Catch missing table exceptions
		try
		{
			$this->Query("DELETE FROM {$tablename}
				WHERE " . $idquery,
				$values
			);
		} catch (Exception $e)
		{
		}
	}

	// ------------------------------------------------------------------
	public function Unlink($o1, $o2, $type = 0)
	{
		global $T;

		$model1	=	get_class($o1);
		$model2	=	get_class($o2);

		$table1	=	$model1::$DESC['table'];
		$table2	=	$model2::$DESC['table'];

		$idnames1	=	$this->GetModelIDs($model1, true);
		$idnames2	=	$this->GetModelIDs($model2, true);

		if (!$idnames1 || !$idnames2)
			throw new Exception('DB.Unlink: Cannot unlink objects without ID fields ');

		foreach ($idnames1 as $idname => $idtype)
		{
			if (!$o1->$idname)
				throw new Exception('DB.Unlink: ' . $model1 . ' missing ' . $idname);
		}

		foreach ($idnames2 as $idname => $idtype)
		{
			if (!$o2->$idname)
				throw new Exception('DB.Unlink: ' . $model2 . ' missing ' . $idname);
		}

		$tablename	=	$table1 . '__' . $table2;

		$idquery	=	[];
		$values		=	[];

		foreach ($idnames1 as $idname => $idtype)
		{
			$idquery[]	=	'a_' . $idname . ' = ?';
			$values[]		=	$o1->$idname;
		}

		foreach ($idnames2 as $idname => $idtype)
		{
			$idquery[]	=	'b_' . $idname . ' = ?';
			$values[]		=	$o2->$idname;
		}

		if ($type)
		{
			$idquery[]	=	'linktype = ?';
			$values[]		=	$type;
		}

		$idquery	=	join(' AND ', $idquery);

		$this->Query("DELETE FROM {$tablename}
			WHERE {$idquery}",
			$values
		);
	}

	// ------------------------------------------------------------------
	public function Linked($o1, $model2, $type = 0, $options = [])
	{
		global $T;

		if (is_array($o1))
		{
			$ls	=	[];

			$model1	=	get_class($o1);

			$table1	=	$model1::$DESC['table'];
			$table2	=	$model2::$DESC['table'];

			$idnames1	=	$this->GetModelIDs($model1, true);

			foreach ($o1 as $obj)
			{
				$ls[$obj->$idname1[0]]	=	$this->Linked($o1, $model2, $type, $fields, $limit);
			}

			return $ls;
		}
		
		$model1	=	get_class($o1);

		$table1	=	$model1::$DESC['table'];
		$table2	=	$model2::$DESC['table'];

		$idnames1	=	$this->GetModelIDs($model1, true);
		$idnames2	=	$this->GetModelIDs($model2, true);

		if (!$idnames1 || !$idnames2)
			throw new Exception('DB.Linked: Cannot unlink objects without ID fields ');
			
		$fields		=	StrV($options, 'fields', '*');
		$start		=	IntV($options, 'start', 0, 0, 99999999);
		$limit		=	IntV($options, 'limit', 100, 20, 5000);
		$reversed	=	IntV($options, 'reversed');
		$orderby	=	StrV($options, 'orderby');

		foreach ($idnames1 as $idname => $idtype)
		{
			if (!$o1->$idname)
				throw new Exception('DB.Linked: ' . $model1 . ' missing ID ' . $idname);
		}

		$tablename	=	$table1 . '__' . $table2;

		$idstoselect	=	[];
		$idquery			=	[];
		$values				=	[];

		foreach ($idnames1 as $idname => $idtype)
		{
			$idquery[]	=	($reversed ? 'b_' : 'a_') . $idname . ' = ?';
			$values[]		=	$o1->$idname;
		}
		
		foreach ($idnames2 as $idname => $idtype)
		{
			$idstoselect[]	=	($reversed ? 'a_' : 'b_') . $idname . ' AS ' . $idname;
		}

		if ($type)
		{
			$idquery[]	=	'linktype = ?';
			$values[]		=	$type;
		}

		$query	=	"SELECT " . join(', ', $idstoselect) . ", linknum, linktype, linkcomment FROM {$tablename}
				WHERE " . join(' AND ', $idquery) . "
				ORDER BY linknum 
				LIMIT {$start}, {$limit}";

		$objs	=	[];

		try
		{
			$results	=	$this->Query($query, $values);
		} catch (Exception $e)
		{
			// Linking table doesn't exist
			if (false !== strpos($e->getMessage(), 'exist'))
				return [];

			throw $e;
		}

		foreach ($results as $r)
		{
			try
			{
				$obj	=	$this->Load($model2, $r, $fields);
			} catch (Exception $e)
			{
				echo "<pre class=Error>No {$model2} with ID " . var_export($r, true) . " was found: {$e}</pre>";
				continue;
			}

			$obj->dblinktype	=	$r['linktype'];
			$objs[]						=	$obj;
		}
		
		if ($orderby)
		{
			usort(
				$objs,
				function($a, $b) use ($orderby)
				{
					return strcasecmp($a[$orderby], $b[$orderby]);
				}
			);
		}

		return $objs;
	}

	// ------------------------------------------------------------------
	public function MaxID($model)
	{
		$table	=	$model::$DESC['table'];
		$idname	=	$model::$DESC['id'];

		$r	=	$this->Query("SELECT MAX({$idname}) AS max FROM {$table}")->fetch();

		if ($r)
			return intval($r['max']);

		return 0;
	}
	
	// ------------------------------------------------------------------
	public function Tag($ls, $tagname, $language = 0)
	{
		if (!$ls || !$tagname)
			throw new Exception("DB.Tag: Missing object or tagname!");

		if (!is_array($ls))
			$ls	=	[$ls];
			
		if (!$language)
			$language	=	$this->language;
			
		if (is_array($tagname))
		{
			$this->Begin();
		
			foreach ($tagname as $tag)
				$this->Tag($ls, $tag, $language);
				
			$this->Commit();
			return $this;
		}

		$model			=	get_class($ls[0]);
		$tablename	=	$model::$DESC['table'] . '_tags';
		$fields			=	$model::$DESC['fields'];
		$idnames		=	$this->GetModelIDs($model, true);

		$tagid	=	$this->Query("SELECT suid1
			FROM h_tags
			WHERE tagname = ? AND languageid = ?",
			[$tagname, $language]
		)->fetch();
		
		if ($tagid)
		{
			$tagid	=	$tagid['suid1'];
		} else
		{
			for ($i = 0; ; $i++)
			{
				$tagid	=	MakeSUIDs(1)[0];

				$results	=	$this->Query('SELECT suid1 FROM h_tags WHERE suid1 = ?', $tagid);

				if (!$results)
				{
					$this->Query(
						'INSERT INTO h_tags(suid1, languageid, tagname)
						VALUES(?, ?, ?)',
						[$tagid, $language, $tagname]
					);
					break;
				}
				if ($i == 1000)
					throw new Exception('DB.Tag: Could not find suid for tag!');
			}
		}

		$ids			=	[];
		$keynames	=	[];
		$idquery	=	[];
		$questionmarks	=	[];

		foreach ($idnames as $name => $type)
		{
			$idquery[]	=	$name . ' = ?';
			$ids[]			=	$name;
			$questionmarks[]	=	'?';

			if ($this->dbtype == 'mysql')
			{
				if (substr($name, 0, 4) != 'suid'
					&& (strpos($fields[$name][1], 'TEXT') !== false
						|| strpos($fields[$name][1], 'DATE') !== false)
				)
				{
					$keynames[]	=	$name . '(255)';
				} else
				{
					$keynames[]	=	$name;
				}
			} else
			{
				$keynames[]	=	$name;
			}
		}

		$idquery				=	join(' AND ', $idquery);
		$ids						=	join(', ', $ids);
		$keynames				=	join(', ', $keynames);
		$questionmarks	=	join(', ', $questionmarks);

		foreach ($ls as $o)
		{
			$values	=	[];

			foreach ($idnames as $name => $type)
			{
				$values[]	=	$o->$name;
			}

			$values[]	=	$tagid;
			$values[]	=	$language;

			try
			{
				$results	=	$this->Query(
					"SELECT * FROM {$tablename} WHERE {$idquery} AND tagid = ?",
					$values
				);

				if ($results)
					continue;

				$this->Query(
					"INSERT INTO {$tablename}({$ids}, tagid, languageid) VALUES({$questionmarks}, ?, ?)",
					$values
				);
			} catch (Exception $e)
			{
				$idfields		=	[];

				foreach ($idnames as $name => $idtype)
				{
					$idfields[]		=	$name . ' ' . $idtype . ' NOT NULL';
				}

				$idfields		=	join(",\n", $idfields);

				$this->Query("
					CREATE TABLE IF NOT EXISTS {$tablename}(
						{$idfields},
						tagid	INT,
						languageid INT,
						PRIMARY KEY({$keynames}, languageid, tagid)
					);
				");

				$this->Query(
					"INSERT INTO {$tablename}({$ids}, tagid, languageid) VALUES({$questionmarks}, ?, ?)",
					$values
				);
			}
		}
	}

	// ------------------------------------------------------------------
	public function UnTag($ls, $tagname, $language = 0)
	{
		if (!$ls || !$tagname)
			throw new Exception("DB.Tag: Missing object or tagname!");

		if (!is_array($ls))
			$ls	=	[$ls];
			
		if (!$language)
			$language	=	$this->language;
		
		$model			=	get_class($ls[0]);
		$tablename	=	$model::$DESC['table'] . '_tags';
		$idnames		=	$this->GetModelIDs($model, true);

		$idquery	=	[];

		foreach ($idnames as $name => $type)
		{
			$idquery[]	=	$name . ' = ?';
		}

		$idquery				=	join(' AND ', $idquery);

		$tagid	=	$this->Query('SELECT suid1
			FROM h_tags
			WHERE languageid = ? AND tagname = ?',
			[$languageid, $tagname]
		)->fetch();

		if ($tagid)
		{
			$tagid	=	$suid['suid1'];
		} else
		{
			return;
		}

		foreach ($ls as $o)
		{
			$values	=	[];

			foreach ($idnames as $name => $type)
			{
				$values[]	=	$o->$name;
			}

			$values[]	=	$suid;

			$this->Query(
				"DELETE FROM {$tablename} WHERE {$idquery} AND tagid = ?",
				$values
			);
		}
	}

	// ------------------------------------------------------------------
	public function HasTag($model, $tagname, $language)
	{
		if (!$model || !$tagname)
			throw new Exception("DB.HasTag: Missing model or tagname!");

		if (!$language)
			$language	=	$this->language;

		$tablename	=	$model::$DESC['table'] . '_tags';
		$idnames		=	$this->GetModelIDs($model, true);

		$idquery	=	[];

		foreach ($idnames as $name => $type)
		{
			$idquery[]	=	$name . ' = ?';
		}

		$idquery				=	join(' AND ', $idquery);

		$tagid	=	$this->Query('SELECT suid1
			FROM h_tags
			WHERE languageid = ? AND tagname = ?',
			[$language, $tagname]
		)->fetch();

		if ($tagid)
		{
			$tagid	=	$tagid['suid1'];
		} else
		{
			return [];
		}

		$objs	=	[];

		try
		{
			foreach ($this->Query(
					"SELECT * FROM {$tablename} WHERE suid1 = ?",
					$tagid
				) as $r
			)
			{
				$objs[]	=	$this->Load($model, $r);
			}
		} catch (Exception $e)
		{
			echo $e;
		}

		return $objs;
	}
	
	// ------------------------------------------------------------------
	public function ListAllTags($model, $language)
	{
		if (!$model)
			throw new Exception("DB.ListModelTag: Missing model!");

		if (!$language)
			$language	=	$this->language;
			
		$tablename	=	$model::$DESC['table'] . '_tags';

		$tags	=	[];

		try
		{
			foreach ($this->Query("SELECT tagname
					FROM {$tablename}
					LEFT JOIN h_tags ON {$tablename}.suid1 = tags.suid1
					WHERE languageid = ?
					GROUP BY tagname",
					$language
				) as $r
			)
			{
				$tags[$r]	=	0;
			}
		} catch (Exception $e)
		{
			return $tags;
		}

		return array_keys($tags);
	}

	// ------------------------------------------------------------------
	public function SetProp($ls, $propname, $propvalue, $language = 0)
	{
		if (!$ls || !$propvalue)
			throw new Exception("DB.SetProp: Missing object or tagname!");

		if (!is_array($ls))
			$ls	=	[$ls];

		if (!$language)
			$language	=	$this->language;
			
		$model			=	get_class($ls[0]);
		$tablename	=	$model::$DESC['table'] . '_props';
		$idnames		=	$this->GetModelIDs($model, true);

		$idnames				=	[];
		$keynames				=	[];
		$idquery				=	[];
		$questionmarks	=	[];
		$keylength			=	'';

		foreach ($idnames as $name => $type)
		{
			$idquery[]	=	$name . ' = ?';
			$idnames[]	=	$name;
			$questionmarks[]	=	'?';

			if ($this->dbtype == 'mysql')
			{
				$keylength	=	'(255)';

				if (strpos($model::$DESC['fields'][$name][1], 'TEXT') !== false
					|| strpos($model::$DESC['fields'][$name][1], 'DATE') !== false)
				{
					$keynames[]	=	$idname . '(255)';
				} else
				{
					$keynames[]	=	$idname;
				}
			} else
			{
				$keynames[]	=	$idname;
			}
		}

		$idquery				=	join(' AND ', $idquery);
		$idnames				=	join(', ', $idnames);
		$keynames				=	join(', ', $keynames);
		$questionmarks	=	join(', ', $questionmarks);

		foreach ($ls as $o)
		{
			$values	=	[];

			foreach ($idnames as $name => $type)
			{
				$values[]	=	$o->$name;
			}

			$values[]	=	$propname;
			$values[]	=	$propvalue;
			$values[]	=	$language;

			try
			{
				$this->Query(
					"REPLACE INTO {$tablename}({$idnames}, propname, propvalue, languageid) VALUES({$questionmarks}, ?, ?, ?)",
					$values
				);
			} catch (Exception $e)
			{
				$idfields		=	[];

				foreach ($idnames1 as $idname => $idtype)
				{
					$idfields[]		=	$idname . ' ' . $idtype . ' NOT NULL';
				}

				$idfields		=	join(",\n", $idfields);

				$this->Query("
					CREATE TABLE IF NOT EXISTS {$tablename}(
						{$idfields},
						propname		TEXT NOT NULL,
						propvalue 	TEXT NOT NULL,
						languageid	INT NOT NULL,
						PRIMARY KEY({$keynames}, propname{$keylength}, propvalue)
					);
				");

				$this->Query(
					"REPLACE INTO {$tablename}({$idnames}, propname, propvalue, languageid) VALUES({$questionmarks}, ?, ?, ?)",
					$values
				);
			}
		}
	}

	// ------------------------------------------------------------------
	public function GetProp($ls, $propname, $language)
	{
		if (!$ls || !$tagname)
			throw new Exception("DB.Tag: Missing object or tagname!");

		if (!$language)
			$language	=	$this->language;
			
		$isarray	=	is_array($ls);

		if (!$isarray)
			$ls	=	[$ls];

		$model			=	get_class($ls[0]);
		$tablename	=	$model::$DESC['table'] . '_props';
		$idnames		=	$this->GetModelIDs($model, true);

		$idquery	=	[];

		foreach ($idnames as $name => $type)
		{
			$idquery[]	=	$name . ' = ?';
		}
		
		$idquery[]	=	'languageid = ?';
		$idquery				=	join(' AND ', $idquery);

		$results	=	[];

		foreach ($ls as $o)
		{
			$values	=	[];

			foreach ($idnames as $name => $type)
			{
				$values[]	=	$o->$name;
			}

			$values[]	=	$propname;
			$values[]	=	$language;

			$results[]	=	$this->Query(
				"SELECT propvalue FROM {$tablename} WHERE {$idquery} AND propname = ? AND languageid = ?",
				$values
			)->fetch()['propvalue'];
		}

		if (!$isarray)
			return $results[0];

		return $results;
	}
}

