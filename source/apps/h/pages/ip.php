<?php

function Run()
{
	global $pathargs;

	$command	=	StrV($pathargs, 0);
	$arg			=	StrV($pathargs, 1);
	
	if (!$command || !$arg)
	{
		echo 'Invalid command or arg';
		return;
	}
	
	switch ($command)
	{
		case 'set':
			if ($arg != '9h89h9ahsdvdpoh3t8')
			{
				echo 'Invalid token';
				return;
			}
		
			$ip	=	$_SERVER['REMOTE_ADDR'];
			
			try
			{
				$f	=	fopen('homeip', 'r');
				$oldip	=	fread($f, 15);
				fclose($f);
				
				if ($oldip == $ip)
				{
					echo "IP hasn't changed.";
					return;
				}
			} catch (Exception $e)
			{
			}
			
			$f	=	fopen('homeip', 'w');
			fwrite($f, $ip);
			fclose($f);
			
			echo 'Set new IP to ' . $ip . '.';
			break;
		case 'get':
			$f	=	fopen('homeip', 'r');
			$ip	=	fread($f, 15);
			fclose($f);
			
			if (!$ip)
			{
				echo "No IP has been set.";
				return;
			}
			
			switch ($arg)
			{
				case 'tnl':
					$port	=	52000;
					break;
				case 'bedcam':
					$port	=	53000;
					break;
			};
			
			header("Location: http://{$ip}:{$port}");
			break;
		default:
			echo 'Unknown command';
	};
}

Run();
exit();
