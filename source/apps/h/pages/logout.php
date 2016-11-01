<?php

$T_MAIN	=	$T->Load('main');
$page->Cache(0);
$page->title	=	$T_MAIN[7];

session_destroy();
session_write_close();

$loginurl	=	$R->URL('login', 'h');
echo <<<EOT
<h1>{$T_MAIN[7]}</h1>
<p>{$T_MAIN[8]}</p>
<div class=ButtonBar>
	<a class="Color3 Button" href="{$loginurl}">{$T_MAIN[9]}</a>
	<a class="Color4 Button" href="{$R->baseurl}">{$T_MAIN[6]}</a>
</div>
EOT;
