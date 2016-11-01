items1	=	[];
items2	=	[];

// ====================================================================
function ListApps()
{
	P.DialogAPI(
		'translations/listapps',
		{
			langcode:	$$('.langcode1').value
		},
		function(d)
		{
			var	appnames	=	$('.appnames');
			$('option', appnames).remove();

			if (d.count)
			{
				var ls	=	[];
				var items	=	d.items.sort();

				for (var i = 0, l = items.length; i < l; i++)
				{
					ls.push('<option>' + EncodeEntities(items[i]) + '</option>');
				}

				appnames.ht(ls.join('\n'));
				$('.ListResults').fill('');
			}
		}
	);
}

// ====================================================================
function ListCollections()
{
	P.DialogAPI(
		'translations/listcollections',
		{
			appname:	$$('.appnames').value,
			langcode:	$$('.langcode1').value
		},
		function(d)
		{
			var	collections	=	$('.collections');
			$('option', collections).remove();

			if (d.count)
			{
				var ls		=	[];
				var items	=	d.items.sort();

				for (var i = 0, l = items.length; i < l; i++)
				{
					ls.push('<option>' + EncodeEntities(items[i]) + '</option>');
				}

				collections.ht(ls.join('\n'));
				$('.ListResults').fill('');
			}
		}
	);
}

// ===========================================================
function ListObjects()
{
	var langcode1		=	$$('.langcode1').value;
	var langcode2		=	$$('.langcode2').value;
	var collection	=	$$('.appnames').value + '/' + $$('.collections').value;

	if (langcode1 == langcode2)
	{
		$('.ListResults').ht('<div class=Error>Your two langcodes are the same.</div>');
		return;
	}

	P.DialogAPI(
		'translations/translations',
		{
			langcode1:	langcode1,
			langcode2:	langcode2,
			collection:	collection
		},
		function(d)
		{
			items1	=	d.items[langcode1];
			items2	=	d.items[langcode2];
			MakeTranslationsTable();
		}
	);
}

// ===========================================================
function MakeTranslationsTable()
{
	var langcode1			=	$$('.langcode1').value;
	var langcode2			=	$$('.langcode2').value;
	
	var ls		=	[
		'<table class="Styled Width100 Translations">',
			'<thead>',
				'<tr>',
					'<th>ID</th>',
					'<th>' + P.Selected('.langcode1').text + '</th>',
					'<th>' + P.Selected('.langcode2').text + '</th>',
				'</tr>',
			'</thead>',
		'<tbody class=ObjectsBody>'
	];

	for (var i	=	1; i < 9999; i++)
	{
		var text1	=	items1[i];
		var text2	=	items2[i];
	
		if (!text1)
			text1	=	'';

		if (!text2)
			text2	=	'';
		
		switch (P.screensize)
		{
			case 'small':
				var textlength	=	14;
				break;
			case 'medium':
				var textlength	=	26;
				break;
			default:
				var textlength	=	80;
		}

		text1	=	text1.length > textlength ? text1.substr(0, textlength) + '...' : text1;
		text2	=	text2.length > textlength ? text2.substr(0, textlength) + '...' : text2;

		ls	=	ls.concat([
			'<tr>',
				'<td class=TextID>' + i + '</td>',
				'<td class=Hover data-textid=' + i + ' data-lang="' + langcode1 + '">' + EncodeEntities(text1) + '</td>',
				'<td class=Hover data-textid=' + i + ' data-lang="' + langcode2 + '">' + EncodeEntities(text2) + '</td>',
			'</tr>'
		]);

		if (!text1 && !text2)
			break;
	}

	ls.addtext([
			'</tbody>',
		'</table>',
		'<div class=ButtonBar>',
			'<a class="RenameCollectionButton Color1" onclick="RenameCollectionPopup()">Rename Collection</a>',
			'<a class="DeleteCollectionButton Color2" onclick="DeleteCollectionPopup()">Delete Collection</a>',
		'</div>',
		'<div class=Cleared></div>'
	]);

	$('.ListResults').ht(ls.join("\n"));
	$('.Translations').on('click', OnTranslationClicked, 'td');
	$('.RenameCollectionButton').on('click', function() { RenameCollectionPopup(1); });
	$('.DeleteCollectionButton').on(
		'click', 
		function() 
		{ 
			var collection	=	$$('.appnames').value + '/' + $$('.collections').value;
			P.ConfirmDeletePopup(
				collection,
				function()
				{
					P.LoadingAPI(
						'.ConfirmDeleteResults',
						'translations/delete',
						{
							collection:	collection
						},
						function(d)
						{
							$('.ConfirmDeletePopup').remove();
						}
					);
				}
			);
		}
	);
}

// ===========================================================
function OnTranslationClicked(e)
{
	var target	=	P.Target(e);
	var textid	=	target.get('%textid');

	if (!textid)
		return;
		
	textid	=	parseInt(textid);

	var lang				=	target.get('%lang');
	var text				=	(lang == $$('.langcode1').value) ? items1[textid] : items2[textid];
	var appname			=	$$('.appnames').value;
	var collection	=	$$('.collections').value;
	
	$('.FullScreen').set('+dblueg').show().ht([
		'<div class="Padded FullWidth">',
			'<div class=FlexInput data-name=TranslationText data-value="' + EncodeEntities(text) + '"></div>',
			'<div class=ButtonBar>',
				'<a class="Color3" onclick="SaveTranslation(\'' + lang + '\', ' + textid + ')">Save</a>',
				'<a class="Spacer Width8"></a>',
				'<a class="Color1" onclick="$(\'.FullScreen\').hide()">Cancel</a>',
			'</div>',
			'<div class=SaveResults></div>',
		'</div>'
	].join("\n"));

	P.FlexInput();

	setTimeout(
		function()
		{
			$$('.TranslationText').select();
		},
		200
	);
}

// ===========================================================
function SaveTranslation(lang, textid)
{
	textid	=	parseInt(textid);

	var appname			=	$$('.appnames').value;
	var collection	=	$$('.collections').value;
	var newtext			=	$$('.TranslationText').value;

	if ((lang == $$('.langcode1').value))
	{
		items1[textid]	= newtext;
	} else
	{
		items2[textid]	= newtext;
	}

	var maxtextid	=	0;

	$('td').each(
		function(item)
		{
			var self					=	$(item);
			var currenttextid	=	parseInt(self.get('%textid'));

			if (currenttextid == textid
				&& self.get('%lang') == lang
			)
			{
				self.fill(newtext);
				$$(self).scrollIntoView();
			}

			if (currenttextid > maxtextid)
				maxtextid	=	currenttextid;
		}
	);

	if (textid == maxtextid)
	{
		var i = textid + 1;
		$$('.ObjectsBody').innerHTML	+= [
			'<tr>',
				'<td class=TextID>' + i + '</td>',
				'<td class=Hover data-textid=' + i + ' data-lang=' + $$('.langcode1').value + '></td>',
				'<td class=Hover data-textid=' + i + ' data-lang=' + $$('.langcode2').value + '></td>',
			'</tr>'
		].join("\n");
	}
	
	P.LoadingAPI(
		'.SaveResults',
		'translations/saveone',
		{
			collection:		appname + '/' + collection,
			textid:				textid,
			translation:	newtext,
			langcode:			lang
		},
		function()
		{
			$('.FullScreen').set('-dblueg').hide();
		}
	);
}

// ===========================================================
function RenameCollectionPopup(rename)
{
	var collection	=	rename ? $$('.appnames').value + '/' + $$('.collections').value : '';
	var popupclass	=	0;

	popupclass	=	P.EditPopup(
		[	['newvalue', 'multitext', collection, 'New name']],
		function(d)
		{
			var newvalue	=	$$('.newvalue').value;
			var parts			=	newvalue.split('/');
			
			if (parts.length == 1)
			{
				parts[0]	=	$$('.appnames').value;
				newvalue	=	parts[0] + '/' + newvalue;
			}
		
			P.LoadingAPI(
				'.EditPopupResults',
				'translations/' + (rename ? 'rename' : 'create'),
				{
					collection:	collection,
					newname:		newvalue
				},
				function(d)
				{
					P.CloseThisPopup('.EditPopupResults');
					ListCollections();
					$$('.appnames').value	=	parts[0];		
					setTimeout(ListTranslations, 300);
				}
			);
		},
		{
			parent:			rename ? $('.RenameCollectionButton') : $('.NewCollectionButton'),
			cancelfunc:	function(d) { $(d).remove(); }
		}
	);
}

// ===========================================================
function DeleteCollectionPopup()
{
	P.DialogAPI(
		'translations/delete',
		{
			collection:	$$('.appnames').value + '/' + $$('.collections').value
		},
		function(d)
		{
		}
	);
}

// ===========================================================
function DeleteCollection()
{
	P.DialogAPI(
		'translations/delete',
		{
			collection:	$$('.appnames').value + '/' + $$('.collections').value
		},
		function(d)
		{
		}
	);
}

// ===========================================================
function CheckSamelangcode(selectnum)
{
	var select1	=	$('.langcode1');
	var select2	=	$('.langcode2');
	var select1val	=	select1[0].value;
	var select2val	=	select2[0].value;

	if (select1val != select2val)
		return;

	if (selectnum == 1)
	{
		var valuetoavoid	=	select1val;
		var select				=	select2;
	} else
	{
		var valuetoavoid	=	select2val;
		var select				=	select1;
	}

	var options	=	select[0].options;
	var l				=	options.length;

	for (var i = 0; i < options.length; i++)
	{
		if (options[i].value != valuetoavoid)
		{
			select.value	=	options[i].value;
			break;
		}
	}
}

// ********************************************************************
$('.ListTranslationsButton').on('click', ListObjects);
$('.RefreshAppsButton').on('click', ListApps);
$('.RefreshCollectionsButton').on('click', ListCollections);
$('.NewCollectionButton').on('click', function() { RenameCollectionPopup(); });

$('.appnames').on('change', ListCollections);
$('.collections').on('change', ListObjects);
$('.langcode1').on('change', function() { CheckSamelangcode(1); });
$('.langcode2').on('change', function() { CheckSamelangcode(2); });
