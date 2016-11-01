<?php

$T_MAIN		=	$T->Load('h/main');
$T_AD			=	$T->Load('learn/ad');
$T_ABOUT	=	$T->Load('h/about');

$page->title	=	$T_MAIN[1];

?>

<h1><?=$T_MAIN[1]?></h1>

<h2><?=$T_ABOUT[11]?></h2>

<div class=IconSquares>
	<div>
		<img src="<?=$R->ImageURL('happykids.png', 'learn')?>"><br>
		<?=$T_ABOUT[1]?>
	</div>
	<div>
		<img src="<?=$R->ImageURL('bored.student.png', 'learn')?>"><br>
		<?=$T_ABOUT[2]?>
	</div>
	<div>
		<img src="<?=$R->ImageURL('students.using.phones.jpg', 'learn')?>"><br>
		<?=$T_ABOUT[3]?>
	</div>
	<div>
		<img src="<?=$R->ImageURL('kids.with.tablets.jpg', 'learn')?>"><br>
		<?=$T_ABOUT[4]?>
	</div>
</div>

<p class=Center>
	<?=$T_ABOUT[5]?>
</p>

<h2><?=$T_ABOUT[12]?></h2>

<p>
	<?=$T_ABOUT[6]?>
</p>

<p>
	<?=$T_ABOUT[7]?>
</p>

<h2><?=$T_ABOUT[8]?></h2>

<p>
	<?=$T_ABOUT[9]?>
</p>

<p>
	<?=$T_ABOUT[10]?>
</p>

<div class=PageBottomBar></div>

<script>

$.ready(
	function()
	{
		P.MakeButtonBar(
			'.PageBottomBar',
			[
				['<?=$T_AD[4]?>', function() { P.LoadURL(P.URL('register', 'learn')) }, 3],
				['About Jim',  function() { P.LoadURL(P.URL('aboutjim')) }, 2],
				['<?=$T_SYSTEM[1]?>', function() { window.history.back(); }, 1]
			]
		);
	}
);

</script>
