<?php

$page->title	=	"Permissions";

$apps		= [];

foreach ($D->Find('h_dbtype', '', '', ['limit' => 99999]) as $r)
{
	$appname	= explode('_', $r->typename)[0];
	
	if (!isset($apps[$appname]))
		$apps[$appname]	= [];
		
	$apps[$appname][$r->typename]	= $r->ToJSON();
}

?>

<div class=TopBar></div>
<br class=Cleared>
<br class=Cleared>

<div class=Types></div>
<br class=Cleared>

<div class=BottomBar></div>
<br class=Cleared>

<div class=ObjectResults></div>
<br class=Cleared>

<script>

apps	= <?=json_encode($apps)?>;

// ====================================================================
function Query()
{
}

$.ready(
	function()
	{
		_loader.Load(
			P.JSURL('permissions')
		);
		
		_loader.OnFinished(
			function()
			{
			}
		);
	}
);

</script>
