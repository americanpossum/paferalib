<?php

// ====================================================================
function Run()
{
	global $results, $pathargs, $D;

	$data					=	json_decode(file_get_contents("php://input"), true);
	$command			=	StrV($pathargs, 0);

	switch ($command)
	{
		case 'set':
			foreach (ArrayV($data, 'values') as $k => $v)
			{
				if (!in_array($k, ['userid', 'groups']))
					$_SESSION[$k]	= $v;
			}
			
			break;
		case 'list':
			RequireGroup('admins');
			$start		=	IntV($data, 'start');
			$limit		=	IntV($data, 'limit', 100, 20, 1000);
			$orderby	=	StrV($data, 'orderby');
			
			if (!$orderby)
				$orderby	=	'start DESC';
			
			$results['count']	=	$D->Count('h_session');
			
			$items	=	[];
			$useragentids	=	[];
			$userids			=	[];
			
			foreach ($D->Find(
					'h_session', 
					"ORDER BY " . $orderby, 
					0, 
					[
						'start'		=> $start,
						'limit'		=> $limit
					]
				) as $r
			)
			{
				$items[]	=	[
					'start'				=> $r->start,
					'lastvisited'	=> $r->lastvisited,
					'length'			=> $r->length,
					'useragent'		=> $r->useragentid,
					'username'		=> $r->userid,
				];
				
				$useragentids[strval($r->useragentid)]	=	0;
				
				if ($r->userid)
					$userids[strval($r->userid)]	=	0;
			}
			
			$useragents	=	[];
			
			foreach ($D->Query("
				SELECT suid1, useragent
				FROM h_useragents
				WHERE suid1 IN (" . join(", ", array_keys($useragentids)) . ")"
				) as $r
			)
			{
				$useragents[$r['suid1']]	=	$r['useragent'];
			}
			
			$usernames	=	[];
			
			if ($userids)
			{
				foreach ($D->Find(
						'h_user',
						"WHERE suid1 IN (" . join(", ", array_keys($userids)) . ")",
						0,
						[
							'fields'	=> 'suid1, phonenumber, realname, username',
						]
					) as $r
				)
				{
					$usernames[$r->suid1]	=	$r->Name();
				}
			}
			
			foreach ($items as $id => $item)
			{
				foreach ($useragents as $k => $v)
				{
					if ($item['useragent'] == $k)
					{
						$items[$id]['useragent']	=	$v;
						break;
					}
				}
				
				if ($userids)
				{
					foreach ($usernames as $k => $v)
					{
						if ($item['username'] == $k)
						{
							$items[$id]['username']	=	$v;
							break;
						}
					}
				}
			}
			
			$results['items']	=	$items;
			break;
		default:
			$results['error']	=	'Unknown command: ' . $command;
	};
}

Run();
