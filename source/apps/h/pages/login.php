<?php

global $T_SYSTEM, $T, $D, $R;

$nextpage	=	StrV($_REQUEST, 'nextpage') 
	? $_REQUEST['nextpage'] 
	: StrV($_SESSION, 'homepage');
	
if ($nextpage && $nextpage[0] == '/')
	$nextpage	=	substr($nextpage, 1);

if ($_SESSION['userid'])
{
	header('Location: ' . $nextpage);
	echo '<a href="' . $nextpage . '>' . $T_SYSTEM[34] . '</a>';
	exit();
}

$page->title	=	$T_SYSTEM[15];

$phoneid	=	StrV($_REQUEST, 'phoneid');
$model		=	StrV($_REQUEST, 'model');
$product	=	StrV($_REQUEST, 'product');

$t	=	$D->Find('h_phonetoken',
	'WHERE phoneid = ? AND model = ? AND product = ?',
	[$phoneid, $model, $product]
)->One();

$u	=	$t ? $D->Load('h_user', $t->userid) : 0;

if ($u && $t->expires > time())
{
	$_SESSION['userid']		=	$u->suid1;
	$_SESSION['groups']		=	$u->Groups();
	$_SESSION['username']	=	$u->Username();
	$_SESSION['homepage']	= $u->homepage;

	if ($u->wallpaper)
	{
		$_SESSION['wallpaper']	=	$u->wallpaper;
		$_SESSION['texttheme']	=	$u->texttheme;
	}
	
	$homepage	=	$nextpage ? $nextpage : $R->baseurl . $_SESSION['homepage'];
	header('Location: ' . $nextpage);
	echo '<a href="' . $nextpage . '>' . $T_SYSTEM[34] . '</a>';
	exit();
}

if ($phoneid && $model && $product)
{
	$iconurl	=	$R->IconURL('phone', 'h');
	
	echo <<<EOT
<p class=Center>
<img src="{$iconurl}"><br>
{$model} - {$product}
</p>
EOT;
}
?>

<div class=FormFields>
	<div class=Login>
		<h2 class=Center><?=$T_SYSTEM[15]?></h2>
		<div class=LoginForm></div>
	</div>
	<div class=Reset>
		<h2 class=Center><?=$T_SYSTEM[45]?></h2>
		<div class=ResetForm></div>
	</div>
</div>

<script>

var phoneid		=	'<?=$phoneid?>';
var model			=	'<?=$model?>';
var product		=	'<?=$product?>';
var nextpage	=	'<?=$nextpage?>';

function Login(el, fields)
{
	if (!fields.phonenumber
		|| !fields.place
		|| !fields.password
	)
	{
		P.ErrorPopup('<?=$T_SYSTEM[48]?>');
		return;
	}
	
	fields.phoneid	= phoneid;
	fields.model		= model;
	fields.product	= product;
	
	P.LoadingAPI(
		'.LoginFormResults',
		'login/login',
		fields,
		function(d, resultsdiv)
		{
			window.location	=	P.baseurl + nextpage;
		}
	);
}

function Reset(el, fields)
{
	if ((!fields.phonenumber || !fields.place)
		&& !fields.email
	)
	{
		P.ErrorPopup('<?=$T_SYSTEM[48]?>');
		return;
	}
	
	P.LoadingAPI(
		'.ResetFormResults',
		'login/reset',
		fields,
		function(d, resultsdiv)
		{
			$(resultsdiv).ht('<div class="greenb Pad50"><?=$T_SYSTEM[47]?></div>');
		}
	);
}

function SetupForm()
{

	P.EditPopup(
		[
			['phonenumber', 'text', '', '<?=$T_SYSTEM[36]?>'],
			['place', 'text', '', '<?=$T_SYSTEM[43]?>'],
			['email', 'text', '', '<?=$T_SYSTEM[44]?>'],
		],
		Reset,
		{
			formdiv:					'ResetForm',
			gobuttontext:			T[46], 
			cancelbuttontext:	T[1],
			cancelfunc:				function()
				{
					window.history.back();
				}
		}
	);
	
	P.OnEnter(
		'.ResetForm input',
		function()
		{
			Login('.ResetForm', P.FormToArray('.ResetForm'));
		}
	);

	P.EditPopup(
		[
			['phonenumber', 'text', '', '<?=$T_SYSTEM[36]?>'],
			['place', 'text', '', '<?=$T_SYSTEM[43]?>'],
			['password', 'password', '', '<?=$T_SYSTEM[17]?>'],
		],
		Login,
		{
			formdiv:					'LoginForm',
			gobuttontext:			T[15], 
			cancelbuttontext:	T[1],
			cancelfunc:				function()
				{
					window.history.back();
				}
		}
	);
	
	P.OnEnter(
		'.LoginForm input',
		function()
		{
			Login('.LoginForm', P.FormToArray('.LoginForm'));
		}
	);
	
	if (P.screensize == 'large')
	{
		$('.Login, .Reset').set('width',	'44%');
		$('.Login').set('$marginRight', '2em');
		$('.FormFields').set('+Flex +FlexCenter');
	}
}

$.ready(
	function()
	{
		SetupForm();
	}
);

</script>


