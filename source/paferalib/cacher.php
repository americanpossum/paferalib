<?php

include_once('paferalib/utils.php');
include_once('paferalib/files.php');

class Cacher
{
	// ------------------------------------------------------------------
	function __construct($cachedir = 'cache', $timeout = 3600)
	{
		$this->cachedir	=	$cachedir;
		$this->timeout	=	$timeout;

		// Periodically clean out old cache files
		if (mt_rand(1, 1000) == 1)
		{
			$this->Clean(time() - $timeout);
		}
	}

	// ------------------------------------------------------------------
	function Clean($expiredtime = 0, $path = 0, $force = 0)
	{
		if (!$expiredtime)
			$expiredtime	= time();
			
		if (!$path)
			$path	= $this->cachedir;
	
		list($dirs, $files)	=	FileDir($path)->Scan();
					
		foreach ($dirs as $d)
			$this->Clean($expiredtime, $path . '/' . $d, $force);
		
		foreach ($files as $f)
		{
			$fullpath	=	$path . '/' . $f;
			
			if ($force || filemtime($fullpath) < $expiredtime)
				unlink($fullpath);
		}
	}
	
	// ------------------------------------------------------------------
	function CacheFile($path)
	{
		return $this->cachedir . '/' . $path;
	}

	// ------------------------------------------------------------------
	function IsFresh($path, $basefile = '', $modified = 0, $timeout = 0)
	{
		global $D;
	
		# Disable code caching on development systems to always reflect the
		# latest changes to source files
		if (
			(
				strpos($path, 'models') !== false
				|| strpos($path, 'pages') !== false
			) && 
			(
				$D && !($D->flags & DB::PRODUCTION)
			)
		)
		{
			return false;
		}
	
		if (!$timeout)
			$timeout	=	$this->timeout;
			
		$cachefile	=	$this->CacheFile($path);

		if (!is_file($cachefile))	
			return false;

		$cachemtime	=	lstat($cachefile)['mtime'];

		if ($basefile && is_file($basefile) && lstat($basefile)['mtime'] > $cachemtime)
				return false;
		
		if (!$modified)
			$modified	=	time();
		
		return ($modified - $cachemtime) < $this->timeout;
	}
	
	// ------------------------------------------------------------------
	function Read($path)
	{
		$cachefile	=	$this->CacheFile($path);

		if (!is_file($cachefile))
			throw new Exception('Cache file ' . $cachefile . ' does not exist!');

		return file_get_contents($this->CacheFile($path));
	}

	// ------------------------------------------------------------------
	function Write($path, $content)
	{
		$cachefile	=	$this->CacheFile($path);
		$cachedir		=	pathinfo($cachefile, PATHINFO_DIRNAME);

		if (!is_dir($cachedir))
			mkdir($cachedir, 0777, true);

		# Use the old write to disk and rename trick to insure atomic reads
		file_put_contents($cachefile . '.cache', $content, LOCK_EX);
		@rename($cachefile . '.cache', $cachefile);
		return $this;
	}
}
