<?php

include_once('paferalib/utils.php');

// ====================================================================
function IncludeDir($dirpath)
{
	if (!is_dir($dirpath))
		return;

  $dirh = opendir($dirpath);

  while ($file = readdir($dirh))
  {
    $path = $dirpath . '/' . $file;

		if ($file == '..' or $file == '.')
			continue;

    if (is_dir($path))
    {
      IncludeDir($path);
    } else
    {
			if (strlen($file) < 4 || substr($file, -4) != '.php')
				continue;

			global_include($path);
    }
  }
  closedir($dirh);
}

// ====================================================================
function RequireLogin($isapi = False)
{
	global $T, $_resolver, $T_SYSTEM;
	
	if (!V($_SESSION, 'userid'))
	{
		if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false)
		{
			header('Content-type: application/json');
			echo json_encode(['error' => $T_SYSTEM[51]]);
		} else
		{
			header('Location: ' .  $_resolver->baseurl . '/login?nextpage=' . urlencode($_SERVER['REQUEST_URI']) . ($_SERVER['QUERY_STRING'] ? '&' . $_SERVER['QUERY_STRING'] : ''));
		}
		exit();
	}
}

// ====================================================================
function RequireGroup($groups, $isapi = False)
{
	global $T, $D, $_resolver, $T_SYSTEM;

	if (IntV($_SESSION, 'userid') == 1)
		return;

	RequireLogin();

	if (!is_array($groups))
		$groups	=	[$groups];
		
	foreach ($groups as $group)
	{
		if (in_array($group, $_SESSION['groups']))
			return;
	}

	if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false)
	{
		header('Content-type: application/json');
		echo json_encode(['error' => $T_SYSTEM[50]]);
	} else
	{
		throw new Exception($T_SYSTEM[50]);
	}
	exit();
}

// ====================================================================
function LanguageTokens()
{
	$ls	 = [];
	
	if (V($_SERVER, 'HTTP_ACCEPT_LANGUAGE'))
	{
		foreach (explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $line)
		{
			foreach (explode(',', $line) as $token)
			{
				// Standardize language codes
				$ls[]	=	trim(str_replace('_', '-', strtolower($token)));
			}
		}
	}
	
	return $ls;
}

// ====================================================================
function SetupSession()
{
	global $S, $D;

	$_SESSION['wallpaper']	=	'blue';
	$_SESSION['texttheme']	=	'dark';
	$_SESSION['userid']			=	0;
	$_SESSION['username']		=	'';
	$_SESSION['groups']			=	[];
	$_SESSION['lang']				=	1;
	$_SESSION['langcode']		=	'en';
	$_SESSION['possumbot']	=	'possum';

	foreach (LanguageTokens() as $token)
	{
		foreach (DBTranslator::$LANGUAGES as $num => $v)
		{
			foreach ($v[2] as $code)
			{
				if ($token == $code)
				{
					$_SESSION['lang']			=	$num;
					$_SESSION['langcode']	=	$code;
					$D->language	=	$num;
					goto FoundLang;
				}
			}
		}
	}

	FoundLang:
}

// ====================================================================
// readfile with HTTP ranges support
// Adapted from gaosipov at gmail dot com's version
function SendFile(
	$path, 
	$downloadfilename = '', 
	$forcedownload 		= false,
	$blocksize				=	1024 * 16
)
{
	if (!file_exists($path))
  { 
		header ("HTTP/1.0 404 Not Found");
    return;
  }
	
	if (!$downloadfilename)
		$downloadfilename	=	pathinfo($path, PATHINFO_BASENAME);
		
	$stats	=	stat($path);	
	$size		=	$stats['size'];
	
  $fm	=	@fopen($path, 'rb');
	
  if (!$fm)
		throw new Exception('Could not open file ' . $path);
 
  $begin	= 0;
  $end		= $size;
 
  if (V($_SERVER, 'HTTP_RANGE'))
  { 
		if (preg_match(
			'/bytes=\h*(\d+)-(\d*)[\D.*]?/i', 
			$_SERVER['HTTP_RANGE'], 
			$matches)
		)
    { 
			$begin	= intval($matches[0]);
			
      if (!empty($matches[1]))
        $end	=	intval($matches[1]);
    }
  }
	
	if ($begin > $end || $begin > ($size - 1) || $end > $size)
	{
		header('HTTP/1.1 416 Requested Range Not Satisfiable');
		header("Content-Range: bytes $begin-$end/$size");
		exit();
	}
 
  if ($begin > 0 || $end < $size)
	{
		header('HTTP/1.0 206 Partial Content');
	} else
	{
    header('HTTP/1.0 200 OK'); 
	}
 
	$finfo = finfo_open(FILEINFO_MIME_TYPE);

	header('Content-Type: ' . finfo_file($finfo, $path));	
	
	$ifmodified	=	V($_SERVER, 'HTTP_IF_MODIFIED_SINCE') 
		? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) 
		: false;	
	
	if ($ifmodified == $stats['mtime'])
	{
		header("HTTP/1.1 304 Not Modified");
		exit();
	} else
	{
		header("Last-Modified: " . date($stats['mtime']));
	}
	
  header('Cache-Control: no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Accept-Ranges: 0-' . $size);
  header('Content-Length:' . ($end - $begin));
	
	if ($forcedownload)
	{
		header('Content-Disposition: attachment; filename="' . $downloadfilename . '"');
		
		// Extra headers for stupid IE
		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	} else
	{
		header('Content-Disposition: inline; filename="' . $downloadfilename . '"');
	}
	
  header('Connection: close'); 
 
  $cur	=	$begin;
  fseek($fm, $begin, 0);

	while (ob_get_level())
		ob_end_clean();
	
  while(!feof($fm) && $cur < $end && (connection_status()==0))
  { 
		print fread($fm, min($blocksize, $end - $cur));
    $cur	+=	$blocksize;
  }
}
