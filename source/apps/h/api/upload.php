<?php

RequireLogin(1);

function Run()
{
	global $results, $R, $T, $D, $pathargs;
	
	$fileclass	=	$R->currentapp . '_file';
	
	$purpose	=	StrV($pathargs, 0);
	$usercode	=	StrV($pathargs, 1);
	$filename	=	StrV($pathargs, 2);
	
	# Delete any uploads left after an hour
	try
	{
		$deletebefore	=	time() - 3600;
	
		foreach (FileDir('uploads')->Scan()[1] as $f)
		{
			$fullpath	=	'uploads/' . $f;
		
			if (filemtime($fullpath) < $deletebefore)
				@unlink(@fullpath);
		}
	} catch (Exception $e)
	{
	}

	try
	{
		$infile				=	@fopen('php://input', 'rb');
		$uploadedfile	=	'uploads/' . UUID4();
		$outfile			=	@fopen($uploadedfile, 'wb');
		
		while(!feof($infile))
			fwrite($outfile, fread($infile, 262144));
		
		fclose($infile);
		fclose($outfile);
	} catch (Exception $e)
	{
		$results['error']	=	$e->getMessage();
		return;
	}
	
	switch ($purpose)
	{
		case 'file':
			RequireLogin();
			
			$fileid	=	$D->Create($fileclass)
					->Set([
						'flags' 		=> $fileclass::UNLINKED,
						'title'			=> StrV($pathargs, 1),
						'modified'	=> DB::Date(),
						'size'			=> filesize($uploadedfile)
					])->Insert()->suid1;
					
			$fullpath	=	$R->DataPath('f/' . IDToAlnumPath($fileid)[2]);
			@mkdir(pathinfo($fullpath, PATHINFO_DIRNAME), 0777, 1);
			rename($uploadedfile, $fullpath);
		
			$results['id']	=	ToShortCode($fileid);
			break;
		case 'delete':
			$fileid	=	StrV($pathargs, 1);
			
			if (!$fileid)
			{
				$results['error']	=	$T_SYSTEM[48];
				return;
			}
			break;
		case 'headshot':
			if (!$usercode)
			{
				$results['error']	=	$T_SYSTEM[48];
				break;
			}
			
			$u			=	$D->Load('h_user', FromShortCode($usercode));
			
			if (!($D->Access('h_user', $u) & DB::CAN_CHANGE))
			{
				$results['error']	=	$T_SYSTEM[12];
				break;
			}
			
			$tofile			=	h_user::HeadshotFile($u->suid1);
			@mkdir(pathinfo($tofile, PATHINFO_DIRNAME), 0777, 1);
			$thumbnail	= imagecreatetruecolor(128, 128);

			$jpg				=	imagecreatefromjpeg($uploadedfile);
			list($width, $height) = getimagesize($uploadedfile);

			imagecopyresized($thumbnail, $jpg, 0, 0, 0, 0, 128, 128, $width, $height);

			imagejpeg($thumbnail, $tofile, 50);
			unlink($uploadedfile);
			break;
		case 'wallpaper':
			RequireGroup('admins');

			$jpg				=	imagecreatefromjpeg($uploadedfile);
			list($width, $height) = getimagesize($uploadedfile);

			if ($width != 1920 && $height != 1080)
			{
				$results['error']	=	'Wallpapers must be 1920x1080!';
				break;
			}

			$filename	=	pathinfo($filename, PATHINFO_FILENAME);

			$parts	=	explode('-', $filename);

			if (count($parts) > 1)
				$filename	=	$parts[0];

			$tofile			=	getcwd() . "/apps/main/images/wallpapers/{$filename}-light-thumb.jpg";
			$thumbnail	= imagecreatetruecolor(192, 108);
			imagecopyresized($thumbnail, $jpg, 0, 0, 0, 0, 192, 108, $width, $height);
			imagejpeg($thumbnail, $tofile, 30);

			$tofile			=	getcwd() . "/apps/main/images/wallpapers/{$filename}-large.jpg";
			$thumbnail	= imagecreatetruecolor(1920, 1080);
			imagecopyresized($thumbnail, $jpg, 0, 0, 0, 0, 1920, 1080, $width, $height);
			imagejpeg($thumbnail, $tofile, 30);

			$tofile			=	getcwd() . "/apps/main/images/wallpapers/{$filename}-medium.jpg";
			$thumbnail	= imagecreatetruecolor(1280, 720);
			imagecopyresized($thumbnail, $jpg, 0, 0, 0, 0, 1280, 720, $width, $height);
			imagejpeg($thumbnail, $tofile, 30);

			$tofile			=	getcwd() . "/apps/main/images/wallpapers/{$filename}-small.jpg";
			$thumbnail	= imagecreatetruecolor(720, 405);
			imagecopyresized($thumbnail, $jpg, 0, 0, 0, 0, 720, 405, $width, $height);
			imagejpeg($thumbnail, $tofile, 30);

			unlink($uploadedfile);
			break;
		default:
			$results['error']	=	'No file role specified!';
	};
}

Run();
