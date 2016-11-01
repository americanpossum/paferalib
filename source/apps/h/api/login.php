<?php

// ====================================================================
function CheckPreviousLogin($phonenumber, $place)
{
	global $D;

	$r	= $D->Query(
		"SELECT timestamp
		FROM h_loginattempts
		WHERE phonenumber = ? AND place = ?", 
		[$phonenumber, $place]
	)->fetchAll();
	
	$currenttime	=	time();
	
	// Spread out login attempts regardless of success or failure
	sleep(2);
	
	if ($r)
	{
		$timestamp	=	$r['0']['timestamp'];
	
		if ($timestamp != $currenttime)
		{
			$D->Query("UPDATE h_loginattempts
				SET timestamp = ?, ipaddress = ?
				WHERE phonenumber = ? AND place = ?", 
				[$currenttime, ip2long($_SERVER['REMOTE_ADDR']), $phonenumber, $place]
			);
		}
	
		// Only allow one login attempt every three seconds
		if ($timestamp + 3 > $currenttime)
			return 1;
	} else
	{
		$D->Query("INSERT INTO 
			h_loginattempts(timestamp, phonenumber, place, ipaddress, flags)
			VALUES(?, ?, ?, ?, ?)",
			[$currenttime, $phonenumber, $place, ip2long($_SERVER['REMOTE_ADDR']), 0]
		);
	}
	
	return 0;
}

// ====================================================================
function Run()
{
	global $results, $pathargs, $D, $R, $S, $T_SYSTEM;

	// Create login attempts class in database if necessary
	class_exists('h_loginattempt');
			
	$data					=	json_decode(file_get_contents("php://input"), true);
	$command			=	StrV($pathargs, 0);

	switch ($command)
	{
		case 'login':
			$phonenumber	=	substr(trim(V($data, 'phonenumber')), 0, 64);
			$place				=	substr(trim(V($data, 'place')), 0, 64);
			$password			=	substr(trim(V($data, 'password')), 0, 64);
			$phoneid			=	substr(trim(V($data, 'phoneid')), 0, 64);
			$model				=	substr(trim(V($data, 'model')), 0, 64);
			$product			=	substr(trim(V($data, 'product')), 0, 64);

			if (!$phonenumber || !$place || !$password)
			{
				$results['error']	=	$T_SYSTEM[48];
				break;
			}
			
			if (CheckPreviousLogin($phonenumber, $place))
			{
				$results['error']	=	$T_SYSTEM[49];
				break;
			}
			
			$u	=	$D->Find(
				'h_user',
				'WHERE phonenumber = ? AND place = ?',
				[$phonenumber, $place]
			)->One();
			
			if ($u && $u->CheckPassword($password))
			{
				// Clear old sessions
				$D->Query("UPDATE h_sessions
					SET flags = ? 
					WHERE userid = ? AND id != ?",
					[h_session::EXPIRED, $u->suid1, session_id()]
				);
				
				$S->Set(['userid'	=>	$u->suid1]);
				$_SESSION['userid']		=	$u->suid1;
				$_SESSION['username']	=	DefaultValue($u->usernames, LanguageTokens());
				$_SESSION['groups']		=	$u->Groups();
				$_SESSION['homepage']	= $u->homepage;

				if ($u->wallpaper)
				{
					$_SESSION['wallpaper']	=	$u->wallpaper;
					$_SESSION['texttheme']	=	$u->texttheme;
				}
				
				if ($phoneid && $model && $product)
				{
					$D->Query('DELETE FROM h_phonetokens
						WHERE phoneid = ? AND model = ? AND product = ?',
						[$phoneid, $model, $product]
					);
					$D->Create('h_phonetoken')
						->Set([
							'userid'		=> $u->suid1,
							'phoneid'		=> $phoneid,
							'model'			=> $model,
							'product'		=> $product,
							'expires'		=> time() + (60 * 60 * 24 * 7),
						])->Insert();
				}
				$results['homepage']	= $u->homepage;
			} else
			{
				$results['error']	=	$T_SYSTEM[49];
			}
			break;
		case 'reset':
			$phonenumber	=	substr(trim(V($data, 'phonenumber')), 0, 64);
			$place				=	substr(trim(V($data, 'place')), 0, 64);
			$email				=	substr(trim(V($data, 'email')), 0, 64);

			if ((!$phonenumber || !$place) && !$email)
			{
				$results['error']	=	$T_SYSTEM[48];
				break;
			}
			
			if (CheckPreviousLogin($phonenumber, $place))
			{
				$results['error']	=	$T_SYSTEM[49];
				break;
			}

			$u	=	$D->Find(
				'h_user',
				'WHERE (phonenumber = ? AND place = ?) OR email = ?',
				[$phonenumber, $place, $email]
			)->One();
			
			if ($u)
			{
			} else
			{
				$results['error']	=	$T_SYSTEM[49];
			}
			break;
		case 'changepassword':
			RequireLogin();
		
			$phonenumber	=	substr(trim(V($data, 'phonenumber')), 0, 64);
			$place				=	substr(trim(V($data, 'place')), 0, 64);
			$password			=	substr(trim(V($data, 'password')), 0, 64);
			
			if (!$password)
			{
				$results['error']	=	$T_SYSTEM[48];
				return;
			}
			
			$u	= $D->Load('h_user', $_SESSION['userid']);
			
			if (!$phonenumber)
				$phonenumber	= $u->phonenumber;
				
			if (!$place)
				$place	= $u->place;
			
			$u->Set([
				'phonenumber'	=>	$phonenumber,
				'place'				=>	$place,
				'password'		=>	$password,
			]);
			$u->Update();
			break;
		default:
			$results['error']	=	'Unknown command: ' . $command;
	};
}

Run();
