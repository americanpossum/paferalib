/* An asynchronous dynamic JavaScript and CSS loader which will load
 * scripts in order and alert you when everything is loaded.
 */
 
// ====================================================================
function IsArray(v)
{
	return (v instanceof Array)
		|| (Object.prototype.toString.apply(v) === '[object Array]');
};

// ====================================================================
String.prototype.endswith = function(suffix)
{
    return this.indexOf(suffix, this.length - suffix.length) !== -1;
};

// ====================================================================
Array.prototype.contains = function(o)
{
	for (var i = 0, l = this.length; i < l; i++)
	{
		if (this[i] == o)
			return true;
	}

	return false;
};


_loader	=	{
	// ------------------------------------------------------------------
	Init: function()
	{
		this.filesloaded			=	[];
		this.filesprocessing	=	[];
		this.filestoload			=	[];
		this.onfinished				=	[];
		this.numadded					= 0;
		this.numloaded				= 0;
	},

	// ------------------------------------------------------------------
	OnFinished: function(func, timeout)
	{
		if (typeof func != 'function')
			return;
	
		timeout	=	timeout || 0;
	
		if (_loader.filesprocessing.length || _loader.filestoload.length)
		{
			_loader.onfinished.push([func, timeout]);
		} else
		{
			func();
		}
	
		return _loader;
	},

	// ------------------------------------------------------------------
	InQueue: function(url)
	{
		for (var i = 0, l = _loader.filestoload.length; i < l; i++)
			if (_loader.filestoload[i][0] == url)
				return true;

		return (_loader.filesloaded.indexOf(url) !== -1
			|| _loader.filesprocessing.indexOf(url) !== -1
		)
	},

	// ------------------------------------------------------------------
	Load: function(url)
	{
		if (!url)
			return _loader;

		if (IsArray(url))
		{
			_loader.filestoload	=	_loader.filestoload.concat(url);

			if (!_loader.filesprocessing.length)
				_loader.LoadNext();
			return _loader;
		}

		if (_loader.InQueue(url))
			return _loader;

		if (_loader.filesprocessing.length)
		{
			_loader.filestoload.push(url);
			return _loader;
		}

		_loader.ReallyLoad(url);
	
		return _loader;
	},

	// ------------------------------------------------------------------
	LoadNext: function()
	{
		if (_loader.filestoload.length)
		{
			var url	=	_loader.filestoload.shift();

			if (IsArray(url))
			{
				for (var i = 0, l = url.length; i < l; i++)
				{
					_loader.ReallyLoad(url[i])
				}
			} else
			{
				_loader.ReallyLoad(url);
			}
			return _loader;
		}

		if (!_loader.filesprocessing.length)
		{
			for (var i = 0, l = _loader.onfinished.length; i < l; i++)
			{
				var item	=	_loader.onfinished[i];
				setTimeout(
					item[0],
					item[1]
				);
			}

			_loader.onfinished	=	[];

		}

		return _loader;
	},

	// ------------------------------------------------------------------
	ReallyLoad: function(url)
	{
		if (!_loader.numadded)
		{
			setTimeout(
				_loader.CheckLoaded,
				10000
			);
		}
	
		_loader.numadded++;
		var head  	= document.getElementsByTagName('head')[0];

		if (url.endswith('.js'))
		{
			var link  	= document.createElement('script');
		} else
		{
			var link  	= document.createElement('link');
			link.rel  	= 'stylesheet';
			link.type 	= 'text/css';
			link.href 	= url;
		}
	
		link.src 		= url;
	
		link.onload	=	_loader.OnLoaded;
		
		_loader.filesprocessing.push(url);
		head.appendChild(link);

		return _loader;
	},

	// ------------------------------------------------------------------
	OnLoaded: function(e)
	{
		_loader.numloaded++;
	
		var url	=	this.href || this.src;

		for (var i = 0, l	=	_loader.filesprocessing.length; i < l; i++)
		{
			if (url.indexOf(_loader.filesprocessing[i]) !== -1)
			{
				_loader.filesprocessing.splice(i, 1);
				break;
			}
		}
	
		_loader.LoadNext();
		return _loader;
	},

	// ------------------------------------------------------------------
	// Certain older mobile browsers don't support the onload event.
	// This check will sniff them out and instruct them to download
	// Chrome or Firefox.
	CheckLoaded: function()
	{
		if (_loader.numadded && !_loader.numloaded)
			document.body.innerHTML = T[52];
	
		return _loader;
	}
}

_loader.Init();

