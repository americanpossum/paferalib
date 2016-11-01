<?php

// ====================================================================
function Run()
{
	global $results, $pathargs, $D, $R, $T, $T_SYSTEM;
	
	$T_LEARN	=	$T->Load('learn/default');

	$data					=	json_decode(file_get_contents("php://input"), true);
	$command			=	StrV($pathargs, 0);
	$messageclass			=	'h_message';
	$messagebodyclass	=	'h_messagebody';

	switch ($command)
	{
		case 'search':
			$filter				=	StrV($data, 'filter');
			$start				=	IntV($data, 'start', 0, 0, 99999999);
			$limit				=	IntV($data, 'limit', 100, 20, 1000);
			$uri					=	StrV($data, 'uri');
			$threadid			=	IntV($data, 'threadid');
			$flags				=	IntV($data, 'flags');
			$id						=	StrV($data, 'id');
			
			$conds	=	[];
			$values	=	[];
			
			if ($uri)
			{
				$conds[]	=	'uri = ?';
				$values[]	= $uri;
			}
			
			if ($threadid)
			{
				$conds[]	=	'threadid = ?';
				$values[]	= $threadid;
			}
			
			if ($flags)
			{
				$conds[]	=	'flags & ?';
				$values[]	= $flags;
			}
			
			if ($id)
			{
				$conds[]	=	'suid1 = ?';
				$values[]	= FromShortCode($id);
			}
			
			$whereclause	=	$conds ? ' WHERE ' . join(' AND ', $conds) : '';
			
			if (!$id)
				$results['count']	=	$D->Count('h_message', $whereclause, $values);
			
			$ls				=	[];
			$bodyids	=	[];
			$userids	=	[];
			
			foreach ($D->Find('h_message', $whereclause, $values) as $m)
			{
				$ls[]	=	[
					'id'				=> ToShortCode($m->suid1),
					'threadid'	=> $m->threadid,
					'body'			=> $m->bodyid,
					'from'			=> $m->fromid,
					'to'				=> $m->toid,
					'readtime'	=> $m->readtime,
					'ups'				=> $m->ups,
					'downs'			=> $m->downs,
					'flags'			=> $m->flags,
				];
				
				$bodyids[strval($m->bodyid)]	=	0;
				$userids[strval($m->fromid)]	= 0;
				$userids[strval($m->toid)]		= 0;
			}
			
			foreach ($D->Find('h_messagebody', 'WHERE suid1 IN (' . join(", ", $bodyids)) as $r)
			{
				foreach ($ls as $k => $v)
				{
					if ($r->suid1 == $v['body'])
					{
						$ls[$k]['title']			=	$r->title;
						$ls[$k]['body']				=	$r->message;
						$ls[$k]['senttime']		=	$r->senttime;
						
						break;
					}
				}
			}
			
			foreach ($D->Find(
					'h_user', 
					'WHERE suid1 IN (' . join(", ", $userids), 
					0, 
					[
						'fields'	=> 'suid1, phonenumber, username, realname',
					]
				) as $r)
			{
				foreach ($ls as $k => $v)
				{
					if ($r->suid1 == $v['from'])
					{
						$ls[$k]['from']			=	$r->Name();
						break;
					} else if ($r->suid1 == $v['to'])
					{
						$ls[$k]['to']			=	$r->Name();
						break;
					}
				}
			}
			
			$results['items']	=	$ls;
			break;
		case 'save':
			RequireLogin(1);
		
			$msgid			=	StrV($data, 'msgid');
			$parentid		=	StrV($data, 'parentid');
			$threadid		=	StrV($data, 'threadid');
			$toids			=	ArrayV($data, 'toids');
			$fileids		=	ArrayV($data, 'fileids');
			$flags			=	IntV($data, 'flags');
			
			if (!$toids)
			{
				$results['error']	=	$T_SYSTEM[48];
				break;
			}
			
			$files	=	[];
			
			if ($fileids)
			{
				foreach ($fileids as $fileid)
					$files[]	=	$D->Load('h_file', FromShortCode($fileid));
			}
			
			$bodies		=	[];
			$savedids	=	[];
			
			foreach ($toid as $toid)
			{
				$userid	=	FromShortCode($toid);
				$u			=	$D->Load('h_user', $userid, 'language');
				
				if (!V($bodies, $u->language))
				{
					$title	= StrV($data, 'title_' . $u->language);
					$msg		= StrV($data, 'message_' . $u->language);
					
					if (!$title && $u->language == $D->language)
					{
						$title	= StrV($data, 'title_translated');
						$msg		= StrV($data, 'message_translated');
					}
						
					if (!$title)
					{
						$title	= StrV($data, 'title');
						$msg		= StrV($data, 'message');
					}
						
					if (!$title || !$msg)
					{
						$results['error']	=	$T_SYSTEM[48];
						return;
					}
					
					$bodies[$u->language]	=	$D->Create($messagebodyclass)
						->Set([
							'title'		=> $title,
							'message'	=> $message,
						])->Insert();
				}
				
				$body	=	$bodies[$u->language];
				
				$msg	=	$msgid
					? $D->Load($messageclass, FromShortCode($msgid))
					: $D->Create($messageclass);
					
				$msg->Set([
					'uri'				=> StrV($data, 'uri'),
					'threadid'	=> $threadid ? FromShortCode($threadid) : 0,
					'parentid'	=> $parentid ? FromShortCode($parentid) : 0,
					'fromid'		=> $_SESSION['userid'],
					'toid'			=> $userid,
					'bodyid'		=> $body->suid1,
					'flags'			=> $flags,
				]);
				
				$msgid ? $msg->Update() : $msg->Insert();
				
				if ($files)
					$msg->Link($files);
				
				$savedids[]	=	ToShortCode($msg->suid1);
			}
			
			$results['items']	=	$savedids;
			break;
		default:
			$results['error']	=	'Unknown command: ' . $command;
	};
}

Run();
