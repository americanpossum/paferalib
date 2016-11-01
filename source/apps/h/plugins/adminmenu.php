<?php

global $R;

try
{
	// ====================================================================
	// Setup user bar
	if (IntV($_SESSION, 'userid') == 1 || in_array('admins', $_SESSION['groups']))
	{
		$pages	=	json_encode(array_values($R->ListDir('admin')));
		$settingsicon	=	$R->Icon('Settings', 'h');
		
		$ls	=	[<<<EOT
		<div class=AdminMenu>
			{$settingsicon}
			<script>
adminpages	=	{$pages};
			</script>
		</div>
EOT
		];
		
	
		$args['0']	=	join("\n", $ls);
	} else
	{
		$args['0']	=	'';
	}
} catch (Exception $e)
{
	$error	=	$e->getMessage();
}
