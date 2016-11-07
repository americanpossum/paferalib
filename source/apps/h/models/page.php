<?php

class templateclass extends ModelBase
{
	public static	$DESC	=	[
		'numsuids'					=>	1,
		'flags'							=>	DB::SECURE | DB::TRACK_CHANGES,
		'fields'			=>	[
			'url'						=>	['SINGLETEXT NOT NULL'],
			'title'					=>	['SINGLETEXT NOT NULL'],
			'stylesheets'		=>	['JSON NOT NULL'],
			'styles'				=>	['MULTITEXT NOT NULL'],
			'scripts'				=>	['JSON NOT NULL'],
			'script'				=>	['MULTITEXT NOT NULL'],
			'header'				=>	['MULTITEXT NOT NULL'],
			'navbar'				=>	['MULTITEXT NOT NULL'],
			'content'				=>	['MULTITEXT NOT NULL'],
			'footer'				=>	['MULTITEXT NOT NULL'],
			'flags'					=>	['INT NOT NULL'],
		],
	];

	const	DONT_CACHE	=	0x01;
	const NO_HEADER		= 0x02;
	const NO_NAVBAR		= 0x04;
	const NO_FOOTER		= 0x08;

	// ------------------------------------------------------------------
	function __construct()
	{
		$this->id						=	null;
		$this->url					=	'';
		$this->title				=	'';
		$this->stylesheets	=	[];
		$this->styles				=	[];
		$this->scripts			=	[];
		$this->script				=	[];
		$this->header				=	[];
		$this->navbar				=	[];
		$this->content			=	[];
		$this->footer				=	[];
		$this->flags				=	0;

		$this->errors				=	[];
	}

	// ------------------------------------------------------------------
	function Cache($v)
	{
		$this->flags	=	$v 
			? $this->flags & (~self::DONT_CACHE)
			: $this->flags | self::DONT_CACHE;
	}
	
	// ------------------------------------------------------------------
	function Header($v)
	{
		$this->flags	=	$v 
			? $this->flags & (~self::NO_HEADER)
			: $this->flags | self::NO_HEADER;
	}
	
	// ------------------------------------------------------------------
	function NavBar($v)
	{
		$this->flags	=	$v 
			? $this->flags & (~self::NO_NAVBAR)
			: $this->flags | self::NO_NAVBAR;
	}
	
	// ------------------------------------------------------------------
	function Footer($v)
	{
		$this->flags	=	$v 
			? $this->flags & (~self::NO_FOOTER)
			: $this->flags | self::NO_FOOTER;
	}
	
	// ------------------------------------------------------------------
	function Render()
	{
		$buffer	=	$this->RenderTemplate();

		if (isset($this->replacements))
			$buffer	=	str_replace($this->replacements[0], $this->replacements[1], $buffer);

		return $buffer;
	}

	// ------------------------------------------------------------------
	function RenderTemplate()
	{
		global	$D, $T_SYSTEM, $T_MAIN, $R, $S, $SETTINGS, $T;
		
		$T_POSSUMBOT	=	$T->Load('h/possumbot');

		ob_start();		

		if ($D->IsProduction())
		{
			$this->stylesheets	=	['/c/h/all.css'];
			$this->scripts			=	['/j/h/all.js'];
		} else
		{
			$ls	=	[];
		
			if (is_array(V($SETTINGS, 'cssfiles')))
			{
				foreach ($SETTINGS['cssfiles'] as $f)
					$ls[]	= '/c/h/' . $f . '.css';
			}
		
			$this->stylesheets = array_merge($ls, $this->stylesheets);
			
			$ls	=	[];
		
			if (is_array(V($SETTINGS, 'jsfiles')))
			{
				foreach ($SETTINGS['jsfiles'] as $f)
					$ls[]	= '/j/h/' . $f . '.js';
			}
		
			$this->scripts = array_merge($ls, $this->scripts);
		}

		if ($R->fullurl)
		{
			$ls	=	[];

			foreach ($this->stylesheets as $s)
			{
				if (is_array($s))
				{
					foreach ($s as $t)
						$ls[]	=	$R->baseurl . $t;
				} else
				{
					$ls[]	=	'<link rel="stylesheet" type="text/css" href="' . $R->baseurl . $s . '" />';
				}
			}

			$this->stylesheets	=	$ls;

			$ls	=	[];

			foreach ($this->scripts as $s)
			{
				if (is_array($s))
				{
					foreach ($s as $t)
						$ls[]	=	'<script src="' . $R->baseurl . $t . '"></script>';
				} else
				{
					$ls[]	=	'<script src="' . $R->baseurl . $s . '"></script>';
				}
			}

			$this->scripts	=	$ls;
		}

		if ($this->errors)
		{
			echo '<div class=Errors><ul>';

			foreach ($this->errors as $e)
			{
				echo '<li>' . $e . '</li>';
			}
			echo '</div>';
		}

$fullurl	=	$R->fullurl ? $R->baseurl : '/';

?>
	<script>

if (!document.addEventListener)
{
	window.location	=	'<?=$fullurl?>static/outdated.<?=$_SESSION['langcode']?>.html';
} else
{
	T						=	<?=$T_SYSTEM->ToJSON()?>;
	T_POSSUMBOT	=	<?=$T_POSSUMBOT->ToJSON()?>;

	_loader.OnFinished(
		function()
		{
			P.wallpaper		=	'<?=$_SESSION['wallpaper']?>';
			P.texttheme		=	'<?=$_SESSION['texttheme']?>';
			P.possumbot		=	'<?=V($_SESSION, 'possumbot')?>';
			P.userid			=	'<?=$_SESSION['userid'] ? ToShortCode($_SESSION['userid']) : ''?>';
			P.groups			=	<?=json_encode($_SESSION['groups'])?>;
			P.lang				=	'<?=$_SESSION['lang']?>';
			P.langcode		=	'<?=$_SESSION['langcode']?>';
			P.firstview		=	'<?=$S->firstview?>';
			P.baseurl			=	'<?=$fullurl?>';
			P.currentapp	=	'<?=$R->currentapp?>';
			P.production	=	<?=$D->IsProduction()?>;
		}
	);
}

<?=join("\n", $this->script)?>

	</script>
<?php
		array_unshift($this->content, ob_get_clean());
		
		if (V($_REQUEST, 'contentonly'))
		{
			return json_encode([
				'title'		=>	$this->title,
				'content'	=>	join("\n", $this->content),
			]);
		}

		ob_start();

		$siteicon	=	$R->Icon('Pafera', 'h');
		
		if (!($this->flags & self::NO_HEADER))
		{
			$this->header[]	=	<<<EOT
			<a class=HeaderLeft href="/">
				{$siteicon}
				<div class=PageTitle>{$T_SYSTEM[2]}</div>
			</a>
EOT;

			$languages	=	[];
			
			// Check how many translated languages are available
			foreach (DBTranslator::$LANGUAGES as $num => $v)
			{
				if ($num != $D->language)
				{
					foreach ($v[2] as $code)
					{
						if (is_file('apps/h/translations/' . $code . '/main.json'))
						{
							$languages[]	=	[$code, $v[0]];
							break;
						}
					}
				}
			}

			$languages	=	json_encode($languages);

			$wallpapers	=	[];

			foreach (scandir('apps/h/images/wallpapers') as $f)
			{
				if ($f == '.' || $f == '..')
					continue;

				if (strpos($f, '-thumb.jpg') !== false)
					$wallpapers[]	=	$f;
			}

			$wallpapers	=	json_encode($wallpapers);
			
			$langicon			=	$R->Icon('Language', 'h');
			$monitoricon	=	$R->Icon('Monitor', 'h');
	
			$this->header[]	=	<<<EOT
			<div class="HeaderRight">
				[[[h/usermenu|0]]]
				[[[h/adminmenu|0]]]
				<div class=LanguagesMenu>
					{$langicon}
				</div>
				<div class=WallpapersMenu>
					{$monitoricon}
				</div>
			</div>
			<script>
	availablelanguages	=	{$languages};
	availablewallpapers	=	{$wallpapers};
			</script>
EOT;
	}

	if (!($this->flags & self::NO_FOOTER))
	{
		$this->footer[]	.=	<<<EOT
		<div class=Cleared></div>
		<div class=Centered>
		{$T_MAIN[22]}
		</div>
EOT;
	}

	if (!($this->flags & self::NO_NAVBAR))
	{
		$this->navbar[]	=	<<<EOT
	<a href="{$R->baseurl}about">{$T_MAIN[1]}</a>
	<a href="{$R->baseurl}learn/index">{$T_MAIN[3]}</a>
	<a href="{$R->baseurl}buy/index">{$T_MAIN[4]}</a>
	<a href="https://shop58113505.taobao.com/" data-nointercept=1>{$T_MAIN[11]}</a>
	<a href="{$R->baseurl}projects/index">Projects</a>
	<a href="{$R->baseurl}sanya/index">An American's Guide to Sanya</a>
EOT;
	}

		$this->styles		=	join("\n", $this->styles);
		$this->script		=	join("\n", $this->script);
		$this->header		=	join("\n", $this->header);
		$this->navbar		=	join("\n", $this->navbar);
		$this->content	=	join("\n", $this->content);
		$this->footer		=	join("\n", $this->footer);
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=$this->title?></title>
<?php

foreach ($this->stylesheets as $f)
{
	echo '<link rel="stylesheet" type="text/css" href="' . $f . '" />' . "\n";
}

foreach ($this->scripts as $f)
{
	echo '<script src="' . $f . '"></script>' . "\n";
}

?>
	<link id="ScreenSizeStylesheet" rel="stylesheet" type="text/css" href="" />
	<link id="TextThemeStylesheet" rel="stylesheet" type="text/css" href="/c/h/themes/light.css" />
  <style type="text/css">
    <?=$this->styles?>
  </style>
</head>
<body>
	<header>
		<?=$this->header?>
		<div class=Cleared></div>
	</header>
	<nav>
		<?=$this->navbar?>
		<div class=Cleared></div>
	</nav>
	<div id="ContentContainer">
		<div id="Content">
			<?=$this->content?>
		</div>
		<br>
	</div>
	<div class="Drawer Raised LeftDrawer Hidden"></div>
	<div class="Drawer Raised TopDrawer Hidden"></div>
	<div class="Drawer Raised RightDrawer Hidden"></div>
	<div class="Drawer Raised BottomDrawer Hidden"></div>
	<div class="FullScreen Hidden"></div>
	<div class="FullScreen1 Hidden"></div>
	<div class="FullScreen2 Hidden"></div>
	<div class="FullScreen3 Hidden"></div>
	<div class=Cleared></div>
	<pre class=DebugMessages></pre>
	<footer id="Footer">
		<?=$this->footer?>
	</footer>
</body>
</html>
<?php
		return ob_get_clean();
	}

	// ------------------------------------------------------------------
	public function RunPlugins($text)
	{
		global	$D, $R, $T;

		// Look for any text for output plugin replacements
		$matches	=	[];
		preg_match_all('/\[\[\[(.*)\]\]\]/', $text, $matches);

		$functions	=	[];

		foreach ($matches[1] as $m)
		{
			$parts	=	explode('|', $m);

			if (!isset($functions[$parts[0]]))
				$functions[$parts[0]]	=	[];

			$functions[$parts[0]][join('|', array_slice($parts, 1))]	=	'';
		}

		if ($functions)
		{
			$matches			=	[];
			$replacements	=	[];

			foreach ($functions as $func => $args)
			{
				try
				{
					$error	=	'';
					
					$parts	=	explode('/', $func);

					include_once('apps/' . $parts[0] . '/plugins/' . $parts[1] . '.php');
					
					if ($error)
					{
						$text	.=	$error . "\n";
						continue;
					}

					foreach	($args as $k => $v)
					{
						$matches[]			=	'[[[' . $func . '|' . $k . ']]]';
						$replacements[]	=	$v;
					}

				} catch (Exception $e)
				{
					$text	.= 'Problem calling plugin: ' . $e->getMessage() . "\n";
				}
			}

			if ($matches)
				$text	=	str_replace($matches, $replacements, $text);
		}
		return $text;
	}
}

