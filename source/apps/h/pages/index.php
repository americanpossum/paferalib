<?php

global $R;

$newurl	=	$R->baseurl . 'learn/index';

if ($_SERVER['QUERY_STRING'])
	$newurl	.= '?' . $_SERVER['QUERY_STRING'];

header("Location: " . $newurl);

$T_MAIN	=	$T->Load('h/main');
?>

<p><a href="<?=$newurl?>"><?=$T_MAIN[31]?></a></p>