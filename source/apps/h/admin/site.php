<?php

RequireGroup('admins');

global $SETTINGS;

$page->title	=	"Site Settings";
$page->Cache(0);

$cssfiles	=	V($SETTINGS, 'cssfiles');

if (is_array($cssfiles))
{
	$ls	=	[];
	
	foreach ($cssfiles as $f)
		$ls[]	=	"'" . $f . "\\n'";
		
	$cssfiles	= join(" + ", $ls);
} else
{
	$cssfiles	=	'';
}

$jsfiles	=	V($SETTINGS, 'jsfiles');

if (is_array($jsfiles))
{
	$ls	=	[];
	
	foreach ($jsfiles as $f)
		$ls[]	=	"'" . $f . "\\n'";
		
	$jsfiles	= join(" + ", $ls);
} else
{
	$cssfiles	= '';
}

?>

<h1><?=$page->title?></h1>

<h2>Maintenance</h2>

<div class=MaintenanceBar></div>

<h2>Database</h2>

<div class=DatabaseForm></div>

<h2>Minified Files</h2>

<p>
	Write each CSS or JavaScript file that you wish to include on all pages below, one filename per line. 
	
	<ul class=Spread>
		<li>The loader script will automatically add .css and .js to the filenames, so you don't need to include them here.</li>
		<li>Each file will be loaded in the order that you have written here.</li>
		<li>In order to use the onefile feature, your server needs to have yuicompressor installed. Alternatively, you can minify on a testing server and upload the onefiles to your production server.</li>
	</ul>
</p>

<div class=MinifyForm></div>

<script>

dbflags	= '<?=$SETTINGS['dbflags']?>';

function ClearCache()
{
	P.DialogAPI(
		'site/clearcache',
		{},
		function(d)
		{
			P.MessageBox(T[41]);
		},
		{
			timeout:	999
		}
	);
}

function SaveSettings(formdiv, values, resultsdiv)
{
	var dbflags	=	0;
	
	if ($('.ProductionButton').get('%toggled') == 'on')
		dbflags	+= 0x20;

	if ($('.TrackChangesButton').get('%toggled') == 'on')
		dbflags	+= 0x2;
	
	if ($('.TrackValuesButton').get('%toggled') == 'on')
		dbflags	+= 0x4;
	
	if ($('.TrackViewsButton').get('%toggled') == 'on')
		dbflags	+= 0x8;
	
	if ($('.SecurityButton').get('%toggled') == 'on')
		dbflags	+= 0x10;
	
	values.dbflags	= dbflags;
	
	P.LoadingAPI(
		resultsdiv,
		'site/save',
		values,
		function(d, resultsdiv)
		{
			resultsdiv.ht('<div class="greenb Pad50">' + T[41] + '</div>');
		},
		{
			timeout:	999
		}
	);
}

$.ready(
	function()
	{
		P.MakeButtonBar(
			'.MaintenanceBar',
			[
				['Clear Cache', ClearCache]
			]
		);
	
		P.EditPopup(
			[
				['dbtype', 'text', '<?=$SETTINGS['dbtype']?>', 'Type'],
				['dbname', 'text', '<?=$SETTINGS['dbname']?>', 'Name'],
				['dbuser', 'text', '<?=$SETTINGS['dbuser']?>', 'Username'],
				['dbpassword', 'password', '<?=$SETTINGS['dbpassword']?>', 'Password'],
				['dbhost', 'text', '<?=$SETTINGS['dbhost']?>', 'Host'],
				['dbflags', 'custom', dbflags, 'Flags', 'DBFlags']
			],
			SaveSettings,
			{
				formdiv:	'DatabaseForm',
				cancelfunc:	function() { window.history.back() }
			}
		);
		
		$('.DBFlags').ht(
			[
				'<div class="ToggleButton ProductionButton" data-toggled="' + ((dbflags & 0x20) ? 'on' : 'unset') + '">Production</div>',
				'<div class="ToggleButton TrackChangesButton" data-toggled="' + ((dbflags & 0x2) ? 'on' : 'unsert') + '">Track Changes</div>',
				'<div class="ToggleButton TrackValuesButton" data-toggled="' + ((dbflags & 0x4) ? 'on' : 'unset') + '">Track Values</div>',
				'<div class="ToggleButton TrackViewsButton" data-toggled="' + ((dbflags & 0x8) ? 'on' : 'unset') + '">Track Views</div>',
				'<div class="ToggleButton SecurityButton" data-toggled="' + ((dbflags & 0x10) ? 'on' : 'unset') + '">Security</div>'
			].join("\n")
		);
		
		P.MakeToggleButtons('.DBFlags .ToggleButton');
		P.SameMaxWidth('.DBFlags .ToggleButton');
	
		P.EditPopup(
			[
				['cssfiles', 'multitext', <?=$cssfiles?>, 'CSS Files'],
				['jsfiles', 'multitext', <?=$jsfiles?>, 'JavaScript Files'],
			],
			SaveSettings,
			{
				formdiv:	'MinifyForm',
				cancelfunc:	function() { window.history.back() }
			}
		);
		P.FlexInput();
	}
);

</script>
