LBUTTON	= 0;
RBUTTON	= 1;
MBUTTON	= 2;

// ********************************************************************
// Master namespace for all Pafera library functions
P	=	{
	// --------------------------------------------------------------------
	Init: function()
	{
		this.lang						=	1;
		this.userid					=	0;
		this.wallpaper			=	'blue';
		this.texttheme			=	'dark';
	
		this.media					=	{};
		this.mediatoload		= [];
		this.onmedialoaded	= [];
		this.currentmediaid	= 0;
		
		this.events					=	{};
		this.messageboxids	=	[];
		this.features				=	[];
		
		this.possumbot			= 'possum';
		this.quickfuncs			= [];
		
		this.istouch			=	0;
		this.mousex				=	0;
		this.mousey				=	0;
		this.currenty			=	0;
		this.holdpos			=	0;
		this.ignoreup			=	0;
		
		this.lastclicktime	=	0;
		
		this.emsize				=	0;
		this.firstview		=	1;
		this.screensize		=	'small';
		this.currenturl		=	'';

		this.viewportwidth	=	0;
		this.viewportheight	=	0;

		this.dontrestorecontent	=	[];
		this.timeoffset		=	new Date().getTimezoneOffset();

		this.ischrome			=	/\bChrome\b/.test(navigator.userAgent);
		this.isfirefox		=	/\bFirefox\b/.test(navigator.userAgent);
		this.issafari			=	/\bSafari\b/.test(navigator.userAgent);
		this.isedge				=	/\bEdge\b/.test(navigator.userAgent);
		this.useadvanced	=	this.ischrome || this.isfirefox || this.issafari || this.isedge;
		this.preloads			=	{};

		this.screenorientation	=	window.innerWidth > window.innerHeight ? 'landscape' : 'portrait';
		this.currentpermissions	=	'';
		
		this.inphoneapp				=	(typeof JSBridge != "undefined");
		this.onuploadfinished	=	0;
		this.layerstack				=	[];
	},

	// --------------------------------------------------------------------
	// Convenience functions to generate URLs
	URL: function(page, app)
	{
		app	=	app	|| P.currentapp;
		return P.baseurl + app + '/' + page;
	},

	// --------------------------------------------------------------------
	APIURL: function(page, app)
	{
		app	=	app	|| P.currentapp;
		return P.baseurl + 'a/' + app + '/' + page;
	},

	// --------------------------------------------------------------------
	DataURL: function(page, app)
	{
		app	=	app	|| P.currentapp;
		return P.baseurl + 'd/' + app + '/' + page;
	},

	// --------------------------------------------------------------------
	ImageURL: function(page, app)
	{
		app	=	app	|| P.currentapp;
		return P.baseurl + 'i/' + app + '/' + page;
	},

	// --------------------------------------------------------------------
	SoundURL: function(page, app)
	{
		app	=	app	|| P.currentapp;
		return P.baseurl + 's/' + app + '/' + page;
	},

	// --------------------------------------------------------------------
	CSSURL: function(page, app)
	{
		app	=	app	|| P.currentapp;
		return P.baseurl + 'c/' + app + '/' + page + '.css';
	},

	// --------------------------------------------------------------------
	JSURL: function(page, app)
	{
		app	=	app	|| P.currentapp;
		return P.baseurl + 'j/' + app + '/' + page + '.js';
	},

	// --------------------------------------------------------------------
	Icon: function(name, app, classes, size)
	{
		classes	= classes || 'LineHeight';
		size		= size		|| '3';
	
		return '<img class="' + classes + '" src="' + P.IconURL(name, app) + '" style="width: ' + size + 'em; height: ' + size + 'em" />';
	},

	// --------------------------------------------------------------------
	IconURL: function(name, app)
	{
		app	= app || P.currentapp;
	
		return P.baseurl + 'i/' + app + '/icons.svg#' + name;
	},

	// --------------------------------------------------------------------
	Target: function(e)
	{
		e = e || window.event;
		var target = e.target || e.srcElement;
		return $(target);
	},
	
	// --------------------------------------------------------------------
	Selected: function(selector) 
	{
		var item	=	$$(selector);
		return item.options[item.selectedIndex];
	},

	// --------------------------------------------------------------------
	Debug: function(msg) 
	{
		$$('.DebugMessages').innerHTML	+= msg + "\n";
	},

	// --------------------------------------------------------------------
	CancelBubble: function(e) 
	{
		e	=	e || window.event;
	
    if (e.stopPropagation) 
		{
			e.preventDefault();
      e.stopPropagation();
    } else 
		{
      e.cancelBubble = true;
    }
	},
	
	// --------------------------------------------------------------------
	HTML: function(selector, ls) 
	{
		var s	=	$$(selector);
	
		if (s)
			s.innerHTML	= ls.join('\n');
	},

	// --------------------------------------------------------------------
	CodeDir: function(code) 
	{
		var ls	=	[];
	
		for (var i = 0, l = code.length; i < l; i += 3)
			ls.push(code.substr(i, 3))
	
		return ls.join('/');
	},

	// --------------------------------------------------------------------
	DoneTyping: function(selector, func, timeout) 
	{
		timeout	=	timeout	|| 1000;

		var self	=	$(selector);
		var el		=	self[0];

		self.on('|change |keydown |paste |input', 
			function(e)
			{
				if (el.typingtimeoutid)
					clearTimeout(el.typingtimeoutid);
			
				el.typingtimeoutid	=	setTimeout(
					function()
					{
						el.typingtimeoutid	=	0;
						func(self);
					},
					timeout
				);
			}
		);
		
		self.on('|blur', 
			function(e)
			{
				if (el.typingtimeoutid)
				{
					clearTimeout(el.typingtimeoutid);			
					el.typingtimeoutid	=	0;
					func(self);
				}
			}
		);
	},

	// --------------------------------------------------------------------
	OnClick: function(
		selector, 
		button,
		clickfunc, 
		doubleclickfunc, 
		holdfunc
	) 
	{
		var el	= interact(selector);
		
		if (clickfunc)
		{
			el.on(
				'tap', 
				function(e)
				{
					if (clickfunc && (e.button == button || e.button == undefined))
					{
						// Prevent taps after holds and phantom double taps 
						if ((!P.holdpos 
							|| (Math.abs(e.screenX - P.holdpos.x) > 32
								|| Math.abs(e.screenY - P.holdpos.y) > 32))
						)
						{							
							clickfunc(e);
						}
					
						P.holdpos	= 0;
					}
				}
			);
		}
	
		if (doubleclickfunc)
		{
			el.on(
				'doubletap', 
				function(e)
				{
					if (doubleclickfunc && (e.button == button || e.button == undefined))
					{
						doubleclickfunc(e);
					}
				}
			);
		}
	
		if (holdfunc)
		{
			el.on(
				'hold', 
				function(e)
				{
					if (holdfunc && (e.button == button || e.button == undefined))
					{
						holdfunc(e);
						P.holdpos	= {x: e.screenX, y: e.screenY};
					}
				}
			);
		}
	},

	// --------------------------------------------------------------------
	AddHandler: function(event, func) 
	{
		if (!this.events[event])
		{
			this.events[event]	=	[];
		}
	
		this.events[event].push(func);
	},

	// --------------------------------------------------------------------
	ClearHandlers: function(event) 
	{
		this.events[event]	=	[];
	},

	// --------------------------------------------------------------------
	Loading: function(selector)
	{
		var el	=	$(selector);
	
		if (el.length)
		{
			el.ht([
				'<div class=Center>',
					'<img style="height: 1.3em;" src="' + this.baseurl + 'i/h/loading.gif">',
					'<div class=Cleared></div>',
				'</div>'
			].join('\n'));
		
			//P.ScrollTo(el);
		}
	},

	// --------------------------------------------------------------------
	// An advanced form of the scrollIntoView() function which scrolls
	// smoothly and uses the minimum amount of scrolling needed
	ScrollTo: function(selector)
	{
		setTimeout(
			function()
			{
				var el	=	$(selector);
			
				if (el.length)
				{
					var rt 		= P.AbsoluteRect(el);
					var above	= rt.top < window.pageYOffset;
				
					if (above || rt.bottom > window.pageYOffset + window.innerHeight)
					{	
						el[0].scrollIntoView({
							behavior:	'smooth',
							block:		above ? 'start' : 'end'
						});
					}
				}
			},
			200
		);
	},

	// --------------------------------------------------------------------
	AbsoluteRect: function(e)
	{
		var el	=	$$(e);
	
		if (!el)
			return 0;
	
		var rt	=	el.getBoundingClientRect();
		return {
			left:			rt.left + window.pageXOffset,
			top:			rt.top + window.pageYOffset,
			right:		rt.right + window.pageXOffset,
			bottom:		rt.bottom + window.pageYOffset,
			width:		rt.width,
			height:		rt.height
		};
	},

	// --------------------------------------------------------------------
	EMSize: function(parent)
	{
		parent = parent || document.body;
		return Number(
			getComputedStyle(parent, "")
			.fontSize.match(/(\d*(\.\d*)?)px/)[1]
		);
	},

	// --------------------------------------------------------------------
	AutoWidth: function()
	{
		// Rearrange elements for optimum width by the data-autowidth
		// attribute. This is extremely resource
		// intensive, but since we'll only do it once per orientation change,
		// it should be a minor annoyance.
		$('.AutoWidth').each(
			function(item, index, context)
			{
				item	=	$(item);
				var parent	=	item.up();
				var pw			=	parent.get('$width', true);
				var px			=	P.EMSize($$(parent));
				var em			=	item.get('%autowidth', true);
				var columns	=	Math.round(pw / (px * em));
				var padding	=	0;

				var paddings	=	item.get(
					[
						'$marginLeft',
						'$paddingLeft',
						'$paddingRight',
						'$marginRight'
					],
					true
				);

				for (var k in paddings)
				{
					if (paddings[k])
						padding	+=	paddings[k];
				}

				if (!columns)
					columns = 1;

				var newwidth	=	Math.floor((pw / columns) - padding);

				item.set('$width', newwidth + 'px');
			
				if (item.get('%autosquare'))
				{
					item.set('$height', newwidth + 'px');
				}
			}
		);
	},

	// --------------------------------------------------------------------
	// Sets the selected elements to the same width. Useful for floating
	// elements
	SameWidth: function(selector, numems, heightratio)
	{
		var elements	=	$(selector);

		if (!elements.length)
			return;
	
		var item		=	$(elements[0]);
	
		var parent	=	item.up();
		var pw			=	parent.get('$width', true);
		var emsize	=	P.EMSize($$(parent));
		var columns	=	Math.round(pw / (numems * emsize));
		var padding	=	0;

		var paddings	=	item.get(
			[
				'$marginLeft',
				'$paddingLeft',
				'$paddingRight',
				'$marginRight'
			],
			true
		)

		for (var k in paddings)
		{
			if (paddings[k])
				padding	+=	paddings[k];
		}

		if (!columns)
			columns = 1;
		
		var newwidth	=	Math.floor((pw / columns) - padding);
		elements.set('$width', newwidth + 'px');
	
		if (heightratio)
			elements.set('$height', Math.floor(newwidth * heightratio) + 'px');
	
		return newwidth + 'px';
	},

	// --------------------------------------------------------------------
	// Sets the selected elements to the maximum width. Useful for 
	// grid layouts
	SameMaxWidth: function(selector)
	{
		var maxwidth	=	0;
		var elements	=	$(selector);

		if (!elements.length)
			return;

		elements.each(
			function(item, index, context)
			{
				var width		=	parseFloat($$(item).clientWidth);

				if (width > maxwidth)
				{
					maxwidth	=	width;
				}
			}
		);

		var px	=	P.EMSize($$(elements[0]));
		var ems	= Math.round(((maxwidth / px) + 0.00001) * 100) / 100;

		elements.set('$width', ems + 'em');
	},

	// --------------------------------------------------------------------
	// Sets the selected elements to the maximum height. Useful for floating
	// elements
	SameMaxHeight: function(selector)
	{
		var maxheight	=	0;
		var elements	=	$(selector);

		if (!elements.length)
			return;

		elements.each(
			function(item, index, context)
			{
				var height		=	parseFloat($$(item).clientHeight);

				if (height > maxheight)
				{
					maxheight	=	height;
				}
			}
		);

		var px	=	P.EMSize($$(elements[0]));
		var ems	= Math.round(((maxheight / px) + 0.00001) * 100) / 100;

		elements.set('$height', ems + 'em');
	},

	// --------------------------------------------------------------------
	Trace: function()
	{
		StackTrace.get().then(
			function(stackframes) 
			{
				var stringifiedStack = stackframes.map(
					function(sf) 
					{ 
						return sf.toString(); 
					}
				).join('\n'); 
				console.log(stringifiedStack); 
			}
		)/*.catch(
			function(err) 
			{
				console.log(err.message); 
			}
		);*/
	},

	// --------------------------------------------------------------------
	Popup: function(content, options)
	{
		var defaults	=	{
			'$width':						'16em',
			'$color':						'black',
			'$backgroundColor':	'transparent',
			'$zIndex': 					1000
		};
	
		switch (P.screensize)
		{
			case 'medium':
				defaults.width	=	'22em';
				break;
			case 'large':
				defaults.width	=	'30em';
				break;
		};
	
		options	=	Merge(defaults, options);
	
		// Generate an ID for this popup
		var popupclass	=	options.popupclass ? options.popupclass : 0;

		if (!popupclass)
		{
			for (;;)
			{
				popupclass	=	'Popup' + RandInt(1, 999999999);

				if (!$('.' + popupclass).length)
					break;
			}
		}

		$('body').add(
			HTML('<div class="Popup ' + popupclass + '" style="position: absolute; left: 0; top: 0;"></div>')
		);

		var popup	=	$('.' + popupclass);
		popup.fill(content);
		popup.set(options);
	
		var viewportrect	=	P.ViewPortRect();
		var popuprect			=	popup[0].getBoundingClientRect();

		var	left	=	0;
		var top		=	0;

		if (options['parent'])
		{
			var parentrect	= P.AbsoluteRect(options['parent']);
		
			switch (options['position'])
			{
				case 'left':
					left	=	parentrect.left - popuprect.width;
					top		=	parentrect.top;
					break;
				case 'top':
					left	=	parentrect.left;
					top		=	parentrect.top - popuprect.height;
					break;
				case 'right':
					left	=	parentrect.right;
					top		=	parentrect.top;
					break;
				default:
					left	=	parentrect.left;
					top		=	parentrect.bottom;
			};
		} else
		{
			var parentrect	= {
				left:		P.mousex,
				top:		P.mousey,
				width:	0,
				height:	0
			};
		
			left	=	options['$left'] 
				? parseInt(options['$left']) 
				: viewportrect.width / 2 - popuprect.width / 2 + viewportrect.left;
			top		=	options['$top'] 
				? parseInt(options['$top'])
				: viewportrect.height / 2 - popuprect.height / 2 + viewportrect.top;
		}
	
		if (left + popuprect.width > viewportrect.right)
			left	=	viewportrect.right - popuprect.width - P.emsize;

		if (top + popuprect.height > viewportrect.bottom)
			top	=	viewportrect.bottom - popuprect.height;
	
		if (left < viewportrect.left)
			left	=	viewportrect.left;

		if (top < viewportrect.top)
			top	=	viewportrect.top;
	
		if (!options['noanimate'])
		{
			popup.set({
					'$left':		parentrect.left + 'px',
					'$top':			parentrect.top + 'px',
					'$width':		parentrect.width + 'px',
					'$height':	parentrect.height + 'px'
				}).show()
				.animate(
					{
						'$left':		left + 'px',
						'$top':			top + 'px',
						'$width':		options['$width'],
						'$height':	'auto'
					}, 
					300
				);
		}
		
		if (options.closeonmouseout)
		{
			setTimeout(
				function()
				{
					// Make sure that the popup doesn't immediately close in the short term
					P.mousex	=	left + 1;
					P.mousey	=	top + 1;
					P.ClosePopupOnMouseOut([options.parent, '.' + popupclass]);
				},
				500
			);
		}

		P.AddLayer(popupclass);
		return '.' + popupclass;
	},

	// --------------------------------------------------------------------
	ClosePopupOnMouseOut: function(elements, closefunc)
	{
		var inrects	=	0;

		for (var i = 0, l = elements.length; i < l; i++)
		{
			var rt	=	P.AbsoluteRect(elements[i]);

			if (InRect(P.mousex, P.mousey, rt))
			{
				inrects	=	1;
				break;
			} 
		}
	
		if (!inrects)
		{
			var selector	=	elements[elements.length - 1];
			P.RemoveLayer(selector);
			$(selector).remove();
			
			if (closefunc)
				closefunc();
			
			return;
		}
	
		setTimeout(
			function()
			{
				P.ClosePopupOnMouseOut(elements, closefunc);
			},
			500
		);
	},

	// --------------------------------------------------------------------
	CloseThisPopup: function(element)
	{
		var el	= $(element);
	
		if (el.is('.Popup'))
		{
			P.ClosePopup(el);
		} else
		{
			P.ClosePopup(el.up('div.Popup'));
		}
	},

	// --------------------------------------------------------------------
	MessageBox: function(content, buttons, options)
	{
		if (!options)
			options	=	{};
	
		if (!options.popupclass)
		{
			var id	= RandInt(1, 99999999);
		
			while (P.messageboxids.indexOf(id) != -1)
			{
				id	= RandInt(1, 99999999);
			}
			options.popupclass	= 'MsgBox' + id;
		}
	
		var ls	=	[
			'<div class="Pad50 whiteb Raised">',
		];
	
		if (!IsArray(content))
			content	= [content];
	
		ls.addtext(content);
			
		ls.push('<div class=MessageBoxBar></div></div>');
	
		P.Popup(HTML(ls.join('\n')), options);
	
		if (!buttons)
		{
			buttons = [
				[T[1], function() { P.CloseThisPopup('.' + options.popupclass + ' .MessageBoxBar') }]
			];
		}
			
		P.MakeButtonBar('.' + options.popupclass + ' .MessageBoxBar', buttons);
	},

	// --------------------------------------------------------------------
	ErrorPopup: function(content, options)
	{
		P.MessageBox(
			[
				'<div class="Pad50 Error">',
					P.Icon('Warning', 'h', 'FloatLeft Pad50', '4'),
					'<div class=ErrorPopupMsg>' + content + '</div>',
					'<div class=Cleared></div>',
				'</div>',
				'<br class=Cleared>'
			],
			[
				[T[1], function() { P.CloseThisPopup('.ErrorPopupMsg') }]
			],
			Merge(
				{
					popupclass:	'ErrorPopup'
				}, 
				options
			)
		);
	},

	// --------------------------------------------------------------------
	ClosePopup: function(popupclass)
	{
		popupclass	=	popupclass	|| '.Popup';
	
		$(popupclass).animate(
			{
				'$left':		'0px',
				'$top':			'0px',
				'$width':		'0px',
				'$height':	'0px'
			},
			300
		);
		
		setTimeout(
			function()
			{
				var el = $(popupclass);
			
				if (el.length)
					el.remove();
			},
			300
		);
	},

	// --------------------------------------------------------------------
	EditPopup: function(fields, onsuccess, options)
	{
		options	=	options	|| {};
	
		var defaults	=	{
			'$backgroundColor':	'white',
			popupclass:	'EditPopup'
		};

		var formdiv						=	options.formdiv;
		var formclass					= formdiv ? formdiv + 'Form' : 'EditPopupForm';
		var cancelfunc				=	options.cancelfunc;
		var fullscreen				=	options.fullscreen;
		var gobuttontext			=	options.gobuttontext;
		var cancelbuttontext	=	options.cancelbuttontext;
		var extrabuttons			=	options.extrabuttons			|| '';
		var resultsdiv				=	formdiv ? (formdiv + 'Results') : 'EditPopupResults';
	
		delete options.formdiv;
		delete options.cancelfunc;
		delete options.fullscreen;
		delete options.gobuttontext;
		delete options.cancelbuttontext;
		delete options.extrabuttons;
	
		options	=	Merge(defaults, options);
	
		var ls	=	[
			'<form class="' + formclass + '">'
		];

		if (P.screensize != 'small')
		{
			ls.push('<table class="Width100 EditPopupTable"><tbody>');

			if (!options['$width'])
				options['$width']	=	'40em';
		}
	
		for (var i = 0, l = fields.length; i < l; i++)
		{
			if (!fields[i])
				continue;
		
			var name							= fields[i][0];
			var valuetype					= fields[i][1];
			var value							= fields[i][2];
			var displayname				=	fields[i][3];
			var placeholder				=	fields[i][4]; // options for selects
			var attrs							=	fields[i][5]; // Attributes for input element
		
			if (!attrs)
				attrs	=	'';

			if (!value)
				value	=	'';

			if (!placeholder)
				placeholder	=	'';
		
			switch (valuetype)
			{
				case 'select':
					var opts	=	[];
					
					if (IsArray(placeholder))
					{
						for (var j = 0, m = placeholder.length; j < m; j++)
						{
							var v	=	placeholder[j];
							opts.push('<option ' + ((value == v) ? 'selected' : '') + '>' + v + '</option>');
						}
					} else
					{
						for (var k in placeholder)
						{
							var v	=	placeholder[k];
							opts.push('<option value="' + v + '" ' + ((value == v) ? 'selected' : '') + '>' + k + '</option>');
						}
					}
			
					editfield	=	'<select name="' + name + '" class="' + name + '" ' + attrs + '>' + opts.join('\n') + '</select>';
					break;
				case 'multitext':
					editfield	=	'<div class=FlexInput data-name="' + name + '" class="' + name + '" data-value="' + EncodeEntities(value.toString()) + '" placeholder="' + EncodeEntities(placeholder) + '" data-attrs="' + EncodeEntities(attrs) + '">';
					break;
				case 'int':
					editfield	=	'<input type=number name="' + name + '" class="' + name + '" value="' + value + '" placeholder="' + EncodeEntities(placeholder) + '" ' + attrs + '>';
					break;
				case 'float':
					editfield	=	'<input type=number step=any name="' + name + '" class="' + name + '" value="' + value + '" placeholder="' + EncodeEntities(placeholder) + '"' + attrs + '>';
					break;
				case 'datetime-local':
					editfield	=	'<input type=datetime-local name="' + name + '" class="' + name + '" value="' + GMTToLocal(value) + '" placeholder="' + EncodeEntities(placeholder) + '"' + attrs + '>';
					break;
				case 'custom':
					editfield	=	'<input type=hidden name="' + name + '" class="' + name + '" value="' + EncodeEntities(value.toString()) + '"' + attrs + '><div class="FormRow ' + placeholder + '"></div>';
					break;
				default:
					editfield	=	'<input type=' + valuetype + ' name="' + name + '" class="' + name + '" value="' + EncodeEntities(value.toString()) + '" placeholder="' + EncodeEntities(placeholder) + '"' + attrs + '>';
			};

			if (valuetype == 'hidden')
			{
				ls.push(editfield);
			} else
			{
				if (P.screensize == 'small')
				{
					ls	=	ls.concat([
						'<div class="' + name + 'Row">',
							'<label>' + displayname + '</label>',
							editfield,
						'</div>'
					]);
				} else
				{
					ls	=	ls.concat([
						'<tr class="' + name + 'Row">',
							'<th class=Width8>' + displayname + '</th>',
							'<td>' + editfield + '</td>',
						'</tr>'
					]);
				}
			}
		}

		if (P.screensize != 'small')
		{
			ls.push('</tbody></table><br>');
		}

		ls.addtext([
					'<br class=Cleared>',
					'<div class=ButtonBar>',
						'<a class="ConfirmButton Color3"><img class="LineHeight BusyIcon">' + (gobuttontext ? gobuttontext : T[47]) + '</a>',
						'<a class="Spacer Width2"></a>',
						formdiv ? '<a class="ResetButton Color2"><img class="LineHeight BusyIcon">' + T[39] + '</a>' : '',
						'<a class="CancelButton Color1"><img class="LineHeight BusyIcon">' + (cancelbuttontext ? cancelbuttontext : T[1]) + '</a>',
						extrabuttons,
					'</div>',
				'</form>',
				'<div class="' + resultsdiv + '"></div>',
				'<div class=Cleared></div>'
		]);

		if (formdiv)
		{
			P.HTML('.' + formdiv, ls);
		
			var resetbutton	= '.' + formdiv + ' .ResetButton';
		
			$(resetbutton).on(
				'click', 
				P.Busy(
					resetbutton,
					function() 
					{ 
						$$('.' + formclass).reset(); 
					}
				)
			);
			
			if (cancelfunc)
			{
				var cancelbutton	= '.' + formdiv + ' .CancelButton';
			
				$(cancelbutton).on(
					'click', 
					P.Busy(
						cancelbutton,
						function() 
						{ 
							cancelfunc('.' + formdiv);
						}
					)
				);
			}
		} else if (fullscreen || P.screensize == 'small')
		{
			$('.FullScreen').set('+dblueg').ht(ls.join('\n')).show();
		
			var cancelbutton	= '.FullScreen .CancelButton';
		
			$(cancelbutton).on(
				'click', 
				P.Busy(
					cancelbutton,
					function()
					{
						$('.FullScreen').hide();
					
						if (cancelfunc)
							cancelfunc('.FullScreen');
					}
				)
			);
			formdiv	=	'FullScreen';
		} else
		{
			formdiv = P.Popup(HTML('<div class="whiteb Pad50 Raised">' + ls.join('\n') + '</div>'), options);
			var cancelbutton	=	formdiv + ' .CancelButton';
		
			$(cancelbutton).on(
				'click', 
				P.Busy(
					cancelbutton,
					function()
					{ 
						$(formdiv).remove();
					
						if (cancelfunc)
							cancelfunc(formdiv);
					}
				)
			);
		}
	
		var confirmbutton	= '.' + formclass + ' .ConfirmButton';
	
		$(confirmbutton).on(
			'click',
			P.Busy(
				confirmbutton,
				function()
				{
					if (onsuccess('.' + formdiv, P.FormToArray('.' + formclass), $('.' + resultsdiv)))
					{
						if (formdiv == 'FullScreen')
						{
							$('.FullScreen').hide();
						} else if (formdiv.indexOf('#Popup') != -1)
						{ 
							P.CloseThisPopup(this);
						}
					}
				}
			)
		);

		$('.' + formclass).on(
			'submit',
			function(e)
			{
				if (e.preventDefault) 
					e.preventDefault();
				
			}
		);
		
		P.FlexInput();

		var inputs	=	$('.' + formclass + ' input');

		if (inputs.length)
		{
			inputs[0].focus();
		} else
		{
			inputs	=	$('.' + formclass + ' textarea');

			if (inputs.length)
				inputs[0].focus();
		}
	
		if (P.screensize != 'small')
		{
			$('.EditPopupTable select').set({
				'$position': 'relative',
				'$top':			'1em'
			});
			$('.EditPopupTable th').set({
				'$position': 'relative',
				'$top':			'0.5em'
			});
			$('.EditPopupTable input').set({
				'$margin': '0px',
				'$width':		'110%'
			});
			$('.EditPopupTable .FlexInput').set({
				'$marginTop': '1em',
				'$border':		'0.01em solid transparent'
			});
		}
	},

	// --------------------------------------------------------------------
	EditThis: function(selector, onsuccess, fields)
	{
		var el	=	$(selector);

		if (fields)
		{
			fields	=	[['newvalue'].concat(fields)];
		} else
		{
			fields	=	[['newvalue', 'multitext', text, 'Change text']];
		}

		var text	=	el.text();

		P.EditPopup(
			fields,
			function()
			{
				var newvalue	=	$('.newvalue').get('value');

				el.fill(newvalue);

				if (onsuccess)
					onsuccess(el, newvalue);

				return true;
			},
			{
				parent:	el,
				popupclass:	'EditThisPopup'
			}
		);
	},

	// --------------------------------------------------------------------
	FlagToToggle: function(value, onflag, offflag)
	{
		var base	=	' data-toggled=';
	
		if (value & onflag)
			return base + 'on';
	
		if (value & offflag)
			return base + 'off';
	
		return base + 'unset';
	},
	
	// --------------------------------------------------------------------
	EditPermissionPopup: function(model, ids)
	{
		P.HTML(
			'.FullScreenContent',
			[
				'<div class="Pad50 EditPermissions">',
					'<h2>Editing Permissions for ' + model + '(' + JSON.stringify(ids) + ')</h2>',
					'<div class=PermissionResults></div>',
					'<div class=ButtonBar>',
						'<a class="ConfirmButton Color3" onclick="P.SavePermissions()">' + T[34] + '</a>',
						'<a class="AddButton Color2" onclick="P.AddPermissionPopup()">' + T[21] + '</a>',
						'<a class="CancelButton Color1" onclick="$(\'.FullScreen\').hide();">' + T[1] + '</a>',
					'</div>',
					'<div class=Cleared></div>',
					'<div class=SavePermissionResults></div>',
				'</div>'
			]
		);
	
		$('.FullScreen').set('+dblueg').show();
	
		P.LoadingAPI(
			'.PermissionResults',
			'permissions/get',
			{
				model:	model,
				objid:	ids,
			},
			function(d, resultsdiv)
			{
				d.item.model	=	model;
				d.item.ids		=	ids;
				P.currentpermissions	=	d.item;
				P.DisplayPermissions();
			}
		);
	},

	// --------------------------------------------------------------------
	DisplayPermissions: function()
	{
		var resultsdiv	=	$('.PermissionResults');
	
		if (!P.currentpermissions)
		{
			resultsdiv.ht('No permissions found');
			return;
		}
		
		var item	= P.currentpermissions;
	
		var ls	=	[
			'<table class="Width100 Permissions">',
				'<tr data-type=owner>',
					'<th>Owner</th>',
					'<td colspan=7 class="greeng Owner HoverHighlight" data-objid=' + item.owner + '>' + item.ownername + '</td>',
				'</tr>',
				'<tr data-type=defaults>',
					'<th>Defaults</th>',
					'<td class=ToggleButton' + P.FlagToToggle(item.access, 1, 2) + '>Create</td>',
					'<td class=ToggleButton' + P.FlagToToggle(item.access, 0x40, 0x80) + '>Read</td>',
					'<td class=ToggleButton' + P.FlagToToggle(item.access, 0x4, 0x8) + '>Write</td>',
					'<td class=ToggleButton' + P.FlagToToggle(item.access, 0x10, 0x20) + '>Delete</td>',
					'<td class=ToggleButton' + P.FlagToToggle(item.access, 0x100, 0x200) + '>Link</td>',
					'<td class=ToggleButton' + P.FlagToToggle(item.access, 0x1000, 0x2000) + '>View All</td>',
					'<td></td>',
				'</tr>',
				'<tr data-type=header class=usersheader>',
					'<th colspan=7>Users</th>',
				'</tr>'
		];
		
		var l	=	item.users.length;
		
		if (!l)
		{
			ls.push('<tr data-type=none><td colspan=7>No users added.</td></tr>');
		} else
		{
			for (var i = 0, l = item.users.length; i < l; i++)
			{
				var user		=	item.users[i];
				var access	=	user.access;
			
				ls.addtext([
					'<tr data-type=user data-objid=' + user.id + '>',
						'<th>' + user.name + '</th>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 1, 2) + '>Create</td>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 0x40, 0x80) + '>Read</td>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 0x4, 0x8) + '>Write</td>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 0x10, 0x20) + '>Delete</td>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 0x100, 0x200) + '>Link</td>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 0x1000, 0x2000) + '>View All</td>',
						'<td class=Color1 onclick="P.DeletePermissions(this)">Delete</td>',
					'</tr>'
				]);
			}
		}
	
		ls.addtext([
			'<tr data-type=header class=groupsheader>',
				'<th colspan=7>Groups</th>',
			'</tr>'
		]);
	
		if (!l)
		{
			ls.push('<tr data-type=none><td colspan=7>No groups added.</td></tr>');
		} else
		{
			for (var i = 0, l = item.groups.length; i < l; i++)
			{
				var group		=	item.groups[i];
				var access	=	group.access;
			
				ls.addtext([
					'<tr data-type=user data-objid=' + group.id + '>',
						'<th>' + group.name + '</th>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 1, 2) + '>Create</td>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 0x40, 0x80) + '>Read</td>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 0x4, 0x8) + '>Write</td>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 0x10, 0x20) + '>Delete</td>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 0x100, 0x200) + '>Link</td>',
						'<td class=ToggleButton' + P.FlagToToggle(access, 0x1000, 0x2000) + '>View All</td>',
						'<td class=Color1 onclick="P.DeletePermissions(this)">Delete</td>',
					'</tr>'
				]);
			}
		}
		
		ls.push('</table>');
	
		P.HTML(resultsdiv, ls);
		
		P.MakeToggleButtons('.EditPermissions .ToggleButton', 1);
	
		P.OnClick('.Permissions .Owner', LBUTTON, P.ChangeOwnerPopup);
	},
			
	// --------------------------------------------------------------------
	DeletePermissions: function(e)
	{
		var tr		=	$(e).up('tr');
		var objid	=	parseInt(tr.get('%objid'));
		var name	=	$('th', tr, 1).text();
		var item	=	P.currentpermissions;
		
		switch (tr.get('%type'))
		{
			case 'user':
				for (var i = 0, l = item.users.length; i < l; i++)
				{
					var user	=	item.users[i];
				
					if ((objid && user.id == objid) || user.name == name)
					{
						P.currentpermissions.users.splice(i, 1);
						break;
					}
				}
				break;
			case 'groups':
				for (var i = 0, l = item.groups.length; i < l; i++)
				{
					var group	=	item.groups[i];
				
					if ((objid && group.id == objid) || group.name == name)
					{
						P.currentpermissions.groups.splice(i, 1);
						break;
					}
				}
				break;
		};
	
		P.DisplayPermissions();
	},

	// --------------------------------------------------------------------
	ChangeOwner: function()
	{
		var value	=	$$('.newowner').value;
		$('.Permissions .Owner').set('%objid', 0).fill(value);
		P.currentpermissions.owner			=	0;
		P.currentpermissions.ownername	=	value;
		P.ClosePopup('.ChangeOwnerPopup');
		return true;
	},

	// --------------------------------------------------------------------
	ChangeOwnerPopup: function()
	{
		P.EditPopup(
			[['newowner', 'text', '', T[53]]],
			P.ChangeOwner,
			{
				parent:	'.Permissions .Owner',
				cancelfunc:	function(formdiv){ P.CloseThisPopup(formdiv); },
				popupclass:	'ChangeOwnerPopup'
			}
		);
		P.OnEnter('.newowner', P.ChangeOwner);
	},

	// --------------------------------------------------------------------
	AddPermissionPopup: function(model, ids)
	{
		P.Popup(
			HTML([
				'<div class="Pad50 whiteb AddPermission">',
					'<div>Type of permissions to add:</div>',
					'<div class="Radio AddTypeSelect">',
						'<input type=text value=users class=addtype>',
						'<div data-value=users>User</div>',
						'<div data-value=groups>Group</div>',
					'</div>',
					'<br class=Cleared><br>',
					'<div>',
						'Name or phone to add:',
					'</div>',
					'<div>',
						'<input type=text class=addid>',
					'</div>',
					'<div class=ButtonBar>',
						'<a class="ConfirmButton Color3" onclick="P.AddPermission()">' + T[21] + '</a>',
						'<a class="Spacer Width400"></>',
						'<a class="CancelButton Color1" onclick="P.CloseThisPopup(this);">' + T[1] + '</a>',
					'</div>',
					'<div class=Cleared></div>',
				'</div>'
			].join('\n')),
			{
				popupclass:	'AddPermissionPopup'
			}
		);
	
		P.MakeRadioButtons('.AddTypeSelect');
		P.OnEnter('.addid', P.AddPermission);
	},
	
	// --------------------------------------------------------------------
	AddPermission: function()
	{
		P.currentpermissions[$$('.addtype').value].push({
			id:			0, 
			name: 	$$('.addid').value,
			access:	0
		});
	
		P.DisplayPermissions();
		P.ClosePopup('.AddPermissionPopup');
	},

	// --------------------------------------------------------------------
	RowToAccess: function(e)
	{
		var access	=	0;
	
		$('td', e, 1).each(
			function(item, index)
			{
				var toggled	=	$(item).get('%toggled');
				
				switch (toggled)
				{
					case 'on':
						switch (index)
						{
							case 0:
								access	|=	0x1;
								break;
							case 1:
								access	|=	0x40;
								break;
							case 2:
								access	|=	0x4;
								break;
							case 3:
								access	|=	0x10;
								break;
							case 4:
								access	|=	0x100;
								break;
							case 5:
								access	|=	0x1000;
								break;
						};
						break;
					case 'off':
						switch (index)
						{
							case 0:
								access	|=	0x2;
								break;
							case 1:
								access	|=	0x80;
								break;
							case 2:
								access	|=	0x8;
								break;
							case 3:
								access	|=	0x20;
								break;
							case 4:
								access	|=	0x200;
								break;
							case 5:
								access	|=	0x2000;
								break;
						};
						break;
				};
			}
		);
			
		return access;
	},

	// --------------------------------------------------------------------
	SavePermissions: function()
	{
		var data	=	{
			owner:			$('.Owner').get('%objid'),
			ownername:	$('.Owner').text(),
			users:			[],
			groups:			[],
			model:			P.currentpermissions.model,
			objid:			P.currentpermissions.ids
		};
		
		$('.Permissions tr').each(
			function(item)
			{
				item	=	$(item);
			
				switch (item.get('%type'))
				{
					case 'user':
						data.users.push({
							id:			item.get('%objid'), 
							name:		$('th', item, 1).text(), 
							access:	P.RowToAccess(item)
						});
						break;
					case 'group':
						data.groups.push({
							id:			item.get('%objid'), 
							name:		$('th', item, 1).text(), 
							access:	P.RowToAccess(item)
						});
						break;
					case 'owner':
						var td	=	$('td', item, 1);
						data.owner			=	td.get('%objid');
						data.ownername	=	td.text();
						break;
					case 'defaults':
						data.access	=	P.RowToAccess(item);
						break;
				};
			}
		);
		
		P.LoadingAPI(
			'.SavePermissionResults',
			'permissions/set',
			data,
			function()
			{
				$('.FullScreen').hide();
			},
			{
				timeout:	600
			}
		);
	},

	// --------------------------------------------------------------------
	SortableTDs: function()
	{
		return [
				'<td class="Color3 Center">',
					P.Icon('Down', 'h', 'FlipV', 1.5),			
				'</td>',
				'<td class="Color4 Center">',
					P.Icon('Down', 'h', '', 1.5),			
				'</td>',
				'<td class="Color1 Center">',
					P.Icon('Cancel', 'h', '', 1.5),			
				'</td>',
			'</tr>'
		];
	},
	
	// --------------------------------------------------------------------
	Tooltip: function(selector, func, delay)
	{
		delay	=	delay	|| 1000;
		$(selector).onOver(
			function(isover, evt)
			{
				var self	=	$(this);
			
				if (isover)
				{
					self.set(
						'%tooltiptimer', 
						setTimeout(
							function()
							{
								var popupid = P.Popup(
									HTML(func(self)),
									{
										parent:						self,
										position:					'top',
										closeonmouseout:	1
									}
								);
								
								$(popupid).set('+Tooltip');
							},
							delay
						)
					);
				} else
				{
					var timerid	=	self.get('%tooltiptimer');
				
					if (timerid)
					{
						clearTimeout(timerid);
						self.set('%tooltiptimer', '');
					}
				}
			}
		);
	},

	// --------------------------------------------------------------------
	MakeButtonBar: function(selector, buttons)
	{
		var ls	=	[];
	
		var color	=	1;
		var item	= 0;
		var cls		= '';
	
		for (var i = 0, l = buttons.length; i < l; i++)
		{
			item	=	buttons[i];
			
			if (item[1] == 'custom')
			{
				ls.push(item[0]);
				continue;
			}
		
			if (item.length > 2 && item[2])
				color	=	item[2];
		
			cls	=	(item.length > 3) ? item[3] : '';
		
			ls.push('<a class="Color' + color + ' Button' + i + ' ' + cls + '"><img class="LineHeight BusyIcon">' + item[0] + '</a>');
		
			color++;
			
			if (color > 6)
				color	=	1;
		}
	
		$(selector).set('+ButtonBar').ht(ls.join('\n'));
	
		for (var i = 0, l = buttons.length; i < l; i++)
		{
			var item	= selector + ' .Button' + i;
			P.OnClick(item, LBUTTON, P.Busy(item, buttons[i][1]));
		}
	},
	
	// --------------------------------------------------------------------
	DragMoveListener: function(event) 
	{
		var target = event.target,
				// keep the dragged position in the data-x/data-y attributes
				x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx,
				y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

		// translate the element
		target.style.webkitTransform =
		target.style.transform =
			'translate(' + x + 'px, ' + y + 'px)';

		// update the posiion attributes
		target.setAttribute('data-x', x);
		target.setAttribute('data-y', y);
	},
	
	// --------------------------------------------------------------------
	MakeFloatingButton: function(selector, content)
	{
		var button	= $(selector);
	
		if (!button.length)
		{
			var popupid	= P.Popup(HTML(content));
			button	= $(popupid);
			button.hide();
		}
	
		setTimeout(
			function()
			{
				button.set('+' + selector.substr(1)).set({
					'$zIndex':		'1100',
					'$position':	'fixed',
					'$left':			'110%',
					'$top':				'80%',
					'$cursor':		'nwse-resize'
				}).show().animate(
					{
						'$left':			'80%',
						'$top':				'80%',
						'$width':			'auto'
					},
					300
				);
			},
			500
		);
		
		interact(selector).draggable({
			inertia:			true,
			autoScroll:		true,
			onmove: 			P.DragMoveListener,
			restrict: {
				restriction: "parent",
				endOnly: true,
				elementRect: { top: 0, left: 0, bottom: 1, right: 1 }
			},
		});
	
		return selector;
	},

	// --------------------------------------------------------------------
	Busy: function(selector, func)
	{
		return function(evt, target)
		{
			var img			= $(selector + ' .BusyIcon');
			var origsrc	= img.get('@src');
		
			img.set('@src', P.ImageURL('busy.svg', 'h'));
		
			func(evt, target);
		
			setTimeout(
				function()
				{
					img.set('@src', origsrc);
				},
				500
			);
		}
	},
	
	// --------------------------------------------------------------------
	MakeRange: function(selector, classname, min, max, value, title, onchange)
	{
		value		=	value ? value : min;
		title		=	title	|| '';
	
		var el	=	$(selector);
		
		P.HTML(
			el,
			[
				'<div class=ButtonBar>',
						title ? '<span class=RangeTitle>' + title + '</span>' : '',
						'<meter class="Range ' + classname + '" min=' + min + ' max=' + max + ' value=' + value + '></meter>',
						'<span class=RangeDisplay>' + value + '</span>',
				'</div>'
			]
		);
	
		P.OnClick(
			selector + ' .Range',
			LBUTTON,
			function(e)
			{
				var self	=	$(selector + ' .Range');
			
				var min		=	parseInt(self.get('@min'));
				var max		=	parseInt(self.get('@max'));
				var value	=	self[0].value;
			
				var rt	=	P.AbsoluteRect(self);
			
				var newvalue	=	Math.round(((P.mousex - rt.left) / rt.width) * (max - min)) + min;
				
				if (value == newvalue)
					return;
				
				self[0].value	=	newvalue;
				$(selector + ' .RangeDisplay').fill(newvalue);			
				onchange(self, newvalue);
			}
		);
	},

	// --------------------------------------------------------------------
	MoveUp: function(el, selector, onfinished)
	{
		el	=	$(el);
	
		if (selector)
			el	=	el.up(selector);

		if (!el.length)
			return;

		var previous	=	$(el.trav('previousSibling')[0]);
		el.addAfter(previous.clone());
		previous.remove();

		if (onfinished)
			onfinished(el);
	},

	// --------------------------------------------------------------------
	MoveDown: function(el, selector, onfinished)
	{
		el	=	$(el);
	
		if (selector)
			el	=	el.up(selector);

		if (!el.length)
			return;

		var next	=	el.next();
		el.addBefore(next.clone());
		next.remove();

		if (onfinished)
			onfinished(el);
	},

	// --------------------------------------------------------------------
	MakeSortableTable: function(selector, onchangefunc)
	{
		$(selector).on(
			'click',
			function(e)
			{
				var target	=	P.Target(e);
				
				if (target[0].tagName != 'TD')
					target	=	target.up('td');
			
				var thistr	=	target.up('tr');
				var img			= $('img', target);
				var src			=	img.get('@src');
			
				if (!src)
					return;
				
				if (src.indexOf('#Cancel') != -1)
				{
					thistr.remove();
				
					if (onchangefunc)
						onchangefunc(selector);
				} else if (src.indexOf('#Down') > -1)
				{
					if (img.is('.FlipV'))
					{
						P.MoveUp(thistr);
					} else 
					{
						P.MoveDown(thistr);
					}
				
					if (onchangefunc)
						onchangefunc(selector);
				}
			}
		);
	},

	// --------------------------------------------------------------------
	ConfirmDeletePopup: function(objname, deletefunc)
	{
		P.Popup(HTML([
				'<div class="DeletePopup yellowb Pad50">',
					'<p>',
						T[57] + objname + '?',
					'</p>',
					'<div class=DeleteButtonBar></div>',
					'<div class=Cleared></div>',
					'<div class=ConfirmDeleteResults></div>',
					'<div class=Cleared></div>',
				'</div>'
			].join("\n")),
			{
				popupclass:	'ConfirmDeletePopup'
			}
		);
		
		P.MakeButtonBar(
			'.DeleteButtonBar',
			[
				[T[34], deletefunc]
				[T[1], function(evt, target) { P.CloseThisPopup(target); }]
			]
		);
		
	},

	// =====================================================================
	// Thanks to http://stackoverflow.com/questions/16894603/javascript-incorrect-viewport-size
	ViewPortRect: function()
	{
		var w = window,
			d = document,
			e = d.documentElement,
			g = d.getElementsByTagName('body')[0];

		/*
		var width		=	Math.max(w.innerWidth || e.clientWidth || g.clientWidth);
		var height	=	Math.max(w.innerHeight|| e.clientHeight|| g.clientHeight);
		*/
	
		var width		=	w.innerWidth;
		var height	=	w.innerHeight;
	
		return {
			left:		w.pageXOffset,
			top:		w.pageYOffset,
			right:	w.pageXOffset + width,
			bottom:	w.pageYOffset + height,
			width:	width,
			height:	height
		};
	},

	// =====================================================================
	OnResize: function()
	{
		if (!P.emsize)
			return;

		var r						=	P.ViewPortRect();

		P.viewportwidth	=	r.width;
		P.viewportheight	=	r.height;

		P.numems			=	r.width / P.emsize;
		
		if (P.numems < 25)
		{
			P.screensize	=	'small';
		} else if (P.numems < 50)
		{
			P.screensize	=	'medium';
		} else
		{
			P.screensize	=	'large';
		}
	
		$("#ScreenSizeStylesheet").set("@href", P.CSSURL(P.screensize, 'h'));

		if (P.wallpaper)
		{
			$('body').set(
				'$backgroundImage', 
				"url('" + P.ImageURL('wallpapers/' + P.wallpaper + "-" + P.screensize + '.jpg', 'h') + "')");
		}

		if (P.texttheme)
		{
			$('#TextThemeStylesheet').set('@href', P.CSSURL('themes/' + P.texttheme, 'h'));
		}
		
		if (!IsEmpty(P.events.resize))
		{
			var ls	=	P.events.resize;
		
			for (var i = 0, l = ls.length; i < l; i++)
			{
				ls[i]();
			}
		}
	},

	// =====================================================================
	// Converts dotted name and value pairs such as animals.lions.number = 5
	// into animals['lions']['number']	= 5. Useful for form elements when
	// you want to convert them to a JSON array.
	StoreInArray: function(ls, name, value)
	{
		var nameparts	=	name.split('.');
		var l					=	nameparts.length;
		var container	=	ls;

		for (var i = 0; i < l; i++)
		{
			var key	=	nameparts[i];

			if (i != l - 1)
			{
				if (!IsObject(container[key]))
					container[key]	=	{};
			} else
			{
				if (container[key])
				{
					if (!IsArray(container[key]))
						container[key]	=	[];

					container[key].push(value);
				} else
				{
					container[key]	=	value;
				}
			}
			container	=	container[nameparts[i]];
		}

		return ls;
	},

	// =====================================================================
	FormToArray: function(selector)
	{
		var	data	=	{};
		var form	=	$(selector);

		$('input', form).each(
			function(item, index, context)
			{
				var self		=	$(item);
				var name		=	self.get('@name');
				var value		=	self.get('value');
				var type		=	self.get('@type');
			
				if (!name)
					return;

				switch (type)
				{
					case 'number':
						value	=	parseFloat(value);
						break;
					case 'datetime-local':
						value	=	LocalToGMT(value);
						break;
				}

				P.StoreInArray(data, name, value);
			}
		);

		$('textarea', form).each(
			function(item, index, context)
			{
				var self		=	$(item);
				var name		=	self.get('@name');
				var value		=	self.get('value');

				if (!name)
					return;

				P.StoreInArray(data, name, value);
			}
		);

		$('select', form).each(
			function(item, index, context)
			{
				var self		=	$(item);
				var name		=	self.get('@name');
				var value		=	self.get('value');

				if (!name)
					return;

				P.StoreInArray(data, name, value);
			}
		);

		$('.ToggleButton', form).each(
			function(item, index, context)
			{
				var self		=	$(item);
				var name		=	self.get('%name');
				var toggled	=	self.get('%toggled');

				if (name)
					P.StoreInArray(data, name, toggled);
			}
		);

		$('.FormInput', form).each(
			function(item, index, context)
			{
				var self		=	$(item);
				var name		=	self.get('%name');
				var value		=	self.get('%value');

				if (name && value)
					P.StoreInArray(data, name, value);
			}
		);

		return data;
	},

	// =====================================================================
	NumToPath: function(num, numdigits)
	{
		numdigits	=	numdigits || 9;

		var path	=	format('{:0' + numdigits + '}', num);
		var parts	=	[];
		var l			=	path.length - 2;

		for (var i = 0; i < l; i += 3)
		{
			parts.push(path.substr(i, 3));
		}

		return parts.join('/');
	},

	// =====================================================================
	DeleteParent: function(parentselector, selector)
	{
		$(selector).up(parentselector).remove();
	},

	// ====================================================================
	MakeAccordion: function(selector)
	{
		$(selector).each(
			function(item, index)
			{
				var self	=	$(item);
				var next	=	self.next();

				// Only create toggles on high elements that require scrolling
				/*if ($$(next).clientHeight < 600)
					return;*/
			
				next.hide();

				self.set('+HoverHighlight +Accordion');
				self.add(HTML(P.Icon('Arrow', 'h', 'FlipV ToggleIcon', 2.5)));

				next.add(
					HTML(
						'<a class="Button MiniButton Color4" onclick="P.ToggleThis(this)">' 
					+ T[37] + P.Icon('Arrow', 'h', 'ToggleIcon', 2.5) + '</a>'
					)
				);
			}
		);
		
		P.OnClick(selector, LBUTTON, P.OnAccordion);
	},

	// ====================================================================
	OnAccordion: function(e)
	{
		var self	=	P.Target(e);
	
		if (!self.is('.Accordion'))
			self	= self.up('.Accordion');
	
		var next	=	self.next();
		var toggleicon	=	$('.ToggleIcon', self);
		var isvisible		=	toggleicon.is('.FlipV');
	
		if (isvisible)
		{
			next.hide();
			toggleicon.set('-FlipV');
		} else
		{
			next.show();
			toggleicon.set('+FlipV');
		}
	},
	
	// ====================================================================
	SortChildrenByText: function(selector, children1, children2)
	{
		children1	=	children1	||	'';
		children2	=	children2	||	'';

		var	parent		=	$(selector);
		var	children	=	parent.children(children1).get();

		children.sort(
			function(a, b)
			{
				var texta = $(a).find(children2).text().toLowerCase();
				var textb = $(b).find(children2).text().toLowerCase();
				return (texta < textb) ? -1 : (texta > textb) ? 1 : 0;
			}
		);

		parent.empty().append(children);
	},

	// ====================================================================
	MakeToggleButtons: function(selector, tritoggle)
	{
		$(selector).each(
			function(item, index)
			{
				var self	=	$(item);
			
				switch (self.get('%toggled'))
				{
					case 'on':
						self.set('-dredg -lgrayg +lgreeng');
						break;
					case 'off':
						self.set('+dredg -lgrayg -lgreeng');
						break;
					case 'unset':
						self.set('-dredg +lgrayg -lgreeng');
				} 
			}
		);
		
		P.OnClick(
			selector,
			LBUTTON,
			function(e)
			{
				var self			=	P.Target(e);
				var newstate	= 'on';
			
				switch (self.get('%toggled'))
				{
					case 'on':
						if (tritoggle)
						{
							newstate	= 'off';
						} else
						{
							newstate	= 'unset';
						}
						break;
					case 'off':
						newstate	= 'unset';
						break;
					default:
						newstate	= 'on';
				};
			
				if (newstate == 'on')
				{
					self.set('-dredg -lgrayg +lgreeng');
				} else if (newstate == 'off')
				{
					self.set('+dredg -lgrayg -lgreeng');
				} else
				{
					self.set('-dredg +lgrayg -lgreeng');
				}
				
				var ontoggle	= self.get('%ontoggle');
			
				if (ontoggle)
					window[ontoggle](e, self, newvalue);
			}
		);
	},

	// ====================================================================
	ToggledButtons: function(selector)
	{
		$(selector).each(
			function(item, index, context)
			{
				var self	=	$(item);

				ls[self.get('%name')]	= self.get('%toggled');
			}
		);

		return ls;
	},

	// ====================================================================
	// Creates a group of radio buttons.
	// 
	// Buttons should be an array in the format
	// [title, value, selected, [classes]]
	//
	// When more than eight buttons are used, this will automatically turn
	// into a select control instead.
	//
	// Any changes will result in the new value being sent to the onchange 
	// function.
	MakeRadioButtons: function(selector, buttons, onchange)
	{
		var el	= $(selector);
		var ls	= [];
		var l		= buttons.length;
	
		if (l > 8)
			ls.push('<select>');
	
		for (var i = 0, l = buttons.length; i < l; i++)
		{
			var item	= buttons[i];
			
			if (l > 8)
			{
				ls.push('<option value="' + EncodeEntities(item[1]) + '" ' + (item[2] ? 'selected' : '') + '>' + item[0] + '</option>');
			} else
			{
				ls.addtext([
					'<div class="RadioButton HoverHighlight Button' + i + (item[3] ? ' ' + item[3] : '') + (item[2] ? ' lgreeng' : ' lgrayg') + '" data-value="' + EncodeEntities(item[1]) + '" data-state="' + (item[2] ? 'on' : 'unset') + '">',
						'<img class=LineHeight src="' + P.ImageURL('icons/radio.png', 'h') + '" />',
						item[0],
					'</div>'
				]);
			}
		}
	
		if (l > 8)
		{
			ls.push('</select>');
		} else
		{	
			el.set('+RadioGroup');
		}
	
		el.ht(ls.join('\n'));
	
		if (l <= 8)
		{
			var children	= $(selector + ' .RadioButton');
			$(children[0]).set('$borderRadius', '1em 0em 0em 1em');
			$(children[children.length - 1]).set('$borderRadius', '0em 1em 1em 0em');
	
			P.OnClick(
				selector + ' .RadioButton',
				LBUTTON,
				function(e)
				{
					var self		=	P.Target(e);
				
					if (!self.is('.RadioButton'))
						self	= self.up('.RadioButton');
				
					var value		=	self.get('%value');
					var parent	=	self.up();
				
					parent.select('div', true).each(
						function(item)
						{
							var self2	=	$(item);
							
							if (self2.get('%value') == value)
							{
								self2.set('-lgrayg +lgreeng').set('%state', 'on');
							} else
							{
								self2.set('-lgreeng +lgrayg').set('%state', 'unset');
							}
						}
					);
					
					if (onchange)
						onchange(value);
				}
			);
		} else
		{
			if (onchange)
			{
				$(selector + ' select').on(
					'change',
					function(e)
					{
						onchange(P.Selected(P.Target(e)).value);
					}
				);
			}
		}
	},

	// =====================================================================
	Set: function(vars)
	{
		P.API(
			'h/sessions/set',
			{
				values:	vars
			}
		);
	},
	
	// ====================================================================
	AJAX: function(url, data, settings)
	{
		var defaults	=	{
			method:		'get',
			timeout:	15,
		};

		settings	=	Merge(defaults, settings || {});

		if (settings.method == 'get' && data)
			url	+=	'?' + ToParams(data);

		var p = _.promise();

		var r	=	new XMLHttpRequest();

		r.open(settings.method, url);

		// Convert to seconds
		r.timeout	=	settings.timeout * 1000;

		r.addEventListener("load", function() { p.fire(true, [r, 'loaded']) });
		r.addEventListener("error", function() { p.fire(false, [r, 'error']); });
		r.addEventListener("abort", function() { p.fire(false, [r, 'aborted']); });
		r.addEventListener("timeout", function() { p.fire(false, [r, 'timeout']); });

		if (settings.method == 'post' && data)
		{
			r.send(data);
		} else
		{
			r.send();
		}

		return p;
	},

	// =====================================================================
	API: function(command, data, onsuccess, options)
	{
		var defaults	=	{
			method: 			'post',
			timeout:			10,
			handleerror:	1
		};
	
		options	=	Merge(defaults, options);
	
		P.AJAX(
			'/a/' + command,
			JSON.stringify(data),
			options
		).then(
			function(r)
			{
				var data	= 0;
			
				try
				{
					var data	=	$.parseJSON(r.response);
				} catch (e)
				{
				}

				if (!data)
				{
					P.Debug('P.API: No JSON returned! ' + r.statusText + ': ' + r.response);
					throw new Error('P.API: No JSON returned! ' + r.statusText + ': ' + r.response);
				} else if (data.error && options.handleerror)
				{
					P.Debug('P.API: ' + data.error);
					throw new Error('P.API: ' + data.error);
				} else
				{
					if (onsuccess)
						onsuccess(data);
				}
			},
			function(r)
			{
				throw new Error('P.API: ' + r.statusText + ': ' + r.response);
			}
		);
	},

	// =====================================================================
	LoadingAPI: function(resultsdiv, command, data, func, options)
	{
		var defaults	=	{
			handleerror:	0
		};
	
		options	=	Merge(defaults, options);
	
		resultsdiv	=	$(resultsdiv);
		P.Loading(resultsdiv);

		this.API(
			command,
			data,
			function(d)
			{
				if (d.error)
				{
					resultsdiv.ht('<div class=Error>' + d.error + '</div>');
				} else
				{
					func(d, resultsdiv);
				}
			},
			options
		);
	},

	// =====================================================================
	LoadingDialog: function()
	{
		return P.Popup(
			HTML(['<div class="LoadingResults Padded FullWidth Centered">',
				'<img src="/i/h/loading.gif" style="width: 96%"></div>'
			].join("\n"))
		);
	},

	// =====================================================================
	DialogAPI: function(command, data, func, options)
	{
		var defaults	=	{
			handleerror:	0
		};
	
		options	=	Merge(defaults, options);
	
		var popupclass	=	P.LoadingDialog();

		this.API(
			command,
			data,
			function(d)
			{
				if (d.error)
				{
					$('.LoadingResults').ht([
						'<div class="whiteb Rounded Pad50">',
							'<div class=Error>' + d.error + '</div>',
							'<br>',
							'<div><a class="Button Color1 Width93" onclick="P.CloseThisPopup(this)">' + T[37] + '</a></div>',
						'</div>'
					].join('\n'));
				} else
				{
					P.ClosePopup(popupclass);
					func(d);
				}
			},
			options
		);
	},

	// ====================================================================
	SetWallpaper: function(filename, nocheck)
	{
		var parts	=	filename.split('-');
	
		this.wallpaper	=	parts[0];
		this.texttheme	=	parts[1].replace('.jpg', '');

		this.OnResize();

		if (!nocheck)
			this.API('wallpapers/set', {'filename': parts[0]}, function(){});
	},

	// ====================================================================
	SetLanguage: function(langcode)
	{
		this.API('translations/setlang', 
			{'langcode': langcode}, 
			function()
			{
				window.location.reload(1);
			}
		);
	},

	// ====================================================================
	EvalInlineScripts: function(contentdiv)
	{
		$('script', contentdiv).each(
			function(item, index, context)
			{
				var src	=	$(item).get('@src');

				if (!src)
					eval($(this).text());
			}
		);
	},

	// ====================================================================
	LoadURL: function(url, contentonly, options)
	{
		options			=	options || {};
	
		title				=	options.title				|| '';
		content			=	options.content			|| '';
		contentfunc	=	options.contentfunc	|| '';
		data				=	options.data				|| {};
		contentdiv	=	options.contentdiv	|| '#Content';
	
		// Avoid multiple page loads on a single click event
		if (url == P.currenturl)
			return;
	
		P.currenturl	=	url;
		
		if (!contentonly || !P.useadvanced)
		{
			P.LoadingDialog();
		
			window.location	=	url;
		} else
		{
			if (content || contentfunc)
			{
				history.pushState(
					{
						title: 				title,
						content:			content,
						contentdiv:		contentdiv,
						contentfunc:	contentfunc,
						data:					data
					},
					title,
					url
				);

				if (contentfunc)
				{
					window[contentfunc](data);
					P.dontrestorecontent.push(1);
				}
			} else
			{
				contentdiv	=	$(contentdiv);

				P.Loading(contentdiv);

				P.AJAX(
					url,
					{contentonly:	1},
					{timeout:	10000}
				).then(
					function(r)
					{
						try
						{
							d	=	$.parseJSON(r.response);
						} catch (e)
						{
							d.error	= e;
						}

						if (!d)
						{
							contentdiv.ht('<div class=Error>Problem getting content... Please try again</div>');
						} else if (d.error)
						{
							contentdiv.ht('<div class=Error>' + d.error + '</div>');
						} else
						{
							contentdiv[0].innerHTML	=	d.content;
						
							if (title)
								document.title	= title;

							$('script', contentdiv).each(
								function(item, index, context)
								{
									var src	=	$(item).get('@src');

									if (src)
									{
										_loader.Load(src);
									}
								}
							);

							_loader.OnFinished(
								function()
								{
									P.EvalInlineScripts(contentdiv);
								}
							);

							history.pushState(d, d.title, url);
							window.scrollTo(0, 0);
						}
					},
					function(r, reason)
					{
						contentdiv.ht(
							'<div class=Error>' + r.reason + '<br>' + r.responseText + '</div>'
						);
					}
				);
			}
		}
	},

	// ====================================================================
	OnPopState: function(e)
	{
		if (P.CloseTopLayer())
			return;
	
		if (e.state)
		{
			if (e.state.contentfunc)
			{
				window[e.state.contentfunc](e.state.data);
			} else if (!P.dontrestorecontent.pop())
			{
				var contentdiv	=	$(e.state.contentdiv);
				contentdiv.ht(e.state.content);
				$('script', contentdiv).each(
					function()
					{
						eval($(this).text());
					}
				);
			}
		} else if (!P.dontrestorecontent.pop())
		{
			window.location.reload(1);
		}
	},

	// ====================================================================
	PageBar: function(count, start, limit, numonpage, listfunc)
	{
		count			=	parseInt(count);
		start			=	parseInt(start);
		numonpage	=	parseInt(numonpage);
		listfunc	=	listfunc || 'ListObjects';
		limit			=	parseInt(limit) || 100;
		
		numpages	=	Math.ceil(count / limit);

		var	ls	=	[
			'<div class=Cleared></div>',
			'<div class=Pad50>',
				(count ? (start + 1) : 0) + ' - ' + (start + numonpage - 1) + ' / ~' + count,
			'</div>',
			'<div class="ButtonBar PageBar">'
		];

		if (count > limit)
		{
			if (start)
			{
				ls.push('<a class="FirstPage" onclick="' + listfunc + '()">&lt;&lt;</a>');

				if (start >= limit)
					ls.push('<a class="PreviousPage" onclick="' + listfunc + '(' + (start - limit) + ')">&lt;</a>');
			}

			var currentpage	=	Math.floor(start / limit);
			var startpage		=	0;
			var endpage			=	numpages;

			if (numpages > 16)
			{
				startpage	=	currentpage > 8 ? currentpage - 8 : 0;
				endpage		=	currentpage + 8 < numpages ? currentpage + 8 : numpages;
			}

			for (var i = startpage; i < endpage; i++)
			{
				var startnum	=	i * limit;

				if (startnum != start)
					ls.push('<a onclick="' + listfunc + '(' + startnum + ')">' + startnum + '</a>');
			}

			if (start + limit < count)
			{
				if (start + limit < count - limit)
					ls.push('<a class="NextPage" onclick="' + listfunc + '(' + (start + limit) + ')">&gt;</a>');

				ls.push('<a class="LastPage" onclick="' + listfunc + '(' + (Math.ceil((count - limit) / limit) * limit) + ')">&gt;&gt;</a>');
			}
		}

		if (numpages > 16)
			ls.push('<a class=ChoosePage onclick="P.ChoosePage(' + start + ', ' + limit + ', ' + count + ', \'' + listfunc + '\')">' + T[34] + '</a>');

		ls.push('</div>');
		ls.push('<div class=Cleared></div>');

		return ls;
	},

	// ====================================================================
	ChoosePage: function(start, limit, count, listfunc)
	{
		var currentpage	=	Math.floor(start / limit) + 1;
		var numpages		=	Math.ceil(count / limit);
	
		P.EditPopup(
			[
				['pagenum', 'int', currentpage, T[35] + ' (1-' + numpages + ')', '', 'min=1 max=' + numpages],
			],
			function()
			{
				P.GotoPage(limit, listfunc);
			}
		);
	
		P.OnEnter(
			'.pagenum',
			function()
			{
				P.GotoPage(limit, listfunc);
			}
		);

		setTimeout(
			function()
			{
				var e	=	$$('.pagenum');
				e.focus();
				e.select();
			},
			500
		);
	},

	// ====================================================================
	GotoPage: function(limit, listfunc)
	{
		var start	=	limit * (parseInt($$('.pagenum').value) - 1);
		P.ClosePopup();
		window[listfunc](start, limit);
	},

	// ====================================================================
	OnPauseButton: function(url)
	{
		var s	= P.media[url];
		
		if (s)
		{
			if (s.playing())
			{
				$('.PlayBar .PauseButton').set('@src', P.ImageURL('icons/pause.png', 'h'));
				s.pause();
			} else
			{
				$('.PlayBar .PauseButton').set('@src', P.ImageURL('icons/play.png', 'h'));
				s.play(P.currentmediaid);
			}
		}
	},

	// ====================================================================
	OnPlayTimer: function(url)
	{
		if (P.media[url] && P.media[url].playing())
		{
			var pos	= P.media[url].seek();
		
			$('.PlayBar .CurrentPos').fill(SecondsToTime(pos, 1));
			$('.PlayBar .Position').set('value', pos);
		
			setTimeout(
				function()
				{
					P.OnPlayTimer(url);
				},
				300
			);
		}
	},
	
	// ====================================================================
	MakePlayButtons: function(selector)
	{
		$(selector).each(
			function(item, index)
			{
				$(item).set('+HoverHighlight').ht('<img class=DoubleLineHeight src="' + P.ImageURL('icons/play.png', 'h') + '"/>');
			}
		);
		
		P.OnClick(
			selector,
			LBUTTON,
			function(e)
			{
				var el	= P.Target(e);
				var url	= el.get('%url');
				
				var img	= $('img', el, 1);
				var src	= img.get('@src');
			
				img.set('@src', P.ImageURL('busy.svg', 'h'));
			
				P.Popup(HTML([
						'<div class="whiteb Pad50 Raised Rounded PlayBar">',
							'<img src="' + P.ImageURL('loading.gif', 'h') + '"/>',
						'</div>'
					].join('\n')),
					{
						closeonmouseout:	1,
						parent:						el
					}
				)
				
				P.Play(
					url, 
					url, 
					{
						autoplay:		1,
						onplay: function()
							{
								var s	= P.media[url];
								var d	= s.duration();
							
								P.HTML(
									'.PlayBar',
									[
										'<img class="DoubleLineHeight PauseButton HoverHighlight" src="' + P.ImageURL('icons/play.png', 'h') + '"/>',
										'<span class=CurrentPos>' + SecondsToTime(0, 1) + '</span> / <span class=Duration>' + SecondsToTime(d, 1) + '</span>',
										'<meter class="Position Width100" min=0 max=' + d.toFixed(0) + '></meter>'
									]
								);
							
								P.OnClick(
									'.PlayBar .PauseButton', 
									LBUTTON, 
									function()
									{
										P.OnPauseButton(url);
									}
								);
							
								P.OnClick(
									'.PlayBar .Position',
									LBUTTON,
									function(e)
									{
										var self	=	P.Target(e);
									
										var min		=	parseInt(self.get('@min'));
										var max		=	parseInt(self.get('@max'));
										var value	=	self[0].value;
									
										var rt	=	P.AbsoluteRect(self);
									
										var newvalue	=	Math.round(((P.mousex - rt.left) / rt.width) * (max - min)) + min;
										
										if (value == newvalue)
											return;
										
										self[0].value	=	newvalue;
									
										if (P.media[url])
											P.media[url].seek(newvalue);
									}
								);
		
								img.set('@src', src);
							
								setTimeout(
									function()
									{
										P.OnPlayTimer(url);
									},
									300
								);
							},
						onloaderror: function(id, msg)
							{
								$('.PlayBar').ht('<div class=Error>' + msg + '</div>');
								img.set('@src', src);
							}
					}
				);
			}
		);
	},
	
	// ====================================================================
	Play: function(id, url, options)
	{
		options	= options	|| {};
		options['src']	= [url];
		
		if (!P.media[id])
		{
			P.mediatoload.push(url);
			
			var prevonload	= options['onload'];
		
			options['onload']	= function()
			{
				P.mediatoload.remove(url);
			
				if (!P.mediatoload.length)
				{
					for (var i = 0, l = P.onmedialoaded.length; i < l; i++)
					{
						P.onmedialoaded[i]();
					}
				
					P.onmedialoaded	= [];
				}
			
				if (prevonload)
					prevonload();
			};
		
			P.media[id]	= new Howl(options);
		} else
		{
			if (options['autoplay'] != 0)
				P.currentmediaid	= P.media[id].play();
		}
	},

	// ====================================================================
	OnMediaLoaded: function(func)
	{
		P.onmedialoaded.push(func);
	},

	// ====================================================================
	AddLayer: function(selector)
	{
		P.layerstack.push(selector);
		//history.pushState(null, '', '#' + selector);	
	},

	// ====================================================================
	RemoveLayer: function(selector)
	{
		var index	=	P.layerstack.indexOf(selector);
	
		if (index > -1)
		{
			P.layerstack.remove(index);
			//history.back();
		}
	},

	// ====================================================================
	CloseTopLayer: function()
	{
		// Close popup windows and fullscreens before going to previous page
		if (P.layerstack.length)
		{
			for (;;)
			{
				if (!P.layerstack.length)
					break;
			
				var selector	=	P.layerstack.pop();
				var el				= $(selector);
			
				if (!el.length)
					break;
			
				if (selector.indexOf('Popup') > -1)
				{
					$(selector).remove();
				} else
				{
					$(selector).ht('').hide();
				}
				window.history.back();
				return 1;
			}
		}
	
		return 0;
	},

	// ====================================================================
	EnterFullScreen: function(num, classes, contentlist)
	{
		num	=	parseInt(num);
		num	=	num	|| '';
		P.AddLayer('.FullScreen' + num);
		var el	=	$('.FullScreen' + num);
	
		if (classes)
			el.set(classes);
	
		el.ht(contentlist.join('\n'))
			.set('$left', '-' + window.outerWidth + 'px')
			.show()
			.animate({$left: '0px'}, 300);
	},

	// ====================================================================
	ExitFullScreen: function(num)
	{
		num	=	parseInt(num);
		num	=	num	|| '';
		var selector	=	'FullScreen' + num;
		P.RemoveLayer('.' + selector);
		// Clear all previous classes
		var el	=	$('.' + selector);
	
		el.animate({'$left': '-' + window.outerWidth + 'px'}, 300);
		
		setTimeout(
			function()
			{
				el.set('@class', selector).ht('').hide();
			},
			500
		);
	},

	// ====================================================================
	ShowDrawer: function(side, size, classes, contentlist)
	{
		side	= side || 'Bottom';
		size	= size || '2em';
	
		var el	=	$('.' + side + 'Drawer');
	
		if (classes)
			el.set(classes);
	
		var prop	= (side == 'left' || side == 'right') ? '$width' : '$height';
	
		var ani		= {};
		ani[prop]	=	size;
	
		el.ht(contentlist.join('\n'))
			.set(prop, '0px')
			.show()
			.animate(ani, 300);
	},

	// ====================================================================
	HideDrawer: function(side)
	{
		var el	=	$('.' + side + 'Drawer');
	
		el.set('@class', 'Drawer ' + side + 'Drawer');
	
		var prop	= (side == 'left' || side == 'right') ? '$width' : '$height';
	
		var ani		= {};
		ani[prop]	=	'0px';
		
		el.animate(ani, 300);
	},
	
	// ====================================================================
	ReloadPage: function()
	{
		window.location.reload(1);
	},

	// ====================================================================
	NoPaste: function(selector)
	{
		$(selector).on(
			'copy paste drag drop',
			function(e)
			{
				e.preventDefault();
			}
		);
	},

	// ====================================================================
	CaptureMousePos: function(e)
	{
		P.mousex = e.changedTouches ? e.changedTouches[0].pageX : e.pageX;
		P.mousey = e.changedTouches ? e.changedTouches[0].pageY : e.pageY;
		
		P.istouch	=	(e.type.indexOf('touch') != -1);
	},

	// ====================================================================
	MousePos: function()
	{
		return [P.mousex, P.mousey];
	},

	// ====================================================================
	FlexInput: function(selector)
	{
		selector	= selector	|| '.FlexInput';
	
		$(selector).each(
			function(item, index)
			{
				var self	=	$(item);
				var name	=	self.get('%name');
				var value	=	self.get('%value');
			
				if (!name)
				{
					self.ht('<div class=Error>No name provided</div>');
					return;
				}

				P.HTML(
					self,
					[
						'<pre><span></span><br></pre>',
						'<textarea class="' + name + '" name="' + name + '"></textarea>'
					]
				);

				var area = $('textarea', self);
				var span = $('span', self);

				area.on(
					'input',
					function()
					{
						span.fill(area.get('value'));
					}
				);
				area.fill(value);
				span.fill(value);
				self.set('+active');
			}
		);
	},

	// ====================================================================
	// Thanks to http://techpatterns.com/downloads/javascript_cookies.php
	// ====================================================================
	MakeCalendar: function(selector, month, switchmonthfunc, dayclickfunc)
	{
		var d		=	month ? new Date(month) : new Date();

		if (isNaN(d.getTime()))
		{
			$(selector).ht('<div class=Error>Invalid date: ' + month + '</div>');
			return;
		}

		var currentdate		=	d.getDate();
		var currentday		=	d.getDay();
		var currentyear		=	d.getFullYear();
		var currentmonth	=	d.getMonth();
		var monthtostop		=	currentmonth == 11 ? 0 : currentmonth + 1;

		var nextmonth	=	(currentmonth == 11)
			? (currentyear + 1) + '-01'
			: currentyear + '-' + (currentmonth < 8 ? '0' + (currentmonth + 2) : (currentmonth + 2));

		var previousmonth	=	(currentmonth == 0)
			? (currentyear - 1) + '-12'
			: currentyear + '-' + (currentmonth < 10 ? '0' + currentmonth : currentmonth);

		var	ls	=	['<table class="Styled Calendar Width100 Center">',
			'<tr>',
				switchmonthfunc ? '<td class="Color1 PreviousMonthButton">&lt;&lt;</td>' : '<th></th>',
				'<td colspan=5 class=ThisMonth>' + currentyear + '-' + (currentmonth < 9 ? '0' + (currentmonth + 1) : (currentmonth + 1)) + '</td>',
				switchmonthfunc ? '<td class="Color2 NextMonthButton">&gt;&gt;</td>' : '<th></th>',
			'</tr>',
			'<tr>',
				'<th>' + T_CALENDAR[9] + '</th>',
				'<th>' + T_CALENDAR[10] + '</th>',
				'<th>' + T_CALENDAR[11] + '</th>',
				'<th>' + T_CALENDAR[12] + '</th>',
				'<th>' + T_CALENDAR[13] + '</th>',
				'<th>' + T_CALENDAR[14] + '</th>',
				'<th>' + T_CALENDAR[15] + '</th>',
			'</tr>'
		];

		// Set date to starting square of calendar
		d.setDate(1);
		d.setDate(d.getDate() - d.getDay() + 1);

		var i		=	0;
		
		var now				=	new Date();
		var thismonth	=	now.getMonth();
		var thisday		=	now.getDate();

		for (;;)
		{
			ls.push('<tr>');

			for (var i = 0; i < 7; i++)
			{
				var loopdate	=	d.getDate();
				var loopmonth	=	d.getMonth();
				var zerodate	= (loopdate < 10 ? '0' + loopdate : loopdate);
				ls.addtext(['<td class="'
					+ ((loopmonth == thismonth && loopdate == thisday) ? 'Today ' : '')
					+ (loopmonth == currentmonth
						? 'Day Day' + zerodate + '" data-date="' + zerodate
						: 'OtherMonth')
					+ '">' + loopdate + '</td>'
				]);
				d.setDate(loopdate + 1);
			}

			ls.push('</tr>');

			if (d.getMonth() == monthtostop || i > 60)
				break;

			i++;
		}

		ls.push('</table>');

		P.HTML(selector, ls);

		if (switchmonthfunc)
		{
			P.OnClick(
				selector + ' .NextMonthButton', 
				LBUTTON,
				function() {switchmonthfunc(selector, nextmonth); }
			);
			
			P.OnClick(
				selector + ' .PreviousMonthButton', 
				LBUTTON,
				function() {switchmonthfunc(selector, previousmonth); }
			);
		}

		if (dayclickfunc)
			P.OnClick(
				selector + ' .Calendar', 
				LBUTTON, 
				function(e)
				{
					var el	= P.Target(e);
				
					if (!el.is('.Day'))
						el	= el.up('.Day');
				
					dayclickfunc(e, el, el.get('%date'))
				}
			);
	},

	// ====================================================================
	OnEnter: function(selector, func)
	{
		$(selector).on(
			'?keypress',
			function(e)
			{
				if (e.keyCode == 13)
				{
					func(e);
					return false;
				}
			
				return true;
			}
		);
	},

	// ====================================================================
	MiniLogin: function()
	{
		P.DialogAPI(
			'login/login',
			{
				phonenumber:	$('.MiniLogin input.phonenumber').sub(1).get('value'),
				place:				$('.MiniLogin input.place').sub(1).get('value'),
				password:			$('.MiniLogin input.password').sub(1).get('value'),
			},
			function(results)
			{
				window.location	=	document.location;
			}
		);
	},

	// ====================================================================
	PopupMiniLogin: function()
	{
		$('.MiniLogin .phonenumber')[1].focus();

		P.OnEnter('.MiniLogin', P.MiniLogin);

		P.OnClick('.MiniLoginButton', LBUTTON, P.MiniLogin);
	},

	// ====================================================================
	PreloadImage: function(url)
	{
		if (IsArray(url))
		{
			for (var i = 0, l = url.length; i < l; i++)
				P.Preload(url[i])

			return;
		}

		for (var k in P.preloads)
		{
			if (k == url)
				return P.preloads[k];
		}

		var img	=	new Image();
		img.src	=	url;
		P.preloads[url]	=	img;
		return img;
	},

	// ====================================================================
	SlideShow: function(selector, slides, options)
	{
		var defaultoptions = {
			nextimage:		'random',
			timeout:			4000,
			imgfraction:	0.7
		};

		Merge(defaultoptions, options);

		var el	=	$(selector);
		var e		=	el[0];

		e.slides			=	slides;
		e.slidepos		=	0;
		e.imgfraction	=	defaultoptions.imgfraction
		e.nextimage		=	defaultoptions.nextimage;
		e.timeout			=	defaultoptions.timeout;
		e.selector		=	selector;

		el.set('+SlideShow');

		P.NextSlide(e);
	},

	// ====================================================================
	NextSlide: function(element)
	{
		var screenw	=	P.ViewPortRect().width;

		$(element.selector + ' .Slide').set('$transform', 'translate(-' + (screenw * 1.2) + 'px, 0)');

		setTimeout(
			function()
			{
				var slidepos	=	-1;

				if (element.nextimage == 'random')
				{
					do
					{
						slidepos	=	RandInt(0, element.slides.length);
					} while (slidepos == element.slidepos);
				} else
				{
					slidepos	=	element.slidepos + 1;

					if (slidepos >= element.urls.length)
						slidepos	=	0;
				}

				element.slidepos	=	slidepos;
				element.innerHTML	=	'<div class=Slide style="position: relative; left: ' + (screenw * 0.5) + 'px;">' + element.slides[element.slidepos] + '</div>';
				$(element.selector + ' img').set('$height', $$('.SlideShow').getBoundingClientRect().height * element.imgfraction + 'px');
				$(element.selector + ' img').set('$width', 'auto');
				$(element.selector + ' .Slide').set('$transform', 'translate(-' + (screenw * 0.5) + 'px, 0)');

				setTimeout(
					function()
					{
						P.NextSlide(element);
					},
					element.timeout
				);
			},
			1000
		);
	},

	// ====================================================================
	MakeTabs: function(selector, tabs)
	{
		selector	=	$(selector);
		selector.set('+TabBar');

		var ls	=	[];

		for (var i = 0, l = tabs.length; i < l; i++)
		{
			var item	=	tabs[i];
			var selected	=	window.location.href.match(item[1]) ? 'class=Selected' : '';
			ls.push('<a data-regex="' + EncodeEntities(item[1]) + '" href="' + EncodeEntities(item[2]) + '" ' + selected + '>' + item[0] + '</a>');
		}

		ls.push('<div class=Cleared></div>');

		P.HTML(selector, ls);
	},

	// ====================================================================
	MakeEditTags: function(selector, taglist, savefunc)
	{
		var editdiv	=	$(selector);
		editdiv.set('+EditTags');
		var self	=	editdiv[0];

		self.origtaglist			=	taglist.slice();

		var ls	=	['<p class=EditTagsList>',
				'</p>',
				'<div class=ButtonBar>',
					'<a class="Color1 AddTagButton">+</a>',
					'<a class="Color2 ResetTagsButton">' + T[39] + '</a>',
					'<a class="Color3 SaveTagsButton">' + T[25] + '</a>',
				'</div>',
				'<div class=SaveTagsResults></div>'
		];

		P.HTML(editdiv, ls);

		P.MakeEditTagsContent(selector, taglist);

		P.OnClick('.EditTags .Tag', LBUTTON, function() { $(this).remove(); });

		$('.AddTagButton').on(
			'click',
			function()
			{
				var self	=	$(this);
				P.EditPopup(
					[['newtag', 'text', T[54]]],
					function()
					{
						var text	=	$('.newtag').get('value');

						if (!taglist.contains(text))
						{
							taglist.push(text);
							P.MakeEditTagsContent(selector, taglist);
						}

						return true;
					},
					{
						parent:	self
					}
				);
			}
		);

		$('.ResetTagsButton').on(
			'click',
			function()
			{
				P.MakeEditTagsContent(selector, self.origtaglist);
			}
		);

		P.OnClick('.SaveTagButton', LBUTTON, function() { onsavefunc(selector) });
	},

	// ====================================================================
	MakeEditTagsContent: function(selector, taglist)
	{
		taglist.sort();

		var ls	=	[];

		for (var i = 0, l = taglist.length; i < l; i++)
		{
			ls.push('<a class=Tag><img src="/i/h/svg/tag.svg">' + taglist[i] + '</a>');
		}

		P.HTML(selector + ' .EditTagsList', ls);
	},

	// ====================================================================
	InterceptHREF: function()
	{
		$('body').on(
			'?click',
			function(e)
			{
				var target	=	P.Target(e);
			
				if (target[0].tagName != 'A')
					target	=	target.up('a');
			
				if (!target.length)
					return 1;
			
				var href =	target.get('@href');
			
				// Don't intercept external links, but do show a loading screen
				if (P.baseurl != '/' && !href.indexOf(P.baseurl))
				{
					P.LoadingDialog();
					return 1;
				}
				
				if (!target.get('%nointercept') && href && href.indexOf('http') != -1)
				{
					P.LoadURL(href, 1);
					e.preventDefault();
					e.stopPropagation();
					return 0;
				}
				return 1;
			}
		);
	},

	// ====================================================================
	ListObjects: function(options)
	{
		start		=	options.start || 0;
		limit		= options.limit || 100;
	
		data	=	Merge(
			{
				start:		start,
				limit:		limit,
				orderby:	options.orderby
			},
			options.data
		);

		P.LoadingAPI(
			options.selector,
			options.api,
			data,
			function(d, resultsdiv)
			{
				var items	=	d.items;
				
				if (!items.length)
				{
					resultsdiv.ht('No items found');
					return;
				}
				
				var ls	=	[];
				
				for (var i = 0, l = items.length; i < l; i++)
				{
					ls.addtext(options.itemfunc(i, items[i]));
				}
				
				ls.addtext(
					P.PageBar(
						d.count, 
						start, 
						limit,
						options.orderby,
						items.length,
						options.listfunc
					)
				);
				P.HTML(resultsdiv, ls);
				P.AutoWidth();
			}
		);
	},

	// ====================================================================
	RotateImage: function(selector, deg)
	{
	},
	
	// ====================================================================
	RotateImage: function(selector, deg)
	{
		var img		=	$(selector);
		var angle	=	parseInt(img.get('%angle'));

		if (!angle)
			angle	=	0;
		
		angle	+=	deg;

		if (angle < 0)
			angle	+=	360;

		if (angle >= 360)
			angle	-=	360;

		img.set({
			'%angle': 		angle,
			'$transform': 'rotate(' + angle + 'deg)'
		});
	},

	// ====================================================================
	// Thanks to http://stackoverflow.com/questions/6157929/how-to-simulate-a-mouse-click-using-javascript
	Emit: function(element, eventName, options)
	{
		options = Merge(
			{
				pointerX:		0,
				pointerY:		0,
				button:			0,
				ctrlKey:		false,
				altKey:			false,
				shiftKey:		false,
				metaKey:		false,
				bubbles:		true,
				cancelable:	true
			}, 
			options || {}
		);
		var oEvent, eventType = null;

		for (var name in {
			'HTMLEvents': /^(?:load|unload|abort|error|select|change|submit|reset|focus|blur|resize|scroll)$/,
			'MouseEvents': /^(?:click|dblclick|mouse(?:down|up|over|move|out))$/
			}
		)
		{
				if (eventMatchers[name].test(eventName)) { eventType = name; break; }
		}

		if (!eventType)
				throw new SyntaxError('Only HTMLEvents and MouseEvents interfaces are supported');

		if (document.createEvent)
		{
			oEvent = document.createEvent(eventType);
			
			if (eventType == 'HTMLEvents')
			{
				oEvent.initEvent(eventName, options.bubbles, options.cancelable);
			}
			else
			{
				oEvent.initMouseEvent(
					eventName, 
					options.bubbles, 
					options.cancelable, 
					document.defaultView,
					options.button, 
					options.pointerX, 
					options.pointerY, 
					options.pointerX, 
					options.pointerY,
					options.ctrlKey, 
					options.altKey, 
					options.shiftKey, 
					options.metaKey, 
					options.button, 
					element
				);
			}
			element.dispatchEvent(oEvent);
		}
		else
		{
			options.clientX = options.pointerX;
			options.clientY = options.pointerY;
			var evt = document.createEventObject();
			oEvent = extend(evt, options);
			element.fireEvent('on' + eventName, oEvent);
		}
		return element;
	},

	// ====================================================================
	SetWallpaperPopup: function()
	{
		var ls	=	['<div class="Wallpapers">'];
	
		for (var i = 0, l = availablewallpapers.length; i < l; i++)
		{
			var item	=	availablewallpapers[i];
		
			ls.push('<img class=HoverHighlight src="' + P.ImageURL('wallpapers/' + item, 'h') 
				+ '" onclick="P.SetWallpaper(\'' + item + '\')">'
			);
		}
	
		ls.push('</div>');
	
		P.Popup(
			HTML(ls.join('\n')),
			{
				closeonmouseout:	1,
				parent:						'.WallpapersMenu',
				'$width':						'10em'
			}
		);
	},

	// ====================================================================
	ChangeLanguagePopup: function()
	{
		var ls	=	['<div class="Languages">'];
		var color	=	1;
	
		for (var i = 0, l = availablelanguages.length; i < l; i++)
		{
			var item	=	availablelanguages[i];
		
			ls.push('<a class="Button Color' + color + '" onclick="P.SetLanguage(\'' + item[0] + '\')">' + item[1] + '</a>'
			);
		
			color++;
		
			if (color > 6)
				color	=	1;
		}
	
		ls.push('</div>');
	
		P.Popup(
			HTML(ls.join('\n')),
			{
				closeonmouseout:	1,
				parent:						'.LanguagesMenu',
				'$width':						'10em'
			}
		);
	},

	// ====================================================================
	UserMenuPopup: function()
	{
		var ls	=	['<div>'];
		var color	=	1;
	
		if (P.userid)
		{
			ls.push('<a class="Button Color1" href="/logout">' + T[27] + '</a>');
		} else
		{
			P.EditPopup(
				[
					['phonenumber', 'text', '', T[42]],
					['place', 'text', '', T[43]],
					['password', 'password', '', T[17]],
				],
				P.MiniLogin,
				{
					parent:						'.UserMenu',
					closeonmouseout:	1,
					gobuttontext:			T[47], 
					cancelbuttontext:	T[1],
					cancelfunc:				function()
						{
							P.CloseThisPopup('.password');
						}
				}
			);
			
			P.OnEnter(
				'.EditPopupForm input', 
				function()
				{
					P.MiniLogin($('.EditPopup'), P.FormToArray('.EditPopupForm'));
				}
			);
			
			setTimeout(
				function()
				{
					var el	=	$$('.EditPopup .phonenumber');
				
					if (el)
						el.focus();
				},
				200
			);
			return;
		}
	
		ls.push('</div>');
	
		P.Popup(
			HTML(ls.join('\n')),
			{
				closeonmouseout:	1,
				parent:						'.UserMenu',
				'$width':						'10em'
			}
		);
	},

	// ====================================================================
	MiniLogin: function(el, fields)
	{
		if (!fields.phonenumber
			|| !fields.place
			|| !fields.password
		)
		{
			P.ErrorPopup('<?=$T_SYSTEM[48]?>');
			return;
		}
		
		P.LoadingAPI(
			'.EditPopupResults',
			'login/login',
			fields,
			function(d, resultsdiv)
			{
				window.location.reload(1);
			}
		);
	},
			
	// ====================================================================
	AdminMenuPopup: function()
	{
		var ls	=	['<div>'];
		var color	=	1;
		
		adminpages.sort();
	
		for (var i = 0, l = adminpages.length; i < l; i++)
		{	
			var item	=	adminpages[i];
		
			if (item.indexOf('/') > -1)
			{
				var href	=	P.baseurl + 'm/' + item;
			} else
			{
				var href	= P.baseurl + 'm/h/' + item;
			}
		
			ls.push('<a class="Button Color' + color + '" href="' + href + '">' + item + '</a>');
		
			color++;
		
			if (color > 6)
				color	=	1;
		}
	
		ls.push('</div>');
	
		P.Popup(
			HTML(ls.join('\n')),
			{
				closeonmouseout:	1,
				parent:						'.AdminMenu',
				'$width':						'10em'
			}
		);
	},
	
	// ====================================================================
	HoverPopup: function(selector, func)
	{
/*		$(selector).onOver(
			function(isover)
			{
				if (isover)
					func();
			}
		);*/
		$(selector).set('+HoverHighlight');
		P.OnClick(selector, LBUTTON, func);
	},

	// ====================================================================
	UploadFilePopup: function(uploadurl, options)
	{
		options	= options	|| {};
		
		options.accept			=	options.accept			|| 'image/*; capture=camera';
		options.maxsize			=	options.maxsize			|| 4294967296;
		options.filetype		=	options.filetype		|| '';
		options.title				= options.title				|| 0;
		options.onfinished	=	options.onfinished	|| 0;
		options.resizeimage	=	options.resizeimage	|| 0;
		
		P.MessageBox(
			[
				'<form class="UploadFileForm" method="post" enctype="multipart/form-data">',
					'<input type=file class=FilesToUpload accept="' + options.accept 
							+ '" data-maxsize="' + options.maxsize 
							+ '" data-filetype="' + options.filetype
							+ '" data-uploadurl="' + EncodeEntities(uploadurl) 
							+ '" multiple style="cursor: pointer;"><br><br>',
					(options.title 
						? ('<label>' + EncodeEntities(options.title) + '</label>' 
							+ '<input type=text class=uploadtitle>')
						: ''),
					'<div class=UploadedFiles></div>',
				'</form>',
				'<br>'
			],
			[
				[T[47], function() { P.UploadFile(0, options) }, 3],
				[T[1], function() { P.CloseThisPopup('.UploadFileForm'); }, 1]
			]
		);
			
		$$('.FilesToUpload').click();
		
		if (!options.title)
			$('.FilesToUpload').on('change', function() { P.UploadFile(0, options) });
	},

	// ====================================================================
	MakeProgressHandler: function(i)
	{
		return function(e)
		{
			var el	=	$('.UploadedFiles .Upload' + i + ' .Right');		
			P.UploadFileProgress(e, el)
		}
	},

	// ====================================================================
	UploadProgress: function(percent, size)
	{
		return function(e)
		{
			var el	=	$('.UploadedFiles .Upload' + i + ' .Right');		
			P.UploadFileProgress(e, el)
		}
	},
	
	// ====================================================================
	UploadFile: function(fileselector, options)
	{
		fileselector	=	fileselector	|| '.FilesToUpload';
	
		var el		= $(fileselector);
		var files	=	$$(el).files;
		var l			=	files.length;
		var titleel	=	$('.uploadtitle');
		var title		=	titleel.length ? $$(titleel).value : 0;

		if (!l)
		{
			P.ErrorPopup(T[65]);
			return;
		}
	
		if (titleel.length && !title)
		{
			P.ErrorPopup(T[48]);
			return;
		}
	
		var uploadurl	=	el.get('%uploadurl');
		var filetype	=	el.get('%filetype');
		var maxsize		=	el.get('%maxsize');

		for (var i = 0; i < l; i++)
		{
			var file			=	files[i];
		
			if (filetype && !file.type.match(filetype))
			{
				P.ErrorPopup(file.name + ' is the wrong type');
				continue;
			}
		
			$('.UploadedFiles').add(
				HTML([
					'<table class="Width100 Upload' + i + '">',
						'<tr><td>' + EncodeEntities(file.name) + '</td><td class=Right>0%</td></tr>',
					'</table>'
				].join('\n'))
			);
		
			var url	=	uploadurl + '/' + (title ? encodeURIComponent(title) + '/' : '') + encodeURIComponent(file.name);
		
			if (options.resizeimage && file.type.match(/image.*/))
			{
				var img = document.createElement("img");
				img.src = window.URL.createObjectURL(file);
				
				var canvas	=	document.createElement('canvas');
				canvas.width	=	options.resizeimage[0];
				canvas.height	=	options.resizeimage[1];
			
				pica.resizeCanvas(
					img, 
					canvas, 
					{
						unsharpAmount: 50,
						unsharpRadius: 1,
						unsharpThreshold: 70
					}, 
					function (err) 
					{
						P.SendFile(i, url, file, options);
					}
				);
			} else
			{
				P.SendFile(i, url, file, options);
			}
		}
	},

	// ====================================================================
	SendFile: function(i, url, file, options)
	{
		var xhr = new XMLHttpRequest();
		
		xhr.open('post', url, true);
		
		xhr.upload.addEventListener(
			"progress", 
			P.MakeProgressHandler(i, file.name)
		);
		
		if (options && options.onfinished)
		{
			xhr.onload	=	function(e)
			{
				options.onfinished(e, xhr, file.name);
			}
		}
			
		xhr.send(file);
	},

	// ====================================================================
	UploadFileProgress: function(e, el)
	{
		var percent = Math.round(e.loaded / e.total * 100);
		el.fill(percent + '%');
	},

	// ====================================================================
	DeleteUpload: function(fileid, onfinished)
	{
		P.DialogAPI(
			'upload/delete/' + fileid,
			{},
			function(d)
			{
				if (onfinished)
					onfinished(d, fileid);
			}
		);
	},

	// ====================================================================
	Location: function(callback)
	{
		if (P.inphoneapp)
		{
			callback(JSON.parse(JSBridge.Location()));
		} else
		{
			navigator.geolocation.getCurrentPosition(
				function(p)
				{
					callback(p.coords);
				},
				function()
				{
					P.ErrorPopup(T[66]);
				},
				{
					enableHighAccuracy: true, 
					maximumAge        : 30000, 
					timeout           : 20000
				}				
			);
		}
	},

	// ====================================================================
	MakeOnClick: function(selector, func)
	{
		return function(e)
		{
			var div	=	P.Target(e);
			
			if (!div.is(selector))
				div	=	div.up(selector);
				
			if (!div)
				return;

			if ($(selector + ' .Selected').length)
			{
				if (div.is('.Selected'))
				{
					div.set('-Selected');
				
					if (!$(selector + ' .Selected').length)
						$('.BottomDrawer').hide();
				} else
				{
					div.set('+Selected');
				}
				return;
			}
		
			if (func)
				func(e, div);
		}
	},

	// ====================================================================
	MakeOnHold: function(selector, divid, func)
	{
		return function(e)
		{
			if (e.button == 1 || e.button == 2)
				return;

			var div	=	P.Target(e);
			
			if (!div.is(selector))
				div	=	div.up(selector);
				
			if (!div)
				return;
						
			var previous			=	$('.Selected').length;
			var thisid			=	div.get(divid);
			var filecards			=	$(selector);
			var foundselected	=	0;

			for (var i = 0, l = filecards.length; i < l; i++)
			{
				var thisfilecard	=	$(filecards[i]);
				
				if (thisfilecard.get(divid) == thisid)
					break;
			
				if (foundselected)
				{
					thisfilecard.set('+Selected');
				} else if (thisfilecard.is('.Selected'))
				{
					foundselected	=	1;
				}
			}

			foundselected	=	0;
			
			for (var i = filecards.length - 1; i >= 0; i--)
			{
				var thisfilecard	=	$(filecards[i]);
				
				if (thisfilecard.get(divid) == thisid)
				{
					break;
				}
			
				if (foundselected)
				{
					thisfilecard.set('+Selected');
				} else if (thisfilecard.is('.Selected'))
				{
					foundselected	=	1;
				}
			}
			
			div.set('+Selected');
			$('.BottomDrawer').set({
				'$width':		'100%',
				'$height':	'2.3em'
			}).set('+lgrayg').show();
		
			if (func)
				func(e, div);
		}
	},

	// ====================================================================
	MakeContextMenu: function(items, options)
	{
		options	=	options	|| {};
		
		options.width	=	options.width	|| '12em';
	
		return function(e)
		{
			var el	=	P.Target(e);
			
			if (options.topclass && !el.is(options.topclass))
				el	=	el.up(options.topclass);
			
			if (!el.length)
				return;
		
			P.CancelBubble(e);
		
			var ls	=	[
				'<div class="Raised">',
					'<div class=ContextMenu>'
			];
		
			var menuitems	=	IsFunc(items) ? items(el) : items;
		
			for (var i = 0, l = menuitems.length; i < l; i++)
			{
				var item	=	menuitems[i];
			
				ls.push('<a class=ContextMenuItem data-pos=' + i + '>' + item[0] + '</a>');
			}
		
			ls.push('</div></div>');

			P.Popup(
				HTML(ls.join('\n')),
				{
					'$left':					P.mousex + 'px',
					'$top':						P.mousey + 'px',
					'$width':					options.width,
					closeonmouseout:	1
				}
			);
			
			P.contextmenuitems	=	menuitems;
			
			P.OnClick(
				'.ContextMenu', 
				LBUTTON,
				function(e)
				{
					var item	=	P.Target(e);
				
					if (!item.is('.ContextMenuItem'))
						item	=	item.up('.ContextMenuItem');
				
					if (!item.length)
						return;
				
					P.CloseThisPopup('.ContextMenu');
					var func	=	P.contextmenuitems[parseInt(item.get('%pos'))][1];
					func(e, el);
				}
			);
		}
	},

	// ====================================================================
	// Thanks to http://stackoverflow.com/questions/30467263/handling-alt-enter-key-press-in-javascript
	OnAltEnter: function(selector, func) 
	{
		$(selector).on(
			'?keydown',
			function(e)
			{
				if (e.defaultPrevented) 
					 return;
			
				var handled = false;
				
				if (e.key !== undefined) 
				{
					if (e.key === 'Enter' && e.altKey) 
						func(selector, e);
				} else if (e.keyIdentifier !== undefined) 
				{
					if (e.keyIdentifier === "Enter" && e.altKey)
						alert('Alt + Enter pressed!');
				} else if (e.keyCode !== undefined) 
				{
					if (e.keyCode === 13 && e.altKey)
						alert('Alt + Enter pressed!');
				}

				if (handled)
				{
					e.preventDefault();
					return 0;
				}
			
				return 1;
			}
		);
	},

	// ====================================================================
	MakeQuickFuncsButton: function(selector, avatar)
	{
		selector	=	selector	|| '.QuickFuncsButton';
		avatar		= avatar		|| P.possumbot;
	
		P.MakeFloatingButton(
			selector, 
			'<div class="redb HoverHighlight Pad50 Rounded Raised"><img class=DoubleLineHeight src="/i/h/avatars/' + avatar + '.png" /></div>'
		);
	
		P.OnClick(
			selector,
			LBUTTON,
			P.ShowQuickFuncsPopup
		);
	},

	// ====================================================================
	ShowQuickFuncsPopup: function(e, command)
	{
		command	= command || '';
	
		var popupid = P.Popup(HTML([
				'<div class="whiteb Pad50 Raised">',
					'<div class="FlexInput QuickFuncInput" data-name="quickfuncinput" data-value="' + EncodeEntities(command) + '"></div>',
					'<div class=QuickFuncsButtons></div>',
					'<div class=QuickFuncResults></div>',
				'</div>'
			].join('\n')),
			{
				'$width':					(window.innerWidth * 0.8) + 'px'
			}
		);
		
		P.FlexInput();
		
		P.MakeButtonBar(
			'.QuickFuncsButtons',
			[
				[T[47], P.ProcessQuickFunc],
				[T[71], P.QuickFuncsHelp],
				[T[1], function() { P.CloseThisPopup('.QuickFuncInput'); }]
			]
		);
			
		var textarea	= $(popupid + ' textarea');
		
		P.OnAltEnter(textarea, P.ProcessQuickFunc);
			
		$$(textarea).focus();
	},

	// ====================================================================
	AddQuickFunc: function(command, options, description, func)
	{
		P.quickfuncs.push([command, options, description, func]);
		P.quickfuncs.sort(
			function(a, b) 
			{
				var x = a[0].length;
				var y = b[0].length;
			
				if (x > y)
					return -1;
			
				if (x < y)
					return 1;
				
				return 0;
			}
		);
	},

	// ====================================================================
	ProcessQuickFunc: function()
	{
		var text	= $('.QuickFuncInput textarea').get('value');
	
		if (text)
		{
			var lower	= text.toLowerCase();
			var found	= 0;
		
			for (var i = 0, l = P.quickfuncs.length; i < l; i++)
			{
				var command = P.quickfuncs[i][0];
			
				if (lower.startswith(command.toLowerCase()))
				{
					P.quickfuncs[i][3](text.substr(command.length).trim(), $('.QuickFuncResults'));
					found	= 1;
					break;
				}
			}
		
			if (!found)
				$('.QuickFuncResults').ht('<div class="yellowb Pad50">' + T[72] + '</div>');
		}
	},

	// ====================================================================
	QuickFuncsHelp: function()
	{
		var helptext	= [
			'<div class="Pad50 whiteb">',
				'<div class=Left>',
					'<h2>' + T_POSSUMBOT[1] + '</h2>',
					'<p>' + T_POSSUMBOT[2] + '</p>',
					'<h3>' + T_POSSUMBOT[3] + '</h3>',
					'<dl>'
		];
	
		for (var i = 0, l = P.quickfuncs.length; i < l; i++)
		{
			var item	= P.quickfuncs[i];
			var color	= (i % 6) + 1;
		
			helptext.addtext([
				'<dt>',
					'<a class="Button Color' + color + '" onclick="P.CopyQuickFuncCommand(\'' + EncodeEntities(item[0]) + '\')">',
						item[1],
					'</a>',
				'</dt>',
				'<dd><p>' + item[2] + '</p></dd>'
			]);
		}
	
		helptext.addtext([
					'</dl>',
					'<p>' + T_POSSUMBOT[9] + '</p>',
					'<div class=ButtonBar>',
						'<a class=Color1 onclick="P.ExitFullScreen(1)">' + T[1] + '</a>',
					'</div>',
				'</div>',
			'</div>'
		]);
	
		P.CloseThisPopup('.QuickFuncInput');
	
		P.EnterFullScreen(
			1, 
			'',
			helptext
		);
	},

	// ====================================================================
	CopyQuickFuncCommand: function(command)
	{
		P.ExitFullScreen(1);
		P.ShowQuickFuncsPopup(0, command);
	},

	// ====================================================================
	QuickChangePassword: function(text, resultsdiv)
	{
		if (!text)
		{
			resultsdiv.ht('<div class="yellowb Pad50">' + T[4] + '</div>');
			return;
		}
	
		P.LoadingAPI(
			resultsdiv,
			'login/changepassword',
			{
				password:	text
			},
			function(d, resultsdiv)
			{
				resultsdiv.ht('<div class="greenb Pad50">' + T[41] + '</div>');
			
				setTimeout(
					function()
					{
						P.CloseThisPopup('.QuickFuncInput');
					},
					2000
				);
			}
		);
	},

	// ====================================================================
	QuickChangeAvatar: function(text, resultsdiv)
	{
		if (text.length)
		{
			P.LoadingAPI(
				resultsdiv,
				'possumbot/set',
				{
					avatar:	text
				},
				function(d, resultsdiv)
				{
					$('.QuickFuncsButton img').set('@src', P.ImageURL('avatars/' + text + '.png', 'h'));
					resultsdiv.ht('<div class="greenb Pad50">' + T[41] + '</div>');
				}
			);
		} else
		{	
			P.LoadingAPI(
				resultsdiv,
				'possumbot/list',
				{},
				function(d, resultsdiv)
				{
					var avatars	= d.items;
				
					var ls	= ['<div class=Cards4>'];
				
					for (var i = 0, l = avatars.length; i < l; i++)
					{
						ls.addtext([
							'<div class="lgrayb Card HoverHighlight" onclick="P.QuickChangeAvatar(\'' + avatars[i] + '\')">',
								'<img class=CardIcon src="' + P.ImageURL('avatars/' + avatars[i] + '.png', 'h') + '">',
								'<div class=Center>' + avatars[i] + '</div>',
							'</div>'
						]);
					}
				
					ls.push('</div><div class=Cleared></div>');
				
					P.HTML(resultsdiv, ls);
				}
			);
		}
	}
}

// ====================================================================
P.Init();

$.ready(
	function()
	{
		P.emsize	=	P.EMSize();

		// ====================================================================
		// Using stacktrace.js
		window.onerror = function(msg, file, line, col, error)
		{
			StackTrace.fromError(error).then(
				function(stackframes) {
					var stringifiedStack = stackframes.map(
						function(sf)
						{
							return sf.toString();
						}
					).join('\n');
					console.log(stringifiedStack);
					P.Debug(msg + "\n" + stringifiedStack);
				}
			)/*.catch(
				function(err)
				{
					console.log(err.message);
					$$('.DebugMessages').innerHTML += ('<pre>' + msg + "\n" + err.message + '</pre>');
				}
			);*/
		}

		SetCookie('timeoffset', P.timeoffset);

		P.SetWallpaper(P.wallpaper + '-' + P.texttheme + '.jpg', 1);

		for (var i = 1; i <= 6; i++)
		{
			$('.Color' + i).set('$color', 'white !important');
		}

		P.HoverPopup('.WallpapersMenu', P.SetWallpaperPopup);
		P.HoverPopup('.LanguagesMenu', P.ChangeLanguagePopup);
		P.HoverPopup('.UserMenu', P.UserMenuPopup);
		P.HoverPopup('.AdminMenu', P.AdminMenuPopup);
		
		$(window).on('|resize', P.OnResize)
			.on('|mousemove |mouseenter |touchmove', P.CaptureMousePos)
			.on('|orientationchange',
				function()
				{
					switch (window.orientation)
					{
						case 90:
						case -90:
							P.screenorientation	=	'landscape';
							break;
						default:
							P.screenorientation	=	'portrait';
					};
					P.OnResize();
				}
			);

		if (P.useadvanced)
		{
			if (P.isfirefox || P.ischrome || P.issafari)
				$(window).on('popstate', P.OnPopState);
		
			// Enables the escape key to exit dialogs and fullscreen layers
			$(window).on(
				P.isfirefox ? '|keypress ' : '|keypress |keydown ', 
				function(e)
				{
					if (e.keyCode == 27)
						P.CloseTopLayer();
				}
			);
		
			P.InterceptHREF();
		}
	
	
		if (window.location.href.indexOf('pafera.com') == -1)
			$('body').set('$fontFamily', 'serif');	
	
		if (P.userid)
		{
			P.MakeQuickFuncsButton();
		
			P.AddQuickFunc(
				T_POSSUMBOT[8], 
				T_POSSUMBOT[4], 
				T_POSSUMBOT[5], 
				P.QuickChangePassword
			);
		
			P.AddQuickFunc(
				T_POSSUMBOT[6], 
				T_POSSUMBOT[6], 
				T_POSSUMBOT[7], 
				function() 
				{ 
					$('.QuickFuncsButton').hide() 
					P.CloseThisPopup('.QuickFuncInput');
				}
			);
			
			P.AddQuickFunc(
				T_POSSUMBOT[10], 
				T_POSSUMBOT[10], 
				T_POSSUMBOT[11], 
				P.QuickChangeAvatar
			);

			P.AddQuickFunc(
				T_POSSUMBOT[12], 
				T_POSSUMBOT[12], 
				T_POSSUMBOT[13], 
				function() 
				{ 
					window.location.href	= P.URL('logout', 'h');
				}
			);
		}
	
		// Enable tap events for draggables
		interact.pointerMoveTolerance(32);
	}
);
