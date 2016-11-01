<?php

// ====================================================================
function OnFatalError()
{
	$e	=	error_get_last();
	
	if ($e['type'] != 1)
		return;
	
	$e['time']	=	DB::Date();
	ob_start();
	print_r($e);
	debug_print_backtrace();
	$error	=	ob_get_clean();
	echo $error;
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/lasterror.log', $error, FILE_APPEND | LOCK_EX);
}

register_shutdown_function("OnFatalError");

// ====================================================================
function __autoload($modelname)
{
	global $C, $D, $R, $S;

	list($app, $model)	=	explode('_', $modelname);
	
	$fromfile		=	'apps/' . $app . '/models/' . $model . '.php';
	$cachepath	=	'models/' . $modelname;
	
	if (!$C->IsFresh($cachepath, $fromfile, 0, 86400))
	{
		$contents	=	str_replace(
			[
				'templateclass',
				'templateapp',
			],
			[
				$modelname,
				$app,
			],
			file_get_contents($fromfile)
		);
	
		$C->Write($cachepath, $contents);
		
		# We've had some problems with the disk file not being fully written,
		# so we're using eval instead. It's a small speed penalty for much
		# more stable requests.
		if ($D->flags & DB::PRODUCTION)
		{
			eval(substr($contents, 5));
		} else
		{
			# Non-production sites still use the disk cache for easier debugging
			include_once($C->CacheFile($cachepath));	
		}
	} else
	{
		$modelpath		=	$C->CacheFile($cachepath);
		
		if (!include_once($modelpath))
			throw new Exception('Could not load ' . $modelpath);
	}
	
	if (!class_exists($modelname, 0))
		throw new Exception('Could not autoload ' . $modelname);
		
	$D->Register($modelname);
}

function SetupInitialDatabase($dbnum = '')
{
	global $SETTINGS;
	
	$SETTINGS['dbtype']			=	'sqlite';
	$SETTINGS['dbhost']			=	'localhost';
	$SETTINGS['dbname']			=	'private/web' . $dbnum . '.db';
	$SETTINGS['dbuser']			= '';
	$SETTINGS['dbpassword']	= '';
	$SETTINGS['cssfiles']		= ["normalize","colors","common","paferalib","pafera"];
	$SETTINGS['jsfiles']		= ["loader.min","lazysizes","minified","stacktrace.min","interact","paferalib","paferadb","paferapage","pafera"];
	
	file_put_contents('private/pafera.cfg', json_encode($SETTINGS, JSON_UNESCAPED_UNICODE));
}

include_once('paferalib/webutils.php');
include_once('paferalib/resolver.php');

// ====================================================================
// Include all remaining library files
IncludeDir('paferalib');

ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 7);
ini_set('session.cookie_lifetime', 60 * 60 * 24 * 7);
ini_set('session.hash_function', 1);

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('UTC');

$SETTINGS	= [];

if (is_file('private/pafera.cfg'))
	$SETTINGS	= json_decode(file_get_contents('private/pafera.cfg'), 1);

if (!$SETTINGS)
	$SETTINGS	= [];

// ====================================================================
// Set default settings for first use
if (!V($SETTINGS, 'dbtype'))
	SetupInitialDatabase();

if (!V($SETTINGS, 'dbflags'))
	$SETTINGS['dbflags']	=	0;

ini_set('display_errors', ($SETTINGS['dbflags'] & DB::PRODUCTION) ? 'off' : 'on');	

$R	=	new Resolver('/');
$C	=	new Cacher();
$T	=	new DBTranslator();

try
{
	$D	=	new DB(
		$SETTINGS['dbtype'],
		$SETTINGS['dbname'],
		$SETTINGS['dbflags'],
		$SETTINGS['dbuser'],
		$SETTINGS['dbpassword'],
		$SETTINGS['dbhost']
	);
	
} catch (Exception $e)
{
	# Try a number of databases in case of file corruption
	for ($i = 1; $i < 100; $i++)
	{
		try
		{
			SetupInitialDatabase($i);
			
			$D	=	new DB(
				$SETTINGS['dbtype'],
				$SETTINGS['dbname'],
				$SETTINGS['dbflags'],
				$SETTINGS['dbuser'],
				$SETTINGS['dbpassword'],
				$SETTINGS['dbhost']
			);
			
			break;
		} catch (Exception $e)
		{
			echo "Problem setting up database: " . $e . "\n";
		}
	}
	
	echo "There was a problem connecting to your database: " . $e . "

We've restored the default sqlite database for you to use while you sort this problem out.";
	exit();
}

// Initialize database and session
$D->InitData();

$S	=	new h_session($D);

// ====================================================================
// Require login for expired sessions
if ($S->flags & h_session::EXPIRED)
{
	$userid		= $S->userid;
	$_SESSION	=	[];

	if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
					$params["path"], $params["domain"],
					$params["secure"], $params["httponly"]
			);
	}
	
	session_destroy();
	session_start();

	if ($userid)
	{
		header('Location: /login?nextpage=' . urlencode($_SERVER['REQUEST_URI']));
		exit();
	} 
}

if (!V($_SESSION, 'lang'))
	SetupSession();

$token				=	StrV($_REQUEST, 'authenticationtoken');
$currenttime	=	time();

if ($token)
{
	$t	=	$D->Find('h_authenticationtoken', 'WHERE token = ?', $token)->One();
	
	if ($t && $t->expires > $currenttime)
	{
		if ($t->flags & h_authenticationtoken::SINGLE_USE)
			$t->Delete();
			
		$u	=	$D->Load('h_user', $t->userid);
		
		$_SESSION['userid']	=	$t->userid;
		$_SESSION['username']	=	$u->username_translated;
		$_SESSION['groups']		=	$u->Groups();

		if ($u->wallpaper)
		{
			$_SESSION['wallpaper']	=	$u->wallpaper;
			$_SESSION['texttheme']	=	$u->texttheme;
		}
	}
	
	$D->Query('DELETE FROM h_authenticationtokens WHERE expires < ?', $currenttime);
}

$D->userid		=	IntV($_SESSION, 'userid');
$D->groups		=	ArrayV($_SESSION, 'groups');
$D->language	=	IntV($_SESSION, 'lang', 1);
$D->langcode	=	StrV($_SESSION, 'langcode');

$T->SetLangCode($_SESSION['langcode']);

$T_SYSTEM	=	$T->Load('h/system');
$T_MAIN		=	$T->Load('h/main');

$R->Resolve();

