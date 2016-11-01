<?php

$page->title	=	"Maintenance";

?>

<div class=FlexInput data-name=Input></div>

<div class=ButtonBar>
	<a class="Color1" onclick="Query()">Query</a>
	<a class="Color2" onclick="Eval()">Eval</a>
	<a class="Color3" onclick="VerifyTranslations()">Verify Translations</a>
</div>

<br class=Cleared>

<div class=ObjectResults></div>

<script>

// ====================================================================
function Query()
{
	var query	=	$('textarea.Input').get('value').trim();

	var resultsdiv	=	$('.ObjectResults');

	if (!query)
	{
		resultsdiv.ht('<div class=Error>Please enter a SQL query.</div>');
		return;
	}

	P.Loading(resultsdiv);

	_db.Query(
		'results', 
		query
	).then(
		function(d)
		{
			console.log(d);
			var results	=	d.results;

			if (results && results.length)
			{
				var ls			=	['<table class="Styled Padded">', '<thead>'];
				var isfirst	=	true;

				for (var i = 0, l = results.length; i < l; i++)
				{
					var item	=	results[i];

					ls.push('<tr>');

					if (isfirst)
					{
						for (var k in item)
						{
							ls.push('<th>' + k + '</th>');
						}

						ls	=	ls.concat([
							'</tr>',
							'</thead>',
							'<tbody>',
							'<tr>'
						].join('\n'));
						isfirst	=	false;
					}

					for (var k in item)
					{
						ls.push('<td>' + item[k] + '</td>');
					}

					ls.push('</tr>');
				}

				ls.push('</tbody>');
				ls.push('</table>');

				resultsdiv.ht(ls.join("\n"));
			} else
			{
				resultsdiv.fill('No items returned.');
			}
		}
	).error(
		function(d)
		{
			console.log(d);
			resultsdiv.ht('<pre>' + d.error + '</pre>');
		}
	);
}

// ====================================================================
function Eval()
{
	var query	=	$('textarea.Input').get('value').trim();

	var resultsdiv	=	$('.ObjectResults');

	if (!query)
	{
		resultsdiv.ht('<div class=Error>Please enter some PHP code to eval.</div>');
		return;
	}

	P.Loading(resultsdiv);

	_db.Eval(
		'results',
		query
	).then(
		function(d)
		{
			if (d.results)
			{
				resultsdiv.ht('<pre>' + PrintArray(d.results) + '</pre>');
			} else
			{
				resultsdiv.fill('No items returned.');
			}
		}
	).error(
		function(d)
		{
			console.log(d);
			resultsdiv.ht('<pre>' + d.error + '</pre>');
		}
	);
}

// ====================================================================
function VerifyTranslations()
{
	P.LoadingAPI(
		'.ObjectResults',
		'translations/verify',
		{},
		function(d, resultsdiv)
		{
			resultsdiv.fill('Finished verifying');
		},
		{
			timeout:	9999
		}
	);
}

_loader.OnFinished(
	function()
	{
		P.FlexInput();
	}
);

</script>
