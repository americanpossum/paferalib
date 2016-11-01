<?php

try
{
	global $T_SYSTEM, $R;

	$usericon	=	$_SESSION['userid'] 
		? '<img src="' . h_user::HeadshotURL($_SESSION['userid']) . '" />' 
		: $R->Icon('User', 'h');
		
	$userlink	=	<<<EOT
	<div class=UserMenu>
		{$usericon}
	</div>
EOT;

	$args['0']	=	$userlink;
} catch (Exception $e)
{
	$error	=	$e->getMessage();
}
