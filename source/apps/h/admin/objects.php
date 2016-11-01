<?php

RequireGroup('admins');

global $R, $D;

$modellist		=	['<select class=ModelSelect>'];
$linkmodellist		=	['<select class=LinkModel>'];

$modelnames		=	[];
$modelfields	=	[];

foreach ($D->types as $name => $type)
{
	if (class_exists($name))
	{
		$modelnames[]	=	$name;
		$modelfields[$name]	=	$D->GetModelFields($name);
	}
}

asort($modelnames);

foreach ($modelnames as $t)
{
	$text	=	"<option>{$t}</option>";
	$modellist[]		=	$text;
	$linkmodellist[]	=	$text;
}

$modellist[]	=	'</select>';
$linkmodellist[]	=	'</select>';

$modellist	=	join("\n", $modellist);
$linkmodellist	=	join("\n", $linkmodellist);

$page->title	=	"Objects";

?>
	<div class=ButtonBar>
		<?=$modellist?>
		<a class="InlineButton Color1" onclick="EditObject()">New</a>
		<input type=search class=SearchFilter placeholder="WHERE clause">
		<a class="InlineButton Color2" onclick="ListObjects()">Search</a>
	</div>
	<div class=Cleared></div>
	<div class=ModelFields></div>
	<br class=Cleared>
	<div class=SearchResults></div>
<script>

modelfields	=	<?=json_encode($modelfields)?>;
languages		=	<?=json_encode(DBTranslator::$LANGUAGES)?>;

_loader.OnFinished(
	function()
	{
		_loader.Load(P.JSURL('objects'));

		_loader.OnFinished(
			function()
			{
				$('.ModelSelect').on(
					'change', 
					function()
					{
						DisplayFields();
						ListObjects();
					}
				);

				DisplayFields();
			},
			100
		);
	}
);

</script>

