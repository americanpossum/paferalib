<?php

// ********************************************************************
class DBTranslation implements ArrayAccess
{
	// ------------------------------------------------------------------
	public function __construct($data)
	{
		$this->data	=	$data ? $data : [];
	}

	// ------------------------------------------------------------------
	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	// ------------------------------------------------------------------
	public function offsetGet($offset)
	{
		if ($this->offsetExists($offset))
			return $this->data[$offset];
			
		return '--';
	}

	// ------------------------------------------------------------------
	public function offsetSet($offset, $value)
	{
		$this->data[$offset]	= $value;
	}

	// ------------------------------------------------------------------
	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}
	
	// ------------------------------------------------------------------
	public function ToJSON()
	{
		return json_encode($this->data, JSON_UNESCAPED_UNICODE);
	}
}

// ********************************************************************
class DBTranslator
{
	# We'll keep languageids as integers for quicker database
	# searches, then convert to IETF codes as necessary. Normal
	# sites shouldn't use more than a byte's worth (256) 
	# of storage for languageids
	public static $LANGUAGES	=	[
		1		=>	['English', 'English', ['en-us', 'en']],
		2		=>	['中文', 'Chinese', ['zh-cn', 'zh']],
		3		=>	['español', 'Spanish', ['es']],
		4		=>	['हिन्दी', 'Hindi', ['hi']],
		5		=>	['العربية/عربي', 'Arabic', ['ar']],
		6		=>	['português', 'Portuguese', ['pt']],
		7		=>	['ру́сский', 'Russian', ['ru']],
		8		=>	['日本語', 'Japanese', ['ja']],
		9		=>	['Deutsch', 'German', ['de']],
		10	=>	['한국말', 'Korean', ['ko']],
		11	=>	['italiano', 'Italian', ['it']],
		12	=>	['français', 'French', ['fr']],
	];
	
	// ------------------------------------------------------------------
	public function __construct()
	{
		$this->lang					=	1;
		$this->langcode			=	'en';
	}

	// ------------------------------------------------------------------
	public function SetLangCode($langcode)
	{
		$id	=	self::CodeToID($langcode);
		
		if (!$id)
			throw new Exception('No language with code "' . $langcode . '" available.');
		
		$this->langcode	=	$langcode;
		$this->lang			=	$id;
	}
	
	// ------------------------------------------------------------------
	public function SetLanguage($languageid)
	{
		$codes	=	self::IDToCode($languageid);
		
		if (!$codes)
			throw new Exception('No language with ID "' . $languageid . '" available.');
		
		$this->langcode	=	$codes[0];
		$this->lang			=	$languageid;
	}

	// ------------------------------------------------------------------
	public static function CodeToID($langcode)
	{
		foreach (self::$LANGUAGES as $num => $v)
		{
			foreach ($v[2] as $code)
			{
				if ($langcode == $code)
				{
					return $num;
				}
			}
		}
		
		return 0;
	}
	
	// ------------------------------------------------------------------
	public static function IDToCodes($language)
	{
		foreach (self::$LANGUAGES as $num => $v)
		{
			if ($language == $num)
			{
				return $v[2];
			}
		}
		
		return 0;
	}
	
	// ------------------------------------------------------------------
	public function Filename($collection, $langcode = 0)
	{
		global $R;

		$parts	=	explode('/', $collection);

		if (count($parts) == 2)
		{
			$app				=	$parts[0];
			$collection	=	$parts[1];
		} else
		{
			$app	=	$R->currentapp;
		}

		if (!$langcode)
			$langcode	=	$this->langcode;
			
		foreach (DBTranslator::$LANGUAGES as $num => $v)
		{
			if (in_array($langcode, $v[2]))
			{
				foreach ($v[2] as $code)
				{
					$filename	=	'apps/' . $app . '/translations/' . $code . '/' . $collection . '.json';
					
					if (file_exists($filename))
						return $filename;
				}
				break;
			}
		}

		return $filename;
	}

	// ------------------------------------------------------------------
	public function Load($collection, $langcode = 0)
	{
		$translationfile	=	$this->Filename($collection, $langcode);

		$translations	=	is_file($translationfile)
			? json_decode(file_get_contents($translationfile))
			: [];

		// Some software like to save JSON objects rather than arrays
		if (is_object($translations))
		{
			$translations	=	(array)$translations;
			$ls	=	[];

			foreach ($translations as $k => $v)
				$ls[intval($k)]	=	$v;

			$translations	=	$ls;
		}

		return new DBTranslation($translations);
	}

	// ------------------------------------------------------------------
	public function Save($collection, $translations, $langcode = 0)
	{
		$filename	=	$this->Filename($collection, $langcode);
		@mkdir(pathinfo($filename, PATHINFO_DIRNAME), 0777, 1);
		
		if (is_a($translations, 'DBTranslation'))
			$translations	=	$translations->data;

		file_put_contents($filename, json_encode($translations));

		return $this;
	}

	// ------------------------------------------------------------------
	public function ListCollections($app = '', $langcode = 0)
	{
		global $R;
		
		if (!$app)
			$app	=	$R->currentapp;

		# Set language to current language
		if (!$langcode)
			$langcode	=	$this->langcode;

		$ls	=	[];
		
		foreach (DBTranslator::$LANGUAGES as $num => $v)
		{
			if (in_array($langcode, $v[2]))
			{		
				$found	=	0;
				
				foreach ($v[2] as $code)
				{
					try
					{
						foreach (FileDir('apps/' . $app . '/translations/' . $code)->Scan()[1] as $t)
						{
							$ls[]	=	str_replace('.json', '', $t);
						}
						
						if ($ls)
							$found	=	1;
					} catch (Exception $e)
					{
					}
					
					if ($found)
						break;
				}
				
				if ($found)
					break;
			}
		}

		return $ls;
	}
	
	// ------------------------------------------------------------------
	public function CreateCollection($collection)
	{
		foreach (self::$LANGUAGES as $id => $v)
		{
			$lastcode	=	$v[2][count($v[2]) - 1];
			
			$filename	=	$this->Filename($collection, $lastcode);
			@mkdir(pathinfo($filename, PATHINFO_DIRNAME), 0777, 1);
			file_put_contents($filename, '[]');
		}
	}
	
	// ------------------------------------------------------------------
	public function RenameCollection($collection, $newname)
	{
		foreach (self::$LANGUAGES as $id => $v)
		{
			foreach ($v[2] as $code)
			{
				@rename(
					$this->Filename($collection, $code), 
					$this->Filename($newname, $code)
				);
			}
		}
	}

	// ------------------------------------------------------------------
	public function DeleteCollection($collection)
	{
		foreach (self::$LANGUAGES as $id => $v)
		{
			foreach ($v[2] as $code)
			{
				@unlink($this->Filename($collection, $code));
			}
		}
	}
};
