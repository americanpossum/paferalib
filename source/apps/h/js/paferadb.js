// ********************************************************************
function DB()
{
	this.intransaction	=	false;
	this.commands	=	[];
};

// --------------------------------------------------------------------
DB.prototype.Begin	=	function()
{
	this.intransaction	=	true;
	return this;
}

// --------------------------------------------------------------------
DB.prototype.Commit	=	function()
{
	if (!this.commands.length)
		return;

	var p	=	_.promise();

	P.API(
		'db/run',
		{
			commands:	this.commands
		},
		function (d)
		{
			p.fire(d.error ? false : true, d);
		},
		{
			timeout:			300,
			handleerror:	0
		}
	);
	this.intransaction	=	false;
	this.commands				=	[];

	return p;
}

// --------------------------------------------------------------------
DB.prototype.Load	=	function(result, model, ids, fields, methods)
{
	fields	=	fields	|| '*';
	methods	=	methods	|| [];

	this.commands.push({
		result:		result,
		command:	'load',
		model:		model,
		ids:		ids,
		fields:		fields,
		methods:	methods
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.LoadMany	=	function(result, model, ids, fields)
{
	fields	=	fields || '*';

	this.commands.push({
		result:		result,
		command:	'loadmany',
		model:		model,
		ids:		ids,
		fields:		fields
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.Find	=	function(result, model, cond, options)
{
	var objs	=	null;
	var	count	=	0;

	var options	=	options || {};
	start		=	options.start || 0;
	limit		=	options.limit || 100;
	orderby	=	options.orderby || '';
	fields	=	options.fields || '*';
	methods	=	options.methods || [];

	this.commands.push({
		result:		result,
		command:	'find',
		model:		model,
		cond:			cond,
		start:		start,
		limit:		limit,
		orderby:	orderby,
		fields:		fields
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.Update	=	function(result, model, ids, data)
{
	this.commands.push({
		result:		result,
		command:	'update',
		model:		model,
		ids:			ids,
		data:			data
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.Save	=	function(result, model, data)
{
	this.commands.push({
		result:		result,
		command:	'save',
		model:		model,
		data:			data
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.SaveMany	=	function(result, model, data)
{
	this.commands.push({
		result:		result,
		command:	'savemany',
		model:		model,
		data:			data
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.Delete	=	function(model, ids, cond)
{
	this.commands.push({
		command:	'delete',
		model:		model,
		ids:		ids,
		cond:			cond
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.Link	=	function(model1, id1, model2, id2, linktype, num, comment)
{
	linktype	=	linktype || 0;
	num				=	num || 0;
	comment		=	comment || '';

	this.commands.push({
		command:	'link',
		model1:		model1,
		id1:			id1,
		model2:		model2,
		id2:			id2,
		linktype:	linktype,
		num:			num,
		comment:	comment
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.LinkArray	=	function(model1, id1, model2, id2, linktype, num, comment)
{
	linktype	=	linktype || 0;
	num				=	num || 0;
	comment		=	comment || '';

	this.commands.push({
		command:	'linkarray',
		model1:		model1,
		id1:			id1,
		model2:		model2,
		id2:			id2,
		linktype:	linktype,
		num:			num,
		comment:	comment
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.Unlink	=	function(model1, id1, model2, id2, linktype)
{
	this.commands.push({
		command:	'unlink',
		model1:		model1,
		id1:			id1,
		model2:		model2,
		id2:			id2,
		linktype:	linktype
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.Linked	=	function(result, model1, id1, model2, linktype, orderby, methods)
{
	linktype	=	linktype	|| 0;
	orderby		=	orderby		|| '';
	methods		=	methods		|| [];

	this.commands.push({
		result:		result,
		command:	'linked',
		model1:		model1,
		id1:			id1,
		model2:		model2,
		linktype:	linktype,
		orderby:	orderby,
		methods:	methods
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.UnlinkMany	=	function(model1, id1, model2, linktype)
{
	linktype	=	linktype	|| 0;

	this.commands.push({
		command:	'unlinkmany',
		model1:		model1,
		id1:			id1,
		model2:		model2,
		linktype:	linktype
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.Query	=	function(result, query)
{
	this.commands.push({
		result:		result,
		command:	'query',
		query:		query
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.Eval	=	function(result, code)
{
	this.commands.push({
		result:		result,
		command:	'eval',
		code:			code
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.Fields	=	function(result, model)
{
	this.commands.push({
		result:		result,
		command:	'fields',
		model:		model
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.ListTypes	=	function(result)
{
	this.commands.push({
		result:		result,
		command:	'listtypes'
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

// --------------------------------------------------------------------
DB.prototype.SaveTranslation	=	function(result, model, ids, translations)
{
	this.commands.push({
		result:				result,
		command:			'savetranslation',
		model:				model,
		ids:					ids,
		translations:	translations
	});

	if (!this.intransaction)
		return this.Commit();

	return this;
}

var _db	=	new DB();
