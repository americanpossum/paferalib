<?php

global $R;

$wallpapers	=	[];

foreach (scandir('apps/main/images/wallpapers') as $f)
{
	if ($f == '.' || $f == '..')
		continue;

	if (strpos($f, '-thumb.jpg') !== FALSE)
	{
		$parts	=	explode('-', $f);
		$wallpapers[$parts[0]]	=	$parts[1];
	}
}

$themes	=	array_map(
	function($e)
	{
		return pathinfo($e, PATHINFO_FILENAME);
	},
	$R->ListDir('css/themes')
);

$page->title	=	"Wallpapers";

?>

<h1>Wallpapers</h1>

<div class=Objects></div>
<br class=Cleared>

<script>

wallpapers	=	<?=json_encode($wallpapers)?>;
themes			=	<?=json_encode($themes)?>;
T8					=	<?=json_encode($T->Load('main/upload'))?>;

// ====================================================================
function ListWallpapers()
{
	var ls	=	['<div class="Cards Cards6">'];

	for (var name in wallpapers)
	{
		var theme	=	wallpapers[name];

		ls	=	ls.concat([
			'<a class="AutoWidth whiteb" data-autowidth=20>',
				'<img class=FloatLeft src="/apps/main/images/wallpapers/' + name + '-' + theme + '-thumb.jpg">',
				'<div class=VAlign>',
					'<span class=red>' + name + '</span><br>',
					'<span class=green>' + theme + '</span>',
				'</div>',
				'<div class=CardActions>',
					'<div class="redb" onclick="DeleteWallpaper(this, \'' + name + '\')">X</div>',
					'<div class="greenb" onclick="EditTheme(this, \'' + name + '\')">e</div>',
				'</div>',
				'<div class=Cleared></div>',
			'</a>'
		].join("\n"));
	}
	ls	=	ls.concat([
		'<a class="AutoWidth whiteb" data-autowidth=20 onclick="AddWallpaper(this)">',
			'<div class="VAlign Center Size400">+</div>',
		'</a>'
	].join("\n"));

	ls.push('</div>');

	$('.Objects').ht(ls.join("\n"));

	pf.AutoWidth();
}

// ====================================================================
function EditTheme(el, name)
{
	var ls		=	[];
	var color	=	1;
	
	for (var i = 0, l = themes.length; i < l; i++)
	{
		var theme	=	themes[i];
		
		if (theme == wallpapers[name])
			continue;
			
		ls.push('<a class="Button Color' + color + '" onclick="SetTheme(this, \'' + name + '\', \'' + theme + '\')">' + theme + '</a>');
		
		color++;
		
		if (color > 6)
			color	=	1;
	}
	
	ls.push('<a class="Button Color1" onclick="pf.CloseThisPopup(this)">Cancel</a><div class=ThemeResults></div>');
	
	pf.Popup(
		HTML(ls.join('\n')),
		{
			parent:	el
		}
	);
}

// ====================================================================
function SetTheme(el, name, theme)
{
	$('.ThemeResults').ht('<img src="/apps/main/images/loading.gif">');
	
	pf.API(
		'wallpapers/settheme',
		{
			filename:	name,
			theme:		theme
		},
		function()
		{
			wallpapers[name]	=	theme;
			ListWallpapers();
			pf.CloseThisPopup(el);
		}
	);
}

// ====================================================================
function DeleteWallpaper(el, name)
{
	pf.Popup(HTML([
		'<div class="yellowb Pad100">',
			'Are you really sure that you want to delete wallpaper ' + name + '?<br><br>',
			'<div class=ButtonBar>',
				'<a class="Color3" onclick="ReallyDeleteWallpaper(this, \'' + name + '\')">Delete</a>',
				'<a class="Spacer Width400"></a>',
				'<a class="Color1" onclick="pf.CloseThisPopup(this)">Cancel</a>',
			'</div>',
			'<div class=Cleared></div>',
			'<div class=DeleteResults></div>',
		'</div>'
	].join('\n')),
		{
			parent:	el
		}
	);
}

// ====================================================================
function ReallyDeleteWallpaper(el, name)
{
	pf.CloseThisPopup(el);
	window.location	=	document.location;
}

// ====================================================================
function AddWallpaper(el)
{
	pf.Popup(HTML([
		'<div class="whiteb Pad50">',
			'<div class=UploadDiv></div>',
			'<div class=Cleared></div>',
			'<a class="Button Color1 Width93" onclick="pf.CloseThisPopup(this)">Cancel</a>',
			'<div class=Cleared></div>',
		'</div>'
	].join('\n')),
		{
			parent:	el
		}
	);

	pf.UploadButton(
		'.UploadDiv',
		'wallpaper',
		{
			onsuccess:	function()
				{
						window.location	=	document.location;
				}
		}
	);
}

// ********************************************************************
_loader.OnFinished(
	function()
	{
		ListWallpapers();
	}
);

</script>
