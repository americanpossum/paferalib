forumuri			=	'';
forumthreadid	=	0;
forumbounty		= 0;

// ====================================================================
function DisplayForum(orderby, start, limit)
{
	orderby		= orderby	|| '';
	start			=	start		|| 0;
	limit			= limit		|| 100;

	P.LoadingAPI(
		'.Forum',
		'forums/list',
		{
			orderby:	orderby,
			start:		start,
			limit:		limit,
			uri:			forumuri,
			threadid:	forumthreadid
		},
		function(d, resultsdiv)
		{
			if (!d.items.length)
			{
				resultsdiv.fill(T[49]);
				return;
			}
		
			var ls	=	[];
		
			for (var i = 0, l = d.items.length; i < l; i++)
			{
				var item				=	d.items[i];
			
				ls.addtext([
					'<div class="Card whiteb ForumPost" data-postid="' + item.id + '" data-threadid="' + item.threadid + '" data-bounty="' + item.bounty + '">',
						'<div class=Title>' + item.title + (parseInt(item.bounty) ? ' (' + item.bounty + ')' : '') + '</div>',
						'<div class=SentTime>' + DisplayTime(item.senttime) + '</div>',
						'<div class=Message>' + item.message + '</div>',
						'<div class=ButtonBar>',
							'<a class="Color1 ReplyButton">' + T_FORUM[2] + '</a>',
						'</div>',
					'</div>'
				]);
			}
			
			ls.addtext(P.PageBar(d.count, start, limit, orderby, d.items.length, 'ListImages'));
		
			P.HTML(resultsdiv, ls);
		
			P.SameWidth('.ForumPost', 20);
			P.OnClick('.ReplyButton', Reply);
		}
	);
}

// ====================================================================
function Reply(e, el)
{
	if (el)
		el	=	el.up('.ForumPost');

	var parentid	=	el ? el.get('%postid') : '';
	var threadid	=	el ? el.get('%threadid') : '';
	var title			=	el ? $('.Title', el).text() : '';

	if (title)
	{
		if (title[0] == '>')
		{
			title	=	'>' + title;
		} else
		{
			title	=	'> ' + title;
		}
	}

	P.EditPopup(
		[
			['title',	'text', title, T_LEARN[59]],
			['message',	'multitext', '', T_LEARN[60]],
			['uploadfile',	'custom', '', '', 'UploadFile'],
			(forumbounty && !el) ? ['bounty',	'float', '', T_LEARN[90]] : '',
		],
		function(el, values)
		{
			var description	=	parseFloat(values.description);
			
			if (!values.title || !values.message)
			{
				P.ErrorPopup(T[48]);
				return;
			}
			
			var fileids	=	[];
			
			$('.UploadedFile').each(
				function(item)
				{
					var fileid = $(item).get('%fileid');
					
					if (fileid)
						fileids.push(fileid);
				}
			);
			
			values.fileids	=	fileids;
			values.uri			= forumuri;
			values.parentid	= parentid;
			values.threadid	= threadid;
			
			P.LoadingAPI(
				'.EditPopupResults',
				'h/forums/save',
				values,
				function(d, resultsdiv)
				{
					resultsdiv.ht('<div class="greeng Pad50">' + T_LEARN[89] + '</div>');
				}
			);
		}
	);
	
	$('.UploadFile').ht(
		'<a class="Button UploadButton Color1" onclick="UploadFile()">' + T[29] + '</a><br>'
		+ '<table class=UploadedFilesTable></table>'
	);
}

// ====================================================================
function UploadFile()
{
	P.UploadFilePopup(
		P.APIURL('upload/file', 'h'),
		{
			accept:			'*; capture=camera',
			maxsize:		1024 * 1024 * 5,
			title:			T_LEARN[15],
			onfinished:	function(e, xhr, filename)
				{
					var d	=	JSON.parse(xhr.response);
					uploadedfiles[d.id]	=	filename;
					DisplayUploadedFiles();
					P.CloseThisPopup('.uploadtitle');
				}
		}
	);
}

// ====================================================================
function DisplayUploadedFiles()
{
	var ls	=	[];
	
	var sorted	=	P.SortArray(uploadedfiles);
	
	for (var i = 0, l = sorted.length; i < l; i++)
	{
		var key		=	sorted[i][0];
		var item	=	uploadedfiles[key];
		
		ls.addtext([
			'<tr>',
				'<td class=UploadedFile data-fileid="' + key + '">' + item + '</td>',
				'<td class="Color1" onclick="DeleteUpload(\'' + key + '\', this)">' + T[22] + '</td>',
			'</tr>'
		]);
	}
	
	P.HTML('.UploadedFilesTable', ls);
}

// ====================================================================
function DeleteUpload(fileid, el)
{
	P.DeleteUpload(
		fileid,
		function(d)
		{
			$(el).up('tr').remove();
		}
	);
}

