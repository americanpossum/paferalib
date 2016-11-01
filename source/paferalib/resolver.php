<?php

/* ********************************************************************

In order to keep URLs as short as possible, all common web facing 
directories are abbreviated using mod_rewrite. 

Here's what directory each letter maps onto inside an app:

a		api
c		css
i		images
h		home
j		javascript
m		admin

**********************************************************************/

include_once('paferalib/db.php');
include_once('paferalib/cacher.php');

/* ****************************************************************** */
class Resolver
{
	// ------------------------------------------------------------------
	function __construct($baseurl = '/')
	{
		$this->apps				=	['h'];
		$this->currentapp	=	'h';
		$this->currentfile	=	'';
		$this->fullurl		=	V($_REQUEST, 'fullurl');
		$this->phoneapp		=	V($_REQUEST, 'phoneapp');
		$this->baseurl		=	$baseurl;
		$this->logfiles		=	[];

		foreach (FileDir('apps')->Scan('', PFDir::FOLLOW_LINKS)[0] as $app)
		{
			if ($app == 'h')
				continue;
				
			$this->apps[]	=	$app;
		}
	}
	
	// ------------------------------------------------------------------
	function ListDir($path, $basepath = 0, $ls = 0)
	{
		$ls	=	$ls ? $ls : [];

		if (!$basepath)
		{
			foreach ($this->apps as $app)
			{
				$dirname	=	'apps/' . $app . '/' . $path;
				
				if (is_dir($dirname))
				{
				
					list($dirs, $files)	=	FileDir($dirname)->Scan('', PFDir::FOLLOW_LINKS);
					
					foreach ($dirs as $d)
					{
						$fullpath	=	$dirname . '/' . $d;
						$ls	=	$this->ListDir($fullpath, $d, $ls);
					}
					
					foreach ($files as $f)
					{
						$ls[]	=	($app == 'h') ? pathinfo($f, PATHINFO_FILENAME) : $app . '/' . pathinfo($f, PATHINFO_FILENAME);
					}
				}
			}
		} else
		{
			list($dirs, $files)	=	FileDir($path)->Scan('', PFDir::FOLLOW_LINKS);
			
			foreach ($dirs as $d)
			{
				$fullpath	=	$path . '/' . $d;
				$ls	=	$this->ListDir($fullpath, $basepath . '/' . $d, $ls);
			}
			
			foreach ($files as $f)
			{
				$ls[]	=	$basepath . '/' . pathinfo($f, PATHINFO_FILENAME);
			}
		}

		return array_values($ls);
	}

	// ------------------------------------------------------------------
	function Path($path)
	{
		return getcwd() . '/apps/' . $this->currentapp . '/' . $path;
	}

	// ------------------------------------------------------------------
	function DataPath($path)
	{
		$p	= getcwd() . '/data/' . $this->currentapp . '/' . $path;
		@mkdir(pathinfo($p, PATHINFO_DIRNAME), 0777, 1);
		return $p;
	}
	
	// ------------------------------------------------------------------
	function PrivatePath($path)
	{
		$p	= getcwd() . '/private/' . $this->currentapp . '/' . $path;
		@mkdir(pathinfo($p, PATHINFO_DIRNAME), 0777, 1);
		return $p;
	}
	
	// ------------------------------------------------------------------
	function Log($msg, $filename = 'log')
	{
		$logfile	=	$this->PrivatePath($filename);
		
		if (!in_array($logfile, array_keys($this->logfiles)))
			$this->logfiles[$logfile]	=	fopen($logfile, 'wb');
			
		fwrite($this->logfiles[$logfile], $msg . "\n");
	}
	
	// ------------------------------------------------------------------
	function IncludePHP($path, $app = '')
	{
		if (!$app)
			$app	=	$this->currentapp;
	
		global_include('apps/' . $app . '/' . $path);
	}

	// ------------------------------------------------------------------
	function IncludeDir($path, $app = '')
	{
		if (!$app)
			$app	=	$this->currentapp;
	
		IncludeDir('apps/' . $app . '/' . $path);
	}
		
	// ------------------------------------------------------------------
	function URL($path, $app = 0)
	{
		$app	=	$app ? $app : $this->currentapp;

		if ($this->fullurl)
			return $this->baseurl . $app . '/' . $path;

		return '/' . $app . '/' . $path;
	}

	// ------------------------------------------------------------------
	function DataURL($path, $app = 0)
	{
		$app	=	$app ? $app : $this->currentapp;

		if ($this->fullurl)
			return $this->baseurl . 'd/' . $app . '/' . $path;

		return '/d/' . $app . '/' . $path;
	}
	
	// ------------------------------------------------------------------
	function ImageURL($path, $app = '')
	{
		$app	=	$app ? $app : $this->currentapp;

		return ($this->fullurl ? $this->baseurl : '/') . 'i/' . $app . '/' . $path;
	}
	
	// ------------------------------------------------------------------
	function TranslatedImageURL($path, $app = '')
	{
		$app	=	$app ? $app : $this->currentapp;
		$info	=	pathinfo($path);
		
		$lang	=	$_SESSION['lang'];
		
		foreach (DBTranslator::$LANGUAGES as $num => $v)
		{
			if ($num == $lang)
			{		
				foreach ($v[2] as $code)
				{
					$filename	= 'apps/' . $app . '/images/' . ($info['dirname'] ? $info['dirname'] . '/' : '') . $info['filename'] . '-' . $code . '.' . $info['extension'];
						
						if (is_file($filename))
							return ($this->fullurl ? $this->baseurl : '/') . 'i/' . $app . '/' . ($info['dirname'] ? $info['dirname'] . '/' : '') . $info['filename'] . '-' . $code . '.' . $info['extension'];
				}
			}
		}

		return "";
	}
	
	// ------------------------------------------------------------------
	function IconURL($name, $app = '')
	{
		$app	=	$app ? $app : $this->currentapp;
	
		return ($this->fullurl ? $this->baseurl : '/') . 'i/' . $app . '/icons.svg#' . $name;
	}
	
	// ------------------------------------------------------------------
	function Icon($name, $app = '', $classes = '', $attrs = '')
	{
		$path	= $this->IconURL($name, $app);
		
		return <<<EOT
	<img src="{$path}" class="lazyload {$classes}" {$attrs}>
EOT;
	}
	
	// ------------------------------------------------------------------
	function IMG($path, $app = '', $classes = '', $attrs = '')
	{
		$app	=	$app ? $app : $this->currentapp;
		$path	=	'/i/' . $app . '/' . $path;
		return <<<EOT
	<img src="{$path}" class="lazyload {$classes}" {$attrs}>
EOT;
	}

	// ------------------------------------------------------------------
	function JSURL($path, $app = '')
	{
		$app	=	$app ? $app : $this->currentapp;
		return '/j/' . $app . '/' . $path . '.js';
	}

	// ------------------------------------------------------------------
	function CSSURL($path, $app = '')
	{
		$app	=	$app ? $app : $this->currentapp;
		return '/c/' . $app . '/' . $path . '.css';
	}

	// ------------------------------------------------------------------
	function HasGroup($groups)
	{
		if (!is_array($groups))
			$groups	=	[$groups];
			
		foreach ($groups as $group)
		{
			if (in_array($group, $_SESSION['groups']))
				return 1;
		}
		
		return 0;
	}
	
	// ------------------------------------------------------------------
	function Resolve()
	{
		global $D, $T, $pathargs, $C, $S, $T_SYSTEM, $T_MAIN, $R;
		
		$page = new h_page();
		
		if ($T_SYSTEM)
			$page->title	=	$T_SYSTEM[2];

		$webroot	= $_SERVER['DOCUMENT_ROOT'];
		$url			=	$_SERVER['REQUEST_URI'];

		$pos	=	strpos($url, '?');

		if ($pos !== false)
			$url	=	substr($url, 0, $pos);

		$includefile	=	0;

		$parts	=	[];

		foreach (explode('/', $url) as $p)
		{
			if ($p != '')
			{
				# Some servers keep the query string as part of the URL, 
				# so we manually strip it out
				$amppos	= strpos($p, '&');
				
				if ($amppos !== false)
				{
					$parts[]	= substr($p, 0, $amppos);
					break;
				}
				
				$parts[]	=	$p;
			}
		}
		
		$isapi		=	0;
		$isadmin	=	0;
		
		if (!$parts || ($parts && $parts[0] == 'a'))
			$S->Set(['numpagesvisited' => $S->numpagesvisited + 1]);

		if ($parts)
		{
			$isapi		=	$parts[0] == 'a';
			$isadmin	=	$parts[0] == 'm';
		
			if (count($parts) == 1)
			{
				$app	=	'h';
			} else
			{
				$app	=	array_shift($parts);
			}
			
			for ($i = count($parts); $i >= 0; $i--)
			{
				$url	=	join('/', array_slice($parts, 0, $i));
				
				if ($isapi)
				{
					if ($i == 0)
					{
						$filename	=	'apps/h/api/' . $parts[0];
						$pathargs	=	array_slice($parts, 1);
					} else
					{
						$filename	=	'apps/' . $parts[0] . '/api/' . join('/', array_slice($parts, 1, $i));
						$pathargs	=	array_slice($parts, $i + 1);
					}
				} else if ($isadmin)
				{
					if ($i == 0)
					{
						$filename	=	'apps/h/admin/' . $parts[0];
						$pathargs	=	array_slice($parts, 1);
					} else
					{
						$filename	=	'apps/' . $parts[0] . '/admin/' . join('/', array_slice($parts, 1, $i));
						$pathargs	=	array_slice($parts, $i + 1);
					}
				} else
				{
					$filename	=	'apps/' . $app . '/pages/' . $url;
					$pathargs	=	array_slice($parts, $i);
				}
				
				if (is_file($filename . '.php'))
				{
					if ($isapi || $isadmin)
						$app	=	($i == 0) ? 'h' : $parts[0];
						
					$includefile			=	$filename . '.php';
					$parts	=	explode('/', $filename);
					$this->currentfile	=	$parts[1] . '/' . join('/', array_slice($parts, 3));
					break;
				} else if (is_file($filename . '/index.php'))
				{
					$newurl			=	join(
						'/', 
						array_merge(
							array_slice($parts, 0, $i + 1), 
							['index'], 
							array_slice($parts, $i + 1)
						)
					);
				
					header('Location: ' . $this->baseurl . $newurl);
					exit();
				
					if ($isapi || $isadmin)
						$app	=	($i == 0) ? 'h' : $parts[0];
						
					$includefile			=	$filename . '/index.php';
					$parts	=	explode('/', $filename);
					$this->currentfile	=	$parts[1] . '/' . join('/', array_slice($parts, 3));
					break;
				}
			}
			
			if (!$includefile)
			{
				if ($isapi)
				{
					header('Content-type: application/json');
					echo '{"error":"API not found"}';
					exit();
				} 
				{
					header('HTTP/1.0 404 Not Found');
					$app	=	'h';
					$includefile	=	'apps/h/pages/404.php';
				}
			}
		} else
		{
			$app	=	'h';
			$includefile	=	'apps/h/pages/index.php';
			$this->currentfile	=	'';
		}
			
		$this->currentapp	=	$app;
		$config	=	'apps/' . $app . '/config.php';

		if (file_exists($config))
			include_once($config);
			
		// APIs don't return pages (usually JSON)
		if ($isapi)
		{
			global $results;
			
			$results	= [];
			
			header('Content-type: application/json');
			
			try
			{
				include($includefile);
			} catch (Exception $e)
			{
				$results['error']	= $e->getMessage();
			}
			
			echo json_encode($results);
			exit();
		}

		// Admin pages aren't cacheable by default
		if ($parts && $parts[0] == 'm')
			$page->Cache(0);
	
		$cachefile	=	'pages/' . $app . '/' . join('_', $parts)
			. (V($_REQUEST, 'contentonly') ? '1' : '0')
			. (V($_REQUEST, 'fullurl') ? '1' : '0')
			. (V($_REQUEST, 'phoneapp') ? '1' : '0')
			. '-' . $D->langcode;
	
		if (!($page->flags && h_page::DONT_CACHE)
			&& $C->IsFresh($cachefile, $includefile, time() - 60000)
		)
		{
			echo $page->RunPlugins($C->Read($cachefile));
		} else
		{
			ob_start();
			
			if ($D->flags & DB::DEBUG)
			{
				include($includefile);
			} else
			{
				try
				{
					include($includefile);
				} catch (Exception $e)
				{
					echo '<pre class=Error>' . $e->getMessage();
					echo '</pre>';
				}
			}
			
			$page->content[]	=	ob_get_clean();

			$content	=	$page->Render();
			
			if (!$page->flags & h_page::DONT_CACHE)
				$C->Write($cachefile, $content);

			echo $page->RunPlugins($content);
		}
		
		exit();
	}
}
