<?php

$page->title	=	"Sessions";

?>

<div class=ListResults></div>

<script>

var	currentorderby	=	'start DESC';

// ====================================================================
function ListObjects(orderby, start)
{
	var orderby	=	orderby || 'start DESC';
	var start		=	start || 0;
	var limit		=	100;

	P.LoadingAPI(
		'.ListResults',
		'sessions/list',
		{
			orderby:	orderby,
			start:		start,
			limit:		limit
		},
		function(d, resultsdiv)
		{
			if (d.count)
			{
				var ls		=	['<table class="Styled Width100 Sessions">'
					+	'<thead>'
					+ '<tr>'
				];

				ls.push('<th class=Button onclick="ListObjects(\''
					+ ((orderby == 'start DESC') ? 'start ASC' : 'start DESC')
					+ '\')">Started At</th>');

				ls.push('<th class=Button onclick="ListObjects(\''
					+ ((orderby == 'lastvisited DESC') ? 'lastvisited ASC' : 'lastvisited DESC')
					+ '\')">Last Visited</th>');

				ls.push('<th class=Button onclick="ListObjects(\''
					+ ((orderby == 'length DESC') ? 'length ASC' : 'length DESC')
					+ '\')">Length</th>');

				ls.push('<th class=Button onclick="ListObjects(\''
					+ ((orderby == 'username DESC') ? 'username ASC' : 'username DESC')
					+ '\')">Username</th>');

				ls.push('<th class=Button onclick="ListObjects(\''
					+ ((orderby == 'useragent DESC') ? 'useragent ASC' : 'useragent DESC')
					+ '\')">Useragent</th>');

				ls.push('</tr>'
					+	'</thead>'
					+	'<tbody>'
				);
				
				for (var i = 0, l = d.items.length; i < l; i++)
				{
					var item	=	d.items[i];
					
					ls.push('<tr><td>' + PrintTimestamp(item.start)
						+ '</td><td>' + PrintTimestamp(item.lastvisited)
						+ '</td><td>' + SecondsToTime(item.length)
						+ '</td><td>' + item.username
						+ '</td><td>' + item.useragent
						+ '</td></tr>'
					);
				}

				ls.push('</tbody>'
					+ '</table>'
				);

				ls.addtext(P.PageBar(d.count, start, 100, orderby, d.items.length));

				resultsdiv.ht(ls.join("\n"));
			}
		}
	);
}

_loader.OnFinished(
	function()
	{
		ListObjects();
	}
);

</script>

<?php
