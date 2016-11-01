
var linkstoadd		=	[];
var existinglinks	=	[];
var deletedlinks	=	[];

var sortfield			=	'';
var sortorder			=	'ASC';
var currentobj		=	null;

// ====================================================================
function DisplayFields()
{
	var model	=	$$('.ModelSelect').value;
	var ls		=	[];

	sortfield		=	'';
	sortorder		=	'ASC';

	for (var k in modelfields[model].fields)
	{
		if (!sortfield)
			sortfield	=	k;

		ls.push('<div class=ToggleButton data-toggled=on>' + k + '</div>');
	}

	$('.ModelFields').ht(ls.join('\n'));
	P.MakeToggleButtons('.ModelFields .ToggleButton');
}

// ====================================================================
function SortBy(fieldname)
{
	if (fieldname == sortfield)
	{
		sortorder	=	(sortorder == 'ASC') ? 'DESC' : 'ASC';
	} else
	{
		sortfield	=	fieldname;
	}

	ListObjects();
}

// ====================================================================
function ListObjects(orderby, start, limit)
{
	currentobj	=	null;
	
	orderby	=	orderby || '';
	start		=	start || 0;
	limit		=	limit || 100;

	if (!orderby && sortfield)
		orderby	=	sortfield + ' ' + sortorder;

	var model	=	$$('.ModelSelect').value;
	var activefields	=	P.ToggledButtons('.ModelFields .ToggleButton').on;
	var	resultsdiv	=	$('.SearchResults');
	P.Loading(resultsdiv);

	_db.Find(
		'items',
		model,
		$$('.SearchFilter').value,
		{
			start:		start,
			limit:		limit,
			orderby:	orderby,
			fields:		activefields.join(', ')
		}
	).then(
		function(d)
		{
			var count	=	d.itemscount;
			var items	=	d.items;

			var ls	=	[];

			if (items.length)
			{
				ls.push('<table class="TypeList Styled FullWidth"><thead><tr>');

				for (var i = 0; i < activefields.length; i++)
				{
					var sortsymbol	=	'';

					if (activefields[i] == sortfield)
					{
						sortsymbol	=	(sortorder == 'ASC')
							? '<img src="' + P.baseurl + '/a/main/i/icons/up.png" class=LineHeight>'
							: '<img src="' + P.baseurl + '/a/main/i/icons/down.png" class=LineHeight>';
					}

					ls.push('<th class=Button onclick="SortBy(\'' + activefields[i] + '\')">' + activefields[i] + sortsymbol + '</th>');
				}

				ls.push('<th></th><th></th><th></th></tr></thead><tbody>');

				for (var i = 0; i < items.length; i++)
				{
					var	item	=	items[i];
					ls.push('<tr>');

					for (var j = 0; j < activefields.length; j++)
					{
						ls.push('<td>' + item[activefields[j]] + '</td>');
					}
					ls.addtext([
						'<td onclick="EditObject(' + EncodeEntities($.toJSON(GetIDs(model, item))) + ')" class=Color1>Edit</td>',
						'<td onclick="P.EditPermissionPopup(\'' + model + '\', ' + EncodeEntities($.toJSON(GetIDs(model, item))) + ')" class=Color2>Permission</td>',
						'<td onclick="DeleteObject(' + EncodeEntities($.toJSON(GetIDs(model, item))) + ')" class=Color3>Delete</td></tr>'
					]);
				}

				ls.push('</tbody></table>');
				ls	=	ls.concat(P.PageBar(count, start, limit, orderby, items.length));
			} else
			{
				ls.push('<p>No items found.</p>');
			}

			resultsdiv.ht(ls.join('\n'));
		}
	).error(
		function(d)
		{
			resultsdiv.ht('<div class=Error>' + d.error + '</div>');
		}
	);
}

// ====================================================================
function EditObjectForm(obj, fields, resultsdiv, hasid)
{
	var ls	=	['<form class=ObjForm>'];
	var editfield	=	'';
	deletedlinks	=	[];
	existinglinks	=	[];
	linkstoadd		=	[];

	if (P.screensize != 'small')
		ls.push('<table class="EditObjForm">');

	for (var k in fields)
	{
		if (fields[k] == 'translation')
			continue;

		var	v	=	obj ? obj[k] : '';

		if (!v)
			v	=	'';

		switch (fields[k])
		{
			case 'multitext':
				editfield	=	'<div class=FlexInput data-name="' + k + '" data-value="' + EncodeEntities(v.toString()) + '"></div>';
				break;
			case 'int':
			case 'number':
				if (!v)
					v	=	0;
		
				editfield	=	'<input type=number name="' + k + '" class="' + k + '" value="' + v + '">';
				break;
			case 'float':
				if (!v)
					v	=	0;
		
				editfield	=	'<input type=number step=any name="' + k + '" class="' + k + '" value="' + v + '">';
				break;
			case 'datetime-local':
				if (!v)
					v	=	new Date().toISOString().substr(0, 19);
				
				editfield	=	'<input type=datetime-local name="' + k + '" class="' + k + '" value="' + GMTToLocal(v) + '">';
				break;
			default:
				editfield	=	'<input type=' + fields[k] + ' name="' + k + '" class="' + k + '" value="' + EncodeEntities(v.toString()) + '">';
		};

		if (P.screensize == 'small')
		{
			ls	=	ls.concat([
				'<div>',
					'<label for="' + k + '">' + k + '</label>',
					editfield,
					'<div class=Cleared></div>',
				'</div>'
			]);
		} else
		{
			ls	=	ls.concat([
				'<tr>',
					'<td>' + k + '</td>',
					'<td>' + editfield + '</td>',
				'</tr>'
			]);
		}
	}

	if (P.screensize != 'small')
		ls.push('</table>');

	ls	=	ls.concat([
		'</form>',
		'<div class=ButtonBar>',
			'<a class="Color3" onclick="SaveObject()">Save</a>',
			'<a class="Color2" onclick="RefreshObject()">Refresh</a>',
			'<a class="Color1" onclick="ListObjects()">Cancel</a>',
		'</div>',
		'<div class=Cleared></div>',
		'<div class=SaveResults></div>',
	]);

	if (hasid)
	{
		if (!IsEmpty(obj.LinkedAll))
		{
			ls.push('<h2>Links</h2>');
		
			for (var model in obj.LinkedAll)
			{
				ls	=	ls.concat([
					'<h3>' + model + '</h3>',
					'<table class=Styled>',
						'<thead>',
							'<tr>'
				].join('\n'));

				var objlist	=	obj.LinkedAll[model];
				var ids			=	modelfields[model].ids;

				for (var fieldname in ids)
				{
					ls.push('<th>' + fieldname + '</th>');
				}

				ls	=	ls.concat([
						'<th>Link Type</th><th></th>',
					'</tr>'
				].join('\n'));

				for (var i = 0, l = objlist.length; i < l; i++)
				{
					var item		=	objlist[i];
					var idvals	=	{
						dbmodel: 		model,
						dblinktype:	item.dblinktype
					};

					ls.push('<tr>');

					for (var fieldname in ids)
					{
						ls.push('<td>' + item[fieldname] + '</td>');
						idvals[fieldname]	=	item[fieldname];
					}

					ls	=	ls.concat([
							'<td>' + item.dblinktype + '</td>',
							'<td class="Color1 DeleteLink" data-model="' + EncodeEntities(model) + '" data-obj="' + EncodeEntities($.toJSON(idvals)) + '">Delete</td>',
						'</tr>'
					].join('\n'));
				}

				ls.push('</table>');
			}

		}

		ls.addtext([
			'<div class=Cleared></div>',
			'<div class=ButtonBar>',
				'<select class=LinkModel>'
		]);

		for (var model in modelfields)
			ls.push('<option>' + model + '</option>');

		ls.addtext([
				'</select>',
				'<a class="InlineButton AddLinkButton Color1">Add Link</a>',
			'</div>',
			'<div class=Cleared></div>',
			'<h2>Translations</h2>',
			'<table class=Styled>',
				'<thead>',
					'<tr>',
						'<th></th>'
		]);

		for (var k in languages)
			ls.push('<th>' + k + '</th>');

		ls.push('</tr></thead><tbody>');

		for (var k in fields)
		{
			if (fields[k] == 'translation')
			{
				ls.push('<tr><th>' + k + '</th>');

				for (var lang in languages)
				{
					var langcode	=	languages[lang];
					var varname		=	k + '_' + langcode;
					ls.push('<td class=Translation data-varname="' + varname + '">' + (obj[varname] ? obj[varname] : '') + '</td>');
				}

				ls.push('</tr>');
			}
		}

		ls.push('</tbody></table>');
	}

	resultsdiv.ht(ls.join('\n'));

	P.FlexInput();

	$('.DeleteLink').on(
		'click',
		function()
		{
			var self	=	$(this);
			var obj		=	$.parseJSON(self.get('%obj'));
			var model	=	$$('.ModelSelect').value;
		
			_db.Unlink(model, GetIDs(model, currentobj), obj.dbmodel, obj);
		
			P.DeleteParent('tr', self);
		}
	);
	
	$('.Translation').on(
		'click',
		function()
		{
			var el	=	$(this);

			P.EditThis(
				el,
				function(el, newtext)
				{
					var varname	=	el.get('%varname');
					currentobj[varname]	=	newtext;

					var translations	=	{};
					translations[varname]	=	newtext;
					_db.SaveTranslation('result', $$('.ModelSelect').value, currentobj, translations);
				}
			);
		}
	);
	$('.AddLinkButton').on('click', AddLinks);
}

// ====================================================================
function GetLinkValues(td)
{
	var values	=	{};

	if (td)
	{
		var tds				=	$('td', $(td).up(), true);

		for (var i = 0; i < activefields.length; i++)
		{
			var el	=	$(tds[i]);
			values[el.get('%field')]	=	el.text();
		}
	}

	return values;
}

// ====================================================================
function GetIDs(model, values)
{
	var ids			=	{};

	for (var idname in modelfields[model].ids)
	{
		ids[idname]	=	values[idname];
	}

	return ids;
}

// ====================================================================
function EditObject(ids)
{
	var model				=	$$('.ModelSelect').value;
	var resultsdiv	=	$('.SearchResults');
	
	P.Loading(resultsdiv);

	if (!IsEmpty(ids))
	{
		_db.Load(
			'obj',
			model,
			ids,
			0,
			['LinkedAll', 'Tags']
		).then(
			function(d)
			{
				currentobj	=	d.obj;
				EditObjectForm(currentobj, modelfields[model].fields, resultsdiv, true);
			}
		).error(
			function(d)
			{
				resultsdiv.ht('<div class=Error>' + d.error + '</div>');
			}
		);
	} else
	{
		currentobj	=	null;
		EditObjectForm({}, modelfields[model].fields, resultsdiv, false);
	}
}

// ====================================================================
function RefreshObject()
{
	EditObjectForm(
		currentobj, 
		modelfields[$$('.ModelSelect').value].fields, 
		$('.SearchResults'), 
		true
	);
}

// ====================================================================
function SaveObject()
{
	var resultsdiv	=	$('.SaveResults');
	P.Loading(resultsdiv);

	_db.Save(
		'unused',
		$$('.ModelSelect').value,
		P.FormToArray('.ObjForm')
	).then(
		function(d)
		{
			resultsdiv.ht('<div class="GreenBackground Padded">Your changes have been saved.</div>');
			setTimeout(
				ListObjects,
				2000
			);
		}
	).error(
		function(d)
		{
			resultsdiv.ht('<div class=Error>Problem saving: ' + d.error + '.</div>');
		}
	);
}

// ====================================================================
function DeleteObject(ids)
{
	var popupid	=	P.Popup(HTML([
			'<div class="DeleteDialog yellowg Pad50">',
				'<p>Really delete this object?</p>',
				'<div class=ButtonBar>',
					'<a class="ConfirmButton Color3">Delete</a>',
					'<a class="CancelButton Color1" onclick="P.CloseThisPopup(this)">Don\'t Delete</a>',
				'</div>',
			'</div>'
		].join("\n"))
	);

	$('.DeleteDialog .ConfirmButton').on(
		'click',
		function()
		{
			ReallyDeleteObject(ids);
		}
	);
}

// ====================================================================
function ReallyDeleteObject(ids, popupid)
{
	var model				=	$$('.ModelSelect').value;
	var resultsdiv	=	$('.SearchResults');
	
	P.Loading(resultsdiv);

	_db.Delete(
		model,
		ids,
		0
	).then(
		function(d)
		{
			resultsdiv.ht('<div class="GreenBackground Padded">Object deleted.</div>');

			setTimeout(
				function()
				{
					P.CloseThisPopup('.DeleteDialog');
					ListObjects();
				},
				1000
			);
		}
	).error(
		function(d)
		{
			resultsdiv.ht('<div class=Error>' + d.error + '</div>');
		}
	);
}

// ====================================================================
function AddLinks()
{
	if (deletedlinks.length)
	{
		RefreshObject();
		setTimeout(AddLinks, 2000);
		return;
	}

	$('.FullScreen').set('+whiteb').ht([
		'<p>',
			'<input type=number class=LinkType placeholder="Link type">',
			'<input type=search class=LinkSearchFilter placeholder="Unique ID contains">',
			'<span class="Button Color2" onclick="ListAvailableLinks()">Search</span>',
		'</p>',
		'<div class=AddLinksList></div>',
		'<div class=ButtonBar>',
			'<a class="ConfirmButton Color3" onclick="LinkObjects()">Link Objects</a>',
			'<a class="Spacer Width8"></a>',
			'<a class="CancelButton Color1" onclick="$(\'.FullScreen\').hide();">Cancel</a>',
		'</div>'
	].join('\n')).show();
	P.Loading('.AddLinksList');
	
	ListAvailableLinks();
}

// ====================================================================
function ListAvailableLinks(orderby, start, limit)
{
	var linkmodel		=	$$('.LinkModel').value;
	var linkfields	=	Keys(modelfields[linkmodel].ids);

	existinglinks	=	[];
	linkstoadd		=	[];

	var linked	=	currentobj.LinkedAll[linkmodel];

	if (!IsEmpty(linked))
	{
		for (var i = 0, l = linked.length; i < l; i++)
		{
			existinglinks.push(JSON.stringify(GetIDs(linkmodel, linked[i])));
		}
	}

	orderby	=	orderby || linkfields.join(', ');
	start		=	start || 0;
	limit		=	limit || 20;
	cond		=	$('.LinkSearchFilter').get('value');

	_db.Find(
		'links',
		linkmodel,
		cond,
		{
			start:		start,
			limit:		limit,
			orderby:	orderby
		}
	).then(
		function(d)
		{
			var count		=	d.linkscount;
			var	items		=	d.links;

			if (!items.length)
			{
				$('.AddLinksList').ht('No available links found.');
				return;
			}

			var ls	=	['<table class="Width100 Center">'];

			ls.push('<tr>');
		
			for (var j = 0; j < linkfields.length; j++)
			{
				ls.push('<th>' + linkfields[j] + '</th>');
			}

			ls.push('</tr>');

			for (var i = 0; i < items.length; i++)
			{
				var	item		=	items[i];

				var linkid	=	JSON.stringify(GetIDs(linkmodel, item));

				if (existinglinks.indexOf(linkid) == -1)
				{
					ls.push('<tr class=ToggleButton data-toggled=unset data-linkid="' + EncodeEntities(linkid) + '">');

					for (var j = 0; j < linkfields.length; j++)
					{
						ls.push('<td>' + item[linkfields[j]] + '</td>');
					}

					ls.push('</tr>');
				}
			}

			ls.push('</table><div class=PurpleGradient>');
			ls.addtext(P.PageBar(count, start, limit, '', items.length, 'ListAvailableLinks'));
			ls.push('</div>');

			$('.AddLinksList').ht(ls.join("\n"));

			P.MakeToggleButtons('.AddLinksList .ToggleButton');

			$('.AddLinksList .ToggleButton').on(
				'click',
				function(e)
				{
					var self		=	$(this);
					var linkid	=	self.get('%linkid');

					if (self.get('%toggled') == 'on')
					{
						linkstoadd.push(linkid);
					} else
					{
						for (var i = 0; i < linkstoadd.length; i++)
						{
							if (linkstoadd[i] == linkid)
							{
								linkstoadd.splice(i, 1);
								break;
							}
						}
					}
				}
			);
		}
	).error(
		function(d)
		{
			$('.AddLinksList').ht('<div class=Error>' + d.error + '</div>');
		}
	);
}

// ====================================================================
function LinkObjects()
{
	var newlinks	=	[];

	for (var i = 0; i < existinglinks.length; i++)
		newlinks.push(JSON.parse(existinglinks[i]));

	for (var i = 0; i < linkstoadd.length; i++)
		newlinks.push(JSON.parse(linkstoadd[i]));

	var model			=	$$('.ModelSelect').value;
	var linkmodel	=	$$('.LinkModel').value;

	var linktype	=	parseInt($$('.LinkType').value);

	if (!linktype || isNaN(linktype))
		linktype	=	0;

	$('.FullScreenContent').ht(
		'<div class=whiteb>',
			'<p>Now saving your links...</p>',
			'<p class=LinkResults></p>',
		'</div>'
	);
	$('.FullScreen').show();
	P.Loading('.LinkResults');

	_db.LinkArray(
		model,
		GetIDs(model, currentobj),
		linkmodel,
		newlinks,
		linktype,
		0,
		0
	).then(
		function(d)
		{
			$('.LinkResults').fill('Links saved.');

			setTimeout(
				function()
				{
					$('.FullScreen').hide();
				},
				1000
			);
		}
	).error(
		function(d)
		{
			$('.LinkResults').ht('<div class=Error>' + d.error + '</div>');
		}
	);
}
