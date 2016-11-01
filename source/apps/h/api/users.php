<?php

// ====================================================================
function ListUsers($orderby = '', $start = 0, $limit = 100, $filter = '')
{
	global $D;

	if (!in_array(
		$orderby, 
		[
			'phonenumber',
			'phonenumber DESC',
			'place',
			'place DESC',
		])
	)
	{
		$orderby	=	'phonenumber';
	}
				
	$start	=	Bound($start);
	$limit	=	Bound($limit, 20, 10000);
	
	return $D->Find('h_user',
		$filter ? "WHERE phonenumber LIKE '%?%'" : '', 
		$filter ? $filter : [], 
		[
			'start'		=> $start,
			'limit'		=> $limit,
			'orderby' => $orderby,
		]
	);
}			

// ====================================================================
function ListGroups($orderby = '', $start = 0, $limit = 1000, $filter = '')
{
	global $D;
	
	if (!in_array(
		$orderby, 
		[
			'groupname',
			'groupname DESC',
		])
	)
	{
		$orderby	=	'groupname';
	}
	
	$start	=	Bound($start);
	$limit	=	Bound($limit, 20, 10000);
			
	return $D->Find('h_group', 
		$filter ? "WHERE groupname LIKE '%?%'" : '', 
		$filter ? $filter : [], 
		[
			'start'		=> $start,
			'limit'		=> $limit,
			'orderby' => $orderby,
		]
	);
	
	return $groups;
}			

// ====================================================================
function Run()
{
	global $results, $pathargs, $D, $R, $S, $T_SYSTEM;

	$data					=	json_decode(file_get_contents("php://input"), true);
	$command			=	StrV($pathargs, 0);

	switch ($command)
	{
		case 'list':
			RequireGroup(['admins']);
			
			$start		=	IntV($data, 'start', 0, 0, 99999999);
			$limit		=	IntV($data, 'limit', 100, 20, 1000);
			$orderby	=	StrV($data, 'orderby', 'phonenumber');
			$filter		=	StrV($data, 'filter');
			
			if (IntV($data, 'listgroups'))
			{
				$groups	=	[];
				
				foreach (ListGroups() as $g)
				{
					$groups[ToShortCode($g->suid1)]	=	[
						'translated'	=> $g->translated_translated,
						'name'				=> $g->groupname,
					];
				}
				
				$results['groups']	=	$groups;
			}
			
			$ls	=	[];
			
			$results['count']	=	$D->Query('SELECT COUNT(*) AS count FROM h_users')->fetch()['count'];
			
			foreach (ListUsers($orderby, $start, $limit, $filter) as $u)
			{
				$ingroups	=	[];
				
				foreach ($u->Linked('h_group') as $g)
				{
					$ingroups[]	=	ToShortCode($g->suid1);
				}
				
				$thumbfile	=	'data/h/headshots/' . IDToAlnumPath($u->suid1)[2] . '.jpg';
				
				$thumbfile	=	is_file($thumbfile) 
					? $R->baseurl . $thumbfile 
					: $R->baseurl . 'i/h/svg/user.svg';
				
				$ls[]	=	[
					'id'					=> ToShortCode($u->suid1),
					'headshot'		=> $thumbfile,
					'phonenumber'	=> $u->phonenumber,
					'place'				=> $u->place,
					'username'		=> $u->username_translated,
					'groups'			=> $ingroups,
				];
			}
			
			$results['items']	= $ls;
			break;
		case 'listgroups':
			RequireGroup(['admins']);
			
			$start		=	IntV($data, 'start', 0, 0, 99999999);
			$limit		=	IntV($data, 'limit', 100, 20, 1000);
			$orderby	=	StrV($data, 'orderby', 'groupname');
			$filter		=	StrV($data, 'filter');
			
			$ls	=	[];
			
			$results['count']	=	$D->Query('SELECT COUNT(*) AS count FROM h_groups')->fetch()['count'];

			$groups	=	[];
			
			foreach (ListGroups($orderby, $start, $limit, $filter) as $g)
			{
				$groups[ToShortCode($g->suid1)]	=	[
					'translated'	=> $g->translated_translated,
					'name'				=> $g->groupname,
				];
			}
			
			$results['items']	=	$groups;
			break;
		case 'view':
			$userid				=	StrV($data, 'userid');
			
			if (!$userid)
			{
				$results['error']	=	$T_SYSTEM[48];
				return;
			}
			
			$usercode	=	$userid;
			$userid	=	FromShortCode($userid);
			
			$u	=	$D->Load('h_user', $userid);
			
			$ingroups	=	[];
			
			foreach ($u->Linked('h_group') as $g)
			{
				$ingroups[]	=	ToShortCode($g->suid1);
			}
			
			$results['item']	=	[
				'id'					=> $usercode,
				'phonenumber'	=> $u->phonenumber,
				'place'				=> $u->place,
				'email'				=> $u->email,
				'username'		=> $u->username_translated,
				'headshot'		=> h_user::HeadshotURL($userid),
				'groups'			=> $ingroups,
			];
			break;
		case 'viewgroup':
			$groupcode	=	StrV($data, 'groupid');
			
			if (!$groupcode)
			{
				$results['error']	=	$T_SYSTEM[48];
				return;
			}
			
			$groupid	=	FromShortCode($groupcode);
			$g	=	$D->Load('h_group', $groupid);
			$results['item']	=	[
				'id'					=> $groupcode,
				'name'				=> $u->groupname,
				'translated'	=> $u->translated_translated,
			];
			break;
		case 'save':
			$userid				=	StrV($data, 'userid');
			$phonenumber	=	StrV($data, 'phonenumber');
			$place				=	StrV($data, 'place');
			
			if (!$phonenumber || !$place)
			{
				$results['error']	=	$T_SYSTEM[48];
				return;
			}
			
			$u	=	$userid 
				? $D->Load('h_user', FromShortCode($userid)) 
				: $D->Create('h_user');
				
			$u->Set($data);
			
			$userid ? $u->Update() : $u->Insert();
			
			$groups	=	[];
			$groupstolink	=	ArrayV($data, 'groups');
			
			if ($groupstolink)
			{
				foreach ($groupstolink as $groupid => $status)
				{
					if ($status == 'on')
						$groups[]	=	$D->Load('h_group', FromShortCode($groupid));
				}
				
				if ($groups)
				{
					$u->Link($groups);
				} else
				{
					$u->UnlinkMany('h_group');
				}
			} else if ($userid)
			{
				$u->UnlinkMany('h_group');
			}
			break;
		case 'savegroup':
			$groupid			=	StrV($data, 'groupid');
			$groupname		=	StrV($data, 'groupname');
			
			if (!$groupname)
			{
				$results['error']	=	$T_SYSTEM[48];
				return;
			}
			
			$g	=	$groupid 
				? $D->Load('h_group', FromShortCode($groupid)) 
				: $D->Create('h_group');
				
			$g->Set($data);
			
			$groupid ? $g->Update() : $g->Insert();
			break;
		case 'delete':
			$userid	=	StrV($data, 'userid');
			
			if (!$userid)
			{
				$results['error']	=	$T_SYSTEM[48];
				return;
			}
			
			$u	=	$D->Load('h_user', FromShortCode($userid));
			$u->Delete();
			$u->UnlinkMany('h_group');
			break;
		case 'setgroup':
			$userid		=	StrV($data, 'userid');
			$groupid	=	StrV($data, 'groupid');
			$ismember	=	IntV($data, 'ismember');
			
			if (!$userid || !$groupid)
			{
				$results['error']	=	$T_SYSTEM[48];
				return;
			}
			
			$u	=	$D->Load('h_user', FromShortCode($userid));
			$g	=	$D->Load('h_group', FromShortCode($groupid));
			
			$ismember ? $u->Link($g) : $u->Unlink($g);
			break;
		default:
			$results['error']	=	'Unknown command: ' . $command;
	};
}

Run();
