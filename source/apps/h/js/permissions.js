function DisplayTypes(app)
{
	var types	= apps[app];
	var keys	= Keys(types);

	keys.sort();

	var ls	= [];

	for (var i = 0, l = keys.length; i < l; i++)
	{
		var item	= types[keys[i]];
	
		ls.addtext([
			'<div class="Card whiteb">',
				'<div class=Title>' + keys[i] + '</div>',
			'</div>'
		]);
	}

	P.HTML('.Types', ls);
}

$.ready(
	function()
	{
		var buttons	= [];
		var keys		= Keys(apps);
	
		for (var i = 0, l = keys.length; i < l; i++)
		{
			var item	= keys[i];
			buttons.push([item, item, 0]);
		}
	
		P.MakeRadioButtons(
			'.TopBar',
			buttons,
			function(value)
			{
				DisplayTypes(value);
			}
		);
	}
);