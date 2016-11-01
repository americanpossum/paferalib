<?php

$T_ADMIN	=	$T->Load('admin');

$page->title	=	"Users";

?>

<div class=ButtonBar>
	<input type=search class=Filter placeholder="">
	<a class="Color1" onclick="ListUsers()"><?=$T_ADMIN[1]?></a>
	<a class="Color2" onclick="EditUser()"><?=$T_ADMIN[2]?></a>
	<a class="Color3" onclick="ListGroups()"><?=$T_ADMIN[3]?></a>
	<a class="Color4" onclick="EditGroup()"><?=$T_ADMIN[4]?></a>
	<a class="CardAction Color5" onclick="DeleteUser()"><?=$T_SYSTEM[22]?></a>
</div>
<div class=Cleared></div>

<div class="UserResults"></div>
<div class=Cleared></div>

<div class=PageBar></div>
<div class=Cleared></div>

<script>

var T_ADMIN				= <?=json_encode($T_ADMIN)?>;
var groups				= 0;
var currentgroup	=	0;

// ********************************************************************
_loader.OnFinished(
	function()
	{
		_loader.Load(P.JSURL('users'));
	}
);

</script>
