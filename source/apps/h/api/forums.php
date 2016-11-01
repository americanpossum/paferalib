<?php

// ====================================================================
function Run()
{
	global $results, $pathargs, $D, $R, $S, $T_SYSTEM;

	$data					=	json_decode(file_get_contents("php://input"), true);
	$command			=	StrV($pathargs, 0);
	
	class_exists('h_message');
	class_exists('h_messagebody');
	
	switch ($command)
	{
		case 'list':
			$start		=	IntV($data, 'start', 0, 0, 99999999);
			$limit		=	IntV($data, 'limit', 100, 20, 1000);
			$orderby	=	StrV($data, 'orderby', 'senttime DESC');
			$filter		=	StrV($data, 'filter');
			$userid		=	StrV($data, 'userid');
			$threadid	=	StrV($data, 'threadid');
			$uri			=	StrV($data, 'uri');
			$flags		=	IntV($data, 'flags');
			
			$ls	=	[];
			
			$conds		=	[];
			$values		=	[];
			$groupby	=	'';
			
			if ($flags)
			{
				$conds[]	= 'flags & ?';
				$values[]	=	$flags;
			}
			
			if ($userid)
			{
				$conds[]	= 'userid = ?';
				$values[]	=	FromShortCode($userid);
			}
			
			if ($threadid)
			{
				$conds[]	= 'userid = ?';
				$values[]	=	FromShortCode($threadid);
			} else
			{
				$conds[]	= 'threadid = 0';
			}
			
			if ($uri)
			{
				$conds[]	= 'uri = ?';
				$values[]	=	$uri;
			}
			
			if ($filter)
			{
				$f	=	EscapeSQL($filter);
				$conds[]	=	"(
					title LIKE '%{$f}%'
					OR message LIKE '%{$f}%'
				)";
			}
			
			$results['count']	=	$D->Query(
				"SELECT COUNT(*)
				FROM h_messages AS a "
					. ($filter ? 'LEFT JOIN h_messagebody AS b ON a.bodyid = b.suid1 ' : '')
					. ($conds ? 'WHERE ' . join(' AND ', $conds) : ''), 
				$values
			)->fetch()['COUNT(*)'];
			
			foreach ($D->Query(
				"SELECT a.suid1 AS id,
					threadid,
					parentid,
					fromid,
					toid,
					title,
					message,
					senttime,
					readtime,
					ups,
					downs,
					bounty,
					replies,
					views,
					a.flags AS flags
				FROM h_messages AS a
				LEFT JOIN h_messagebodys AS b ON a.bodyid = b.suid1 "
				 . ($conds ? 'WHERE ' . join(' AND ', $conds) : ''),
				$values,
				[
					'start'		=> $start,
					'limit'		=> $limit,
				]) as $r
			)
			{
				$ls[]	=	$r;
			}
			
			$results['items']	=	$ls;
			break;
		case 'save':
			RequireLogin();
		
			$threadid	=	StrV($data, 'threadid');
			$parentid	=	StrV($data, 'parentid');
			$uri			=	StrV($data, 'uri');
			$toids		=	ArrayV($data, 'toids');
			$title		=	StrV($data, 'title');
			$message	=	StrV($data, 'message');
			
			if ((!$uri && !$toids) || (!$title || !$message))
			{
				$results['error']	=	$T_SYSTEM[48];
				break;
			}
			
			$threadid	=	$threadid ? FromShortCode($threadid) : 0;
			$parentid	=	$parentid ? FromShortCode($parentid) : 0;
		
			$body	=	$D->Create('h_messagebody')
				->Set([
					'title'			=> $title,
					'message'		=> $message,
					'senttime'	=> DB::Date(),
				])->Insert();
				
			$items	=	[];
				
			if ($toids)
			{
				foreach ($toids as $toid)
				{
					$items[]	=	ToShortCode(
						$D->Create('h_message')
							->Set([
								'threadid'	=> $threadid,
								'parentid'	=> $parentid,
								'fromid'		=> $_SESSION['userid'],
								'toid'			=> $toid,
								'bodyid'		=> $body->suid1,
								'readtime'	=> '',
							])->Insert()->suid1
					);
					
					if ($parentid)
					{
					}
				}
			} else
			{
				$items[]	=	ToShortCode(
					$D->Create('h_message')
						->Set([
							'fromid'		=> $_SESSION['userid'],
							'uri'				=> $uri,
							'bodyid'		=> $body->suid1,
							'bounty'		=> IntV($data, 'bounty'),
							'readtime'	=> '',
						])->Insert()->suid1
				);
			}
			
			$results['items']	=	$items;
			break;
		default:
			$results['error']	=	'Unknown command: ' . $command;
	};
}

Run();
