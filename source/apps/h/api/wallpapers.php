<?php

function Run()
{
	global $results, $pathargs, $D, $R;
	
	$data			=	json_decode(file_get_contents("php://input"), true);
	$command	=	StrV($pathargs, 0);
	
	if (!$data || !$command)
	{
		$results['error']	=	'Invalid data or command';
		return;
	}
	
	$wallpaperdir	=	'apps/h/images/wallpapers';
	$wallpapers	=	[];

	foreach (FileDir($wallpaperdir)->Scan()[1] as $f)
	{
		if (strpos($f, '-thumb.jpg') !== FALSE)
		{
			$parts	=	explode('-', $f);
			$wallpapers[$parts[0]]	=	$parts[1];
		}
	}
	
	$themes	=	[];
	
	foreach ($R->ListDir('css/themes') as $f)
	{
		$themes[]	=	pathinfo($f, PATHINFO_FILENAME);
	}

	switch ($command)
	{
		case 'list':
			$results['items']	= $wallpapers;
			break;
		case 'set':
			$filename	=	StrV($data, 'filename');
			
			if (!$filename)
			{
				$results['error'] = 'No filename specified';
				break;
			}		
			
			if (!in_array($filename, array_keys($wallpapers)))
			{
				$results['error']	=	'The wallpaper ' . $filename . ' does not exist';
				break;
			}
			
			$_SESSION['wallpaper']	=	$filename;
			$_SESSION['texttheme']	=	$wallpapers[$filename];
			
			if (V($_SESSION, 'userid'))
			{
				$u	=	$D->Load('h_user', $_SESSION['userid']);
				$u->Set([
					'wallpaper'	=>	$filename,	
					'texttheme'	=>	$wallpapers[$filename],
				]);
				$D->Save($u);
			}
			break;
		case 'settheme':
			RequireGroup('admins');
			
			$filename	=	StrV($data, 'filename');
			$theme		=	StrV($data, 'theme');
			
			if (!in_array($filename, array_keys($wallpapers)))
			{
				$results['error']	=	'The wallpaper ' . $filename . ' does not exist';
				break;
			}
			
			if (!in_array($theme, $themes))
			{
				$results['error']	=	'The theme ' . $theme . ' does not exist';
				break;
			}
			
			foreach (FileDir($wallpaperdir)->Scan()[1] as $f)
			{
				if (strpos($f, $filename) !== false
					&& strpos($f, '-thumb.jpg') !== false
				)
				{
					rename($wallpaperdir . '/' . $f, $wallpaperdir . '/' . $filename . '-' . $theme . '-thumb.jpg');
					break;
				}
			}
		default:
			$results['error']	=	'Unknown command';
	};
}

Run();
