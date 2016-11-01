<?php

function Run()
{
	global $results, $pathargs, $D, $R;
	
	$data			=	json_decode(file_get_contents("php://input"), true);
	$command	=	StrV($pathargs, 0);
	
	if (!$command)
	{
		$results['error']	=	'Invalid data or command';
		return;
	}
	
	$avatarsdir	=	'apps/h/images/avatars';
	$avatars	=	[];

	foreach (FileDir($avatarsdir)->Scan()[1] as $f)
	{
		if (strpos($f, '.png') !== false)
		{
			$avatars[]	=	pathinfo($f, PATHINFO_FILENAME);
		}
	}
	
	switch ($command)
	{
		case 'list':
			$results['items']	= $avatars;
			break;
		case 'set':
			$avatar	=	trim(StrV($data, 'avatar'));
			
			if (!$avatar)
			{
				$results['error'] = 'No avatar specified';
				break;
			}		
			
			if (!in_array($avatar, $avatars))
			{
				$results['error']	=	'The avatar ' . $avatar . ' does not exist';
				break;
			}
			
			$_SESSION['possumbot']	=	$avatar;
			
			if (V($_SESSION, 'userid'))
			{
				$u	=	$D->Load('h_user', $_SESSION['userid']);
				$u->Set([
					'possumbot'	=>	$avatar,	
				]);
				$D->Save($u);
			}
			break;
		default:
			$results['error']	=	'Unknown command';
	};
}

Run();
