<?php

header('Access-Control-Allow-Origin: *');

if (isset($_SERVER['HTTP_CF_VISITOR']) 
	&& strpos($_SERVER['HTTP_CF_VISITOR'], 'https') === false
)
{
	header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	exit();
}

include_once('paferalib/init.php');
