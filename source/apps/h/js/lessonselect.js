
// ********************************************************************
function LessonSelect()
{
	this.lang						=	1;
	this.multiple				=	0;
	this.lessonids			=	[];
	this.finishedfunc		=	'';
};

// --------------------------------------------------------------------
LessonSelect.prototype.Choose	=	function(multiple, finishedfunc)
{
	this.multiple			=	multiple;
	this.finishedfunc	=	finishedfunc;

	var fullscreencontent	=	$('.FullScreenContent');
	
	fullscreencontent.ht('<img src="/images/loading.gif">');
	$('.FullScreen').set('+DarkBlueGradient').set('$width', pc.ViewPortRect().width).show();
	window.scroll(0, 0);
	
	db.Find(
		'courses',
		'Course', 
		'', 
		{
			orderby:	'title'
		},
		function(d)
		{
			var count		=	d.coursescount;
			var items		=	d.courses;
			
			var ls	=	[];
			
			for (var i = 0; i < items.length; i++)
			{
				var item	=	items[i];
				ls.push([
					'<div class="Card HoverHighlight AutoWidth" onclick="lessonselect.Lessons(' + item.id + ')" data-optimum-width=20>',
						'<span class=Title>' + item.title + '</span>',
					'</div>'
				].join("\n"));
			}
			
			ls	=	ls.concat([
				'<br class=Cleared>',
				'<div>',
					'<a class=Button onclick="$(\'.FullScreen\').hide(\'slow\')">' + T[36] + '</a>',
				'</div>'
			]);
			
			fullscreencontent.ht(ls.join("\n"));
			pc.AutoWidth();
		}
	);
}

// --------------------------------------------------------------------
LessonSelect.prototype.Lessons	=	function(courseid)
{
	var fullscreencontent	=	$('.FullScreenContent');
	fullscreencontent.ht('<img src="/images/loading.gif">');
	window.scroll(0, 0);
	
	db.Linked(
		'lessons',
		'Course',
		courseid,
		'Lesson',
		0,
		0,
		0,
		function(d)
		{
			var ls		=	[];
			var items	=	d.lessons;
			
			for (var i = 0; i < items.length; i++)
			{
				var item	=	items[i];
				ls.push([
					'<div class="Card HoverHighlight AutoWidth" onclick="lessonselect.OnLessonClick(this, ' + item.id + ', \'' + item.title + '\')" data-optimum-width=20>',
						'<span class=Title>' + item.title + '</span>',
					'</div>'
				].join("\n"));
			}
			
			ls	=	ls.concat(['<div class=Cleared></div>',
				'</div>',
				'<table class=ActionButtons>',
					'<tr>',
						'<td class=ConfirmButton onclick="lessonselect.Finished()">Start</td>',
						'<td></td>',
						'<td class=CancelButton onclick="lessonselect.Cancel()">' + T[36] + '</td>',
						'</tr>',
				'</div>'
			]);
			
			fullscreencontent.ht(ls.join("\n"));
			pc.AutoWidth();
		}
	);
}

// --------------------------------------------------------------------
LessonSelect.prototype.Finished	=	function()
{
	this.Cancel();
	this.finishfunc(this.lessonids);
}

// --------------------------------------------------------------------
LessonSelect.prototype.Cancel	=	function()
{
	$('.FullScreen').set('-DarkBlueGradient').hide();
}

// --------------------------------------------------------------------
LessonSelect.prototype.OnLessonClick	=	function(e, lessonid, title)
{
	if (!this.multiple)
	{
		this.finishedfunc(lessonid, title);
		this.Cancel();
		return;
	}

	e	=	$(e);
	var index	=	this.lessonids.indexOf(lessonid);
	
	if (index != -1)
	{
		e.set('-Selected');
		this.lessonids.splice(index, 1);
	} else
	{
		e.set('+Selected');
		this.lessonids.push(lessonid);
	}
}

var lessonselect	=	new LessonSelect();


