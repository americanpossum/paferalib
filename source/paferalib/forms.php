<?php

// ====================================================================
function WeekdaysToString($days)
{
	$weekdays	=	'';
	
	if (in_array('Mon', $days))
	{
		$weekdays	.=	'x';
	} else
	{
		$weekdays	.=	'o';
	}

	if (in_array('Tue', $days))
	{
		$weekdays	.=	'x';
	} else
	{
		$weekdays	.=	'o';
	}

	if (in_array('Wed', $days))
	{
		$weekdays	.=	'x';
	} else
	{
		$weekdays	.=	'o';
	}

	if (in_array('Thu', $days))
	{
		$weekdays	.=	'x';
	} else
	{
		$weekdays	.=	'o';
	}

	if (in_array('Fri', $days))
	{
		$weekdays	.=	'x';
	} else
	{
		$weekdays	.=	'o';
	}

	if (in_array('Sat', $days))
	{
		$weekdays	.=	'x';
	} else
	{
		$weekdays	.=	'o';
	}

	if (in_array('Sun', $days))
	{
		$weekdays	.=	'x';
	} else
	{
		$weekdays	.=	'o';
	}
	
	return $weekdays;
}

// ====================================================================
function WeekdaysSelect($days)
{
	$s	=	'<ul class=CheckDivs>';
	$i	=	0;
	
	foreach (array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun') as $r)
	{
		$s	.=	'<li><input type=checkbox name="weekdays[]" value="' . $r . '" ';
		
		$c	=	$days[0];
		
		if ($days[$i] == 'x')
		{
			$s	.=	'checked';
		}
		
		$s	.=	'> ' . $r . '</li>';
		$i++;
	}
	
	$s	.=	'</ul>
<br class=Cleared>
';
	
	return $s;
}

// ====================================================================
function FormSelect($class, $ls, $selected, $attrs = '')
{
	$s	=	<<<EOT
	<select class="{$class}" {$attrs}>
EOT;

	foreach ($ls as $k => $v)
	{
		$s	.=	'<option value="' . $v . '" ';
		
		if ($selected == $v)
			$s	.=	' selected';
		
		$s	.=	">{$k}</option>";
	}

	$s	.=	'</select>';
	return $s;
}
