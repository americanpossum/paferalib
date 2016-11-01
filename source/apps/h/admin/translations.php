<?php

RequireGroup(['translators']);

$langs					=	[];

foreach (DBTranslator::$LANGUAGES as $id => $v)
{
	$langs[$v[0]]	=	$v[2][0];
}

$langs['None']	=	'none';

$langselect1		=	FormSelect('langcode1', $langs, 'en-us');
$langselect2		=	FormSelect('langcode2', $langs, 'zh-cn');

$apps	=	[];

foreach ($R->apps as $app)
{
	$selected	=	($app == 'main') ? 'selected' : '';
	$apps[]	=	<<<EOT
	<option {$selected}>{$app}</option>
EOT;
}

$apps	=	join("\n", $apps);

$collections	=	[];

foreach ($T->ListCollections('h') as $app)
{
	$selected	=	($app == 'main') ? 'selected' : '';

	$collections[]	=	<<<EOT
	<option {$selected}>{$app}</option>
EOT;
}

$collections	=	join("\n", $collections);

$page->title	=	"Translations";

?>

<div class=ButtonBar>
	<?=$langselect1?>
	<?=$langselect2?>
	<select class=appnames>
		<?=$apps?>
	</select>
	<select class=collections>
		<?=$collections?>
	</select>
</div>

<div class=ButtonBar>
		<a class="ListTranslationsButton Color1">List Translations</a>
		<a class="RefreshAppsButton Color2">Refresh Apps</a>
		<a class="RefreshCollectionsButton Color3">Refresh Collections</a>
		<a class="NewCollectionButton Color4">New Collection</a>
</div>

<br class=Cleared>

<div class=ListResults></div>

<script>

_loader.OnFinished(
	function()
	{
		_loader.Load(P.baseurl + 'j/h/translations.js');
	}
);

</script>
