
// ====================================================================
function ListUsers(orderby, start, limit)
{
	orderby	=	orderby || '';
	start		=	start || 0;
	limit		=	limit || 100;
	
	P.LoadingAPI(
		'.UserResults',
		'users/list',
		{
			start:			start,
			limit:			limit,
			orderby:		orderby,
			listgroups:	(groups == 0 ? 1 : 0),
			filter:			$$('.Filter').value
		},
		function(d, resultsdiv)
		{
			if (groups == 0)
				groups	=	d.groups;
		
			if (!d.items.length)
			{
				resultsdiv.fill(T[49]);
				return;
			}
			
			var ls	=	['<div class=Cards4>'];
			
			for (var i = 0, l = d.items.length; i < l; i++)
			{			
				var item				=	d.items[i];
				var ingroups		=	[];
				var usergroups	=	item.groups;				
				
				for (var j = 0, m = usergroups.length; j < m; j++)
				{
					var g	=	groups[usergroups[j]];
					
					if (g)
						ingroups.push(g.translated ? g.translated : g.name);
				}
				
				ls.addtext([
					'<div class="Card whiteb HoverHighlight" data-userid="' + item.id + '" onclick="EditUser(\'' + item.id + '\')">',
						'<div class="CardActions Size150" onclick="CardMenu(this)"><a class="Color3">&gt;</a></div>',
						'<img src="' + item.headshot + '" class=CardIcon>',
						'<div class=Title>' + item.username + '</div>',
						'<div class=Description>' + item.phonenumber + ' - ' + item.place + '</div>',
						'<div class=Groups>' + ingroups.join(', ') + '</div>',
					'</div>'
				]);
			}
			
			ls.push('</div>');
			ls.addtext(P.PageBar(d.count, start, limit, orderby, d.items.length));
			
			resultsdiv.ht(ls.join('\n'));
			
			P.OnResize();
			
			setTimeout(P.OnResize, 200);
		}
	);
}

// ====================================================================
function ListGroups(orderby, start, limit)
{
	orderby	=	orderby || '';
	start		=	start || 0;
	limit		=	limit || 100;
	
	P.LoadingAPI(
		'.UserResults',
		'users/listgroups',
		{
			start:		start,
			limit:		limit,
			orderby:	orderby,
			filter:		$$('.Filter').value
		},
		function(d, resultsdiv)
		{
			var ls	=	['<table class="Width100 Styled">'];
			
			groups	=	d.items;
			
			var sorted	=	P.SortArray(groups, 1, 'name');
			
			for (var i = 0, l = sorted.length; i < l; i++)
			{
				var key			= sorted[i][0];
				var item		=	groups[key];
				
				ls.addtext([
					'<tr>',
						'<td class=HoverHighlight onclick="ViewGroup(\'' + key + '\', \'' + item.name + '\')">' + item.name + '</td>',
						'<td class=Color1 onclick="EditGroup(\'' + key + '\')">' + T[56] + '</td>',
						'<td class=Color2 onclick="DeleteGroupPopup(\'' + key + '\', \'' + item.name + '\')">' + T[22] + '</td>',
					'</tr>'
				]);
			}
			
			ls.push('</table>');
			ls.addtext(P.PageBar(d.count, start, limit, orderby, sorted.length));
			
			resultsdiv.ht(ls.join('\n'));
		}
	);
}

// ====================================================================
function ViewGroup(groupid, groupname)
{
	currentgroup	=	{
		id:			groupid,
		name:		groupname
	};
	
	ListMembers();
}

// ====================================================================
function ListMembers(orderby, start, limit)
{
	orderby	=	orderby || '';
	start		=	start || 0;
	limit		=	limit || 100;
	
	P.LoadingAPI(
		'.UserResults',
		'users/list',
		{
			start:		start,
			limit:		limit,
			orderby:	orderby
		},
		function(d, resultsdiv)
		{
			if (!d.items.length)
			{
				resultsdiv.fill(T[49]);
				return;
			}
			
			var ls	=	['<div class=Members>'];
			
			for (var i = 0, l = d.items.length; i < l; i++)
			{
				var item		=	d.items[i];
				
				ls.addtext([
					'<div class=ToggleButton data-name="users" data-userid="' + item.id + '" data-toggled="' + (item.groups.indexOf(currentgroup.id) > -1 ? 'on' : 'unset') + '">',
						'<img class="Square400 FloatLeft" src="' + item.headshot + '">',
						EncodeEntities(item.phonenumber) + '<br>',
						EncodeEntities(item.place) + '<br>',
						EncodeEntities(item.username),
					'</div>'
				]);
			}
			
			ls.push('</div>');
			ls.addtext(P.PageBar(d.count, start, limit, orderby, d.items.length));
			
			P.HTML(resultsdiv, ls);
			P.MakeToggleButtons('.ToggleButton');
			P.SameWidth('.Members .ToggleButton', 14);
			P.SameMaxHeight('.Members .ToggleButton');
			
			setTimeout(
				function()
				{
					P.SameWidth('.Members .ToggleButton', 14);
				},
				300
			);
			
			P.OnClick(
				'.Members .ToggleButton',
				function(e, el)
				{
					var userid	=	el.get('%userid');
					var toggled	=	el.get('%toggled');
					
					if (userid)
					{
						P.DialogAPI(
							'users/setgroup',
							{
								userid:		userid,
								groupid:	currentgroup.id,
								ismember:	(toggled == 'unset' ? 1 : 0)
							},
							function()
							{
							}
						);
					}
				}
			);
		}
	);
}

// ====================================================================
function CardMenu(el)
{
	if (el.type)
		return;
		
	var card	=	$(el);
	
	if (!card.is('.Card'))
		card	=	card.up('.Card');
	
	if (!card.length)
		return;
		
	var userid		= card.get('%userid');
	var username	=	$('.Title', card).text();
	
	P.Popup(
		HTML([
			'<div class="Raised">',
				'<div class=ContextMenu>',
					'<a onclick="P.CloseThisPopup(this); EditUser(\'' + userid + '\')">' + T[56] + '</a>',
					'<a class=DeleteButton>' + T[22] + '</a>',
				'</div>',
			'</div>',
		].join('\n')),
		{
			'$left':					P.mousex + 'px',
			'$top':						P.mousey + 'px',
			'$width':					'12em',
			closeonmouseout:	1
		}
	);
	
	P.OnClick(
		'.DeleteButton', 
		function() 
		{
			DeleteUserPopup(userid, username);
		}
	);
	return 0;
}

// ====================================================================
function DeleteUserPopup(userid, username)
{
	P.ConfirmDeletePopup(
		username,
		function()
		{
			DeleteUser(userid);
		}
	);
}

// ====================================================================
function DeleteGroupPopup(groupid, groupname)
{
	P.ConfirmDeletePopup(
		groupname,
		function()
		{
			DeleteGroup(groupid);
		}
	);
}

// ====================================================================
function EditUser(userid)
{
	$('.FullScreen').set('+dblueg').ht('<div class="EditDiv"></div>').show();
	
	if (!userid)
	{
		EditUserForm();
		return;
	}

	P.LoadingAPI(
		'.EditDiv',
		'users/view',
		{
			userid:	userid
		},
		function(d, resultsdiv)
		{
			EditUserForm(d);
		},
		{
			timeout:	999
		}
	);	
}

// ====================================================================
function EditGroup(groupid)
{
	P.Popup(HTML('<div class="whiteb Raised Rounded Pad50 EditDiv"></div>'));
	
	if (!groupid)
	{
		EditGroupForm();
		return;
	}

	P.LoadingAPI(
		'.EditDiv',
		'users/viewgroup',
		{
			groupid:	groupid
		},
		function(d, resultsdiv)
		{
			EditGroupForm(d);
		}
	);	
}

// ====================================================================
function EditGroupForm(d)
{
	P.EditPopup(
		[
			['translated_translated',	'text', d ? d.item.translated : '', T_ADMIN[6]],
			['groupname',	'text', d ? d.item.name : '', T_ADMIN[5]],
			['groupid',	'hidden', d ? d.item.id : ''],
		],
		function(el, values)
		{
			P.LoadingAPI(
				'.EditDivResults',
				'users/savegroup',
				values,
				function(d, resultsdiv)
				{
					P.CloseThisPopup('.groupname');
					ListGroups();
				}
			);
		},
		{
			formdiv:		'EditDiv',
			cancelfunc:	function() { P.CloseThisPopup('.groupname'); }
		}
	);	
}

// ====================================================================
function EditUserForm(d)
{
	P.EditPopup(
		[
			d ? ['headshot',	'custom', '', T[58], 'Headshot'] : 0,
			['username_translated',	'text', d ? d.item.username : '', T[16]],
			['phonenumber',	'text', d ? d.item.phonenumber : '', T[36]],
			['place',	'text', d ? d.item.place : '', T[43]],
			['password',	'password', '', T[17]],
			['email',	'text', d ? d.item.email : '', T[44]],
			d ? ['groups',	'custom', '', T[20], 'Groups'] : 0,
			['userid',	'hidden', d ? d.item.id : ''],
		],
		function(el, values)
		{
			P.LoadingAPI(
				'.EditDivResults',
				'users/save',
				values,
				function(d, resultsdiv)
				{
					$('.FullScreen').hide();
					ListUsers();
				}
			);
		},
		{
			formdiv:		'EditDiv',
			cancelfunc:	function() { $('.FullScreen').hide(); }
		}
	);
	
	if (d)
	{
		P.HTML(
			'.Headshot',
			[
				'<a class="Button Color1" onclick="P.UploadFilePopup(\'a/h/upload/headshot/' + d.item.id + '\')">',
					'<img src="' + (d.item.headshot ? d.item.headshot : P.IconURL('user')) + '">',
				'</a>'
			]
		);
		
		if (groups)
		{
			var ls			=	[];
			var sorted	=	P.SortArray(groups, 1, 'name');
			
			for (var i = 0, l = sorted.length; i < l; i++)
			{
				var key				=	sorted[i][0];
				var item			=	groups[key];
				
				var ismember	=	(d && d.item.groups.indexOf(key) > -1) ? 1 : 0;
				
				ls.push('<div class=ToggleButton data-name="groups.' + key + '" data-toggled=' + (ismember ? 'on' : 'unset') + '>' + EncodeEntities(item.name) + '</div>');
			}
		
			P.HTML('.EditDiv .Groups', ls);
			P.MakeToggleButtons('.ToggleButton');
		}
	}	
}

// ====================================================================
function DeleteUser(userid)
{
	P.ClosePopup();
	
	P.DialogAPI(
		'users/delete',
		{
			userid:	userid,
		},
		function()
		{
		}
	);
	
	$('.Card').find(
		function(item)
		{
			if ($(item).get('%userid') == userid)
			{
				$(item).remove();
				return 0;
			}
		}
	);
}

// ====================================================================
function DeleteGroup(groupid)
{
	P.ClosePopup();
	
	P.DialogAPI(
		'users/deletegroup',
		{
			groupid:	groupid,
		},
		function()
		{
		}
	);
	
	$('tr').find(
		function(item)
		{
			if ($(item).get('%groupid') == userid)
			{
				$(item).remove();
				return 0;
			}
		}
	);
}

// ********************************************************************
_loader.OnFinished(
	function()
	{
		$('.CardAction').hide();
		P.AddHandler(
			'resize',
			function()
			{
				P.SameWidth('.Card', 20);
				P.SameWidth('.Members .ToggleButton', 14);
			}
		);
		ListUsers();
	}
);
