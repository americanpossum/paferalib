/* Common utilities for all web pages */

var NUMBERS = '0123456789';
var LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
var UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

// ====================================================================
function ValidString(param, value)
{
	if (param == '')
		return false;

	for (i = 0; i < param.length; i++)
	{
		if (value.indexOf(param.charAt(i),0) == -1)
			return false;
	}
	return true;
}

// ====================================================================
function IsNumber(param) {return ValidString(param, NUMBERS);}
function IsLower(param) {return ValidString(param, LOWERCASE);}
function IsUpper(param) {return ValidString(param, UPPERCASE);}
function IsAlpha(param) {return ValidString(param, LOWERCASE + UPPERCASE);}
function IsAlphanum(param) {return ValidString(param, LOWERCASE + UPPERCASE + NUMBERS);}

// ====================================================================
// Shamelessly stolen from AngularJS
function EncodeEntities(s)
{
	if (!s)
		return '';

  return s.
    replace(/&/g, '&amp;').
    replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, function(value) {
      var hi = value.charCodeAt(0);
      var low = value.charCodeAt(1);
      return '&#' + (((hi - 0xD800) * 0x400) + (low - 0xDC00) + 0x10000) + ';';
    }).
    replace(/([^\#-~| |!])/g, function(value) {
      return '&#' + value.charCodeAt(0) + ';';
    }).
    replace(/</g, '&lt;').
    replace(/>/g, '&gt;');
}

// ====================================================================
function Merge()
{
	var newobj	=	{};
	
	for (var i = 0, l = arguments.length; i < l; i++) 
	{
		var obj	=	arguments[i];
	
		for (var k in obj)
			newobj[k]	=	obj[k];
  }
	
	return newobj;
}

// ====================================================================
// Thanks to http://stackoverflow.com/questions/7486085/copying-array-by-value-in-javascript
function DeepCopy(o)
{
   var out, v, key;
   out = Array.isArray(o) ? [] : {};
   for (key in o) {
       v = o[key];
       out[key] = (typeof v === "object") ? DeepCopy(v) : v;
   }
   return out;
}

// ====================================================================
function PrintArray(obj, level)
{
	var ls = [];
	level = level || 0;

	var padding = [];

	for (var j = 0; j < level + 1; j++)
		padding.push("\t");

	padding	=	padding.join('');

	if (typeof(obj) == 'object')
	{
		var maxkeylength	=	0;

		for (var key in obj)
		{
			if (key.length > maxkeylength)
				maxkeylength	=	key.length;
		}

		for (var key in obj)
		{
			var value 		= obj[key];

			if (typeof(value) == 'object')
			{
				ls.push(padding + key + ":");
				ls.push(PrintArray(value, level + 1));
			} else
			{
				var keylength	=	key.length;
				var numtabs		=	Math.floor((maxkeylength - keylength) / 6) + 1;
				var tabs			=	[];

				for (var i = 0; i < numtabs; i++)
					tabs.push("\t");

				tabs	=	tabs.join('');

				ls.push(padding + key + ":" + tabs + value);
			}
		}
	} else {
		ls.push(padding + obj + "\t(" + typeof(obj) + ")");
	}
	return ls.join("\n");
}

// ====================================================================
function PrintTimestamp(t, onlydate)
{
	if (t)
	{
	  var d			= new Date(t * 1000);
	} else
	{
	  var d			= new Date();
	}

  var year	= d.getFullYear();

	var month	=	d.getMonth() + 1;
  month			= month < 10 ? '0' + month : month;

	var day		=	d.getDate();
  day				= day < 10 ? '0' + day : day;

	if (onlydate)
		return year + '-' + month + '-' + day;

	var hour	=	d.getHours();
  hour			= hour < 10 ? '0' + hour : hour;

	var minute	=	d.getMinutes();
  minute			= minute < 10 ? '0' + minute : minute;

	var second	=	d.getSeconds();
  second			= second < 10 ? '0' + second : second;

  return year + '-' + month + '-' + day + ' ' + hour + ':' + minute + ':' + second;
}

// ====================================================================
function SecondsToTime(s, precision)
{
	precision	= precision || 0;
	
	var	hours		=	Math.floor(s / 3600);
  hours				= hours < 10 ? '0' + hours : hours;

	var	minutes	=	Math.floor((s % 3600) / 60);
  minutes			= minutes < 10 ? '0' + minutes : minutes;

	var	seconds	=	s % 60;
  seconds			= seconds < 10 ? '0' + seconds.toFixed(precision) : seconds.toFixed(precision);

	return hours + ':' + minutes + ':' + seconds;
}

// ====================================================================
function GMTToLocal(t)
{
	if (!t)
	{
		var d = new Date();
		var s =	d.toISOString().substr(0, 19);
		var t = d.toTimeString();
		return s.substr(0, 11) + t.substr(0, 8) + s.substr(19);
	}

	t	=	t.replace(' ', 'T');

	var d	=	new Date();
	var e	=	new Date(t);

	if (isNaN(e.getTime()))
		throw new Error('Invalid date: ' + t);

	e.setMinutes(e.getMinutes() + d.getTimezoneOffset());
	return e.toISOString().substr(0, 19);
}

// ====================================================================
function LocalToGMT(t)
{
	var d	=	new Date();

	if (!t)
	{
		var e	=	new Date(d.toISOString().substr(0, 19).replace(' ', 'T') + 'Z');
		e.setMinutes(e.getMinutes() - d.getTimezoneOffset());
		return e.toISOString().substr(0, 19);
	}

	t	=	t.replace(' ', 'T');
	var e	=	new Date(t + 'Z');

	if (isNaN(e.getTime()))
		throw new Error('Invalid date: ' + t);

	e.setMinutes(e.getMinutes() - d.getTimezoneOffset());
	return e.toISOString().substr(0, 19);
}

// ====================================================================
function DisplayTime(t)
{
	return t.substr(0, 19).replace('T', ' ');
}

// ====================================================================
function Keys(obj)
{
  var keys = [];

  for (var k in obj)
    keys.push(k);

  return keys;
}

// ====================================================================
function Values(obj)
{
  var values = [];

  for (var p in obj)
	{
		switch (typeof obj[p])
		{
			case 'function':
				continue;
		};

    values.push(obj[p]);
	}

  return values;
}

// ====================================================================
function IsFunc(v)
{
	return typeof v === 'function';
}

// ====================================================================
function IsEmpty(v)
{
	if (typeof v === 'undefined')
		return true;

	if (Array.isArray(v))
	{
		if (v.length == 0)
			return true;
	} else
	{
		for (var k in v)
		{
			if (v.hasOwnProperty(k))
				return false;
		}
		return true;
	}

	return false;
}

// ====================================================================
function NestObjects()
{
	var o	=	arguments[0];

	for (var i = 1, l = arguments.length; i < l; i++)
	{
		var varname	=	arguments[i];

		if (IsEmpty(o[varname]))
			o[varname]	=	{};

		o	=	o[varname];
	}

	return o;
}

// ====================================================================
// Thanks to http://planetozh.com/blog/2008/04/javascript-basename-and-dirname/
function basename(path)
{
	return path.replace(/\\/g,'/').replace( /.*\//, '' );
}

function dirname(path)
{
	return path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '');;
}

// ====================================================================
String.prototype.startswith = function(prefix) {
    return this.indexOf(prefix) == 0;
};

// ====================================================================
String.prototype.endswith = function(suffix) {
    return this.indexOf(suffix, this.length - suffix.length) !== -1;
};

// ====================================================================
// Thanks to http://dumpsite.com/forum/?topic=4.msg29#msg29
String.prototype.replaceAll = function(str1, str2, ignore)
{
	return this.replace(new RegExp(str1.replace(/([\/\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|\<\>\-\&])/g,"\\$&"),(ignore?"gi":"g")),(typeof(str2)=="string")?str2.replace(/\$/g,"$$$$"):str2);
};

// ====================================================================
// Thanks to http://stackoverflow.com/questions/2332811/capitalize-words-in-string/7592235#7592235
String.prototype.capitalize = function() {
    return this.replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });
};

// ====================================================================
// Thanks to http://stackoverflow.com/questions/3954438/remove-item-from-array-by-value
Array.prototype.remove = function()
{
	var what, a = arguments, L = a.length, ax;
	while (L && this.length)
	{
		what = a[--L];
		while ((ax = this.indexOf(what)) !== -1)
			this.splice(ax, 1);
	}
	return this;
};

// ====================================================================
Array.prototype.contains = function(o)
{
	var l	=	this.length;

	for (var i = 0; i < this.length; i++)
	{
		if (this[i] == o)
			return true;
	}

	return false;
};

// ====================================================================
Array.prototype.addtext	=	function(ls, delimiter)
{
	delimiter	=	delimiter || "\n";
	this.push(ls.join(delimiter));
}

// ====================================================================
function Shuffle(a)
{
  var i = a.length, j, tempi, tempj;
  if ( i == 0 ) return false;
  while ( --i ) {
     j       = Math.floor( Math.random() * ( i + 1 ) );
     tempi   = a[i];
     tempj   = a[j];
     a[i] = tempj;
     a[j] = tempi;
  }
  return a;
}

// ====================================================================
function strcmp(a, b, ignorecase)
{
	if (ignorecase)
	{
		a	=	a.toUpperCase();
		b	=	b.toUpperCase();
	}
	
	if (a > b)
		return 1;

	if (b > a)
		return -1;

  return 0;
}

// ====================================================================
function RandInt(min, max)
{
	return Math.floor(Math.random() * (max - min)) + min;
}

// ====================================================================
function Range(min, max, step)
{
	var seq	=	Array();

	for (var i = min; i < max; i += step)
	{
		seq.push(i);
	}

	return seq;
}

// ====================================================================
function IsString(v)
{
	return (typeof v == 'string' || v instanceof String);
}

// ====================================================================
function IsNum(v)
{
	return typeof v == 'number';
}

// ====================================================================
function IsArray(v)
{
	return (v instanceof Array)
		|| (Object.prototype.toString.apply(v) === '[object Array]');
};

// ====================================================================
function IsObject(obj)
{
  return obj === Object(obj);
}

// ====================================================================
function isIE ()
{
  var myNav = navigator.userAgent.toLowerCase();
  return (myNav.indexOf('msie') != -1) ? parseInt(myNav.split('msie')[1]) : false;
}

// ====================================================================
// Import Python 3's string formatting method thanks to
// https://github.com/xfix/python-format
(function(){var a=/\{\{|\}\}|\{(\d*)((?:\.(?:\w+)|\[(?:[^\]]*)\])*)(?::(?:([^{}]?)([<>=^]))?([-+\x20])?(\#)?(0)?(\d*)(,)?(?:\.(\d+))?([bcdeEfFgGosxX%])?)?\}/g;function b(f){var c=-1;var d=Array.prototype.slice.call(arguments,1);return f.replace(a,function e(r,j,t,L,y,U,M,A,l,p,O,k){function u(n,o){var g="";while(o>0){if(o&1){g+=n}o>>=1;n+=n}return g}var m;var w;var J;var K=new ReferenceError(r+" is "+m+".");var q={b:function T(){if(M){M="0b"}return M+m.toString(16)},c:function S(){return String.fromCharCode(20)},d:function R(){return m},e:function Q(){return m.toExponential(O||6)},E:function C(){return m.toExponential(O||6).toUpperCase()},f:function P(){return m.toFixed(O||6)},F:function B(){return q.f()},g:function N(){if(m===0){return 1/m===Infinity?"0":"-0"}if(O===0){O=1}var g=Math.abs(m);if(0.0001<=g&&g<Math.pow(10,O||6)){return +q.f()}else{return m.toExponential(O)}},G:function z(){return q.g().toUpperCase()},n:function I(){return q.g()},o:function H(){if(M){M="0o"}return M+m.toString(8)},s:function D(){return(""+m).substring(0,O)},x:function v(){if(M){M="0x"}return M+m.toString(16)},X:function i(){if(M){M="0x"}return M+m.toString(16).toUpperCase()},"%":function h(){m*=100;return q.f()+"%"}};if(r==="{{"){return"{"}if(r==="}}"){return"}"}if(A){L=L||"0";y=y||"="}j=j||++c;m=d[j];t.replace(/\.(\w+)|\[([^\]]*)\]/g,function(o,n,g){if(m==null){throw K}m=m[n||g]});if(m==null){throw K}if(!m.toExponential){if(k&&k!="s"){throw new TypeError(r+" used on "+m)}k="s";y=y||"<"}if(m==null){throw new TypeError(r+" is "+m)}w=""+q[k||"g"]();if(p){J=w.split(".");J[0]=J[0].replace(/(?=\d(?:\d{3})+$)/g,",");w=J.join(".")}if(l){L=L||" ";switch(y){case"<":w+=u(L,l-w.length);break;case"=":switch(U){case"+":case" ":if(w.charAt(0)==="-"){U="-";w=w.substring(1)}break;default:if(w.charAt(0)!=="-"){U=""}break}w=U+u(L,l-w.length-(""+U).length)+w;break;case"^":l-=w.length;w=u(L,Math.floor(l/2))+w+u(L,Math.ceil(l/2));break;default:w=u(L,l-w.length)+w;break}}return w})}if(typeof module!=="undefined"){module.exports=b}else{this.format=b}}.call(this));

// ====================================================================
function ToParams(data)
{
	var params = [];

	for (var p in data)
	{
		if (data.hasOwnProperty(p))
		{
			var k = p, v = data[p];

			params.push(typeof v == "object"
				? ToParams(v, k) :
				encodeURIComponent(k) + "=" + encodeURIComponent(v));
		}
	}
	return params.join("&");
}
