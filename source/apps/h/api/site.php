<?php

RequireGroup('admins');

// ====================================================================
function Run()
{
	global $results, $pathargs, $C, $D, $R, $S, $T_SYSTEM, $SETTINGS;
	
	$data					=	json_decode(file_get_contents("php://input"), true);
	$command			=	StrV($pathargs, 0);

	switch ($command)
	{
		case 'clearcache':
			$C->Clean(0, 0, 1);
			break;
		case 'save':
			$dbtype				=	substr(trim(V($data, 'dbtype')), 0, 64);
			$dbname				=	substr(trim(V($data, 'dbname')), 0, 64);
			$dbuser				=	substr(trim(V($data, 'dbuser')), 0, 64);
			$dbpassword		=	substr(V($data, 'dbpassword'), 0, 64);
			$dbhost				=	substr(trim(V($data, 'dbhost')), 0, 64);
			$dbflags			=	IntV($data, 'dbflags');
			$cssfiles			= V($data, 'cssfiles');
			$jsfiles			= V($data, 'jsfiles');
			
			if ($dbtype)
				$SETTINGS['dbtype']	= $dbtype;
				
			if ($dbname)
				$SETTINGS['dbname']	= $dbname;
				
			if ($dbuser)
				$SETTINGS['dbuser']	= $dbuser;
				
			if ($dbpassword)
				$SETTINGS['dbpassword']	= $dbpassword;
				
			if ($dbhost)
				$SETTINGS['dbhost']	= $dbhost;
				
			if ($dbflags)
				$SETTINGS['dbflags']	= $dbflags;
				
			try
			{
				$newdb	=	new DB(
					$SETTINGS['dbtype'],
					$SETTINGS['dbname'],
					$SETTINGS['dbflags'],
					$SETTINGS['dbuser'],
					$SETTINGS['dbpassword'],
					$SETTINGS['dbhost']
				);
			} catch (Exception $e)
			{
				$results['error']	= 'Could not connect to new database: ' . $e . '. Please check your settings.';
				return;
			}
			
			# Save the database settings before moving on to minify items
			file_put_contents('private/pafera.cfg', json_encode($SETTINGS, JSON_UNESCAPED_UNICODE));
				
			if ($cssfiles)
			{
				$ls					=	[];
				$allfile		= 'apps/h/css/all.css';
				
				file_put_contents($allfile, '');
				
				foreach (explode("\n", $cssfiles) as $f)
				{
					$trimmed	=	trim($f);
					
					if ($trimmed)
					{
						$ls[]	= $trimmed;
						
						if (strpos($trimmed, '.min.') === false)
						{
							$basefile	= 'apps/h/css/' . $trimmed . '.css';
							$minfile	= 'apps/h/css/' . $trimmed . '.min.css';
						
							if (!is_file($minfile) || filemtime($basefile) > filemtime($minfile))
							{
								$output	= shell_exec('yui-compressor ' 
									. escapeshellarg($basefile) . ' > ' 
									. escapeshellarg($minfile)
								);
								
								if ($output)
								{
									$results['error']	= 'Problem processing ' . $trimmed . '.css: ' . $output;
									return;
								}
							}
						}
						
						file_put_contents(
							$allfile, 
							file_get_contents('apps/h/css/' . $trimmed . '.min.css'),
							FILE_APPEND
						);
					}
				}
			
				$SETTINGS['cssfiles']	=	$ls;
			}
			
			if ($jsfiles)
			{
				$ls					=	[];
				$allfile		= 'apps/h/js/all.js';
				
				file_put_contents($allfile, '');
				
				foreach (explode("\n", $jsfiles) as $f)
				{
					$trimmed	=	trim($f);
					
					if ($trimmed)
					{
						$ls[]	= $trimmed;
						
						if (strpos($trimmed, '.min.') === false)
						{
							$basefile	= 'apps/h/js/' . $trimmed . '.js';
							$minfile	= 'apps/h/js/' . $trimmed . '.min.js';
						
							if (!is_file($minfile) || filemtime($basefile) > filemtime($minfile))
							{
								$output	= shell_exec('yui-compressor ' 
									. escapeshellarg($basefile) . ' > ' 
									. escapeshellarg($minfile)
								);
								
								if ($output)
								{
									unlink($minfile);
									$results['error']	= 'Problem processing ' . $trimmed . '.js: ' . $output;
									return;
								}
							}
						}
						
						file_put_contents(
							$allfile, 
							file_get_contents('apps/h/js/' . $trimmed . '.min.js'), 
							FILE_APPEND
						);
					}
				}
				
				$SETTINGS['jsfiles']	=	$ls;
			}
			
			shell_exec('rm -rf cache/*');
			
			file_put_contents('private/pafera.cfg', json_encode($SETTINGS, JSON_UNESCAPED_UNICODE));
			break;
		default:
			$results['error']	=	'Unknown command: ' . $command;
	};
}

Run();
