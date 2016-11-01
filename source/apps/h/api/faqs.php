<?php

function Run()
{
	global $results, $pathargs, $D, $R;
	
	$data			=	json_decode(file_get_contents("php://input"), true);
	$command	=	StrV($pathargs, 0);
	
	if (!$data || !$command)
	{
		$results['error']	=	'Invalid data or command';
		return;
	}
	
	switch ($command)
	{
		case 'listtopics':
			$langs		=	ArrayV($data, 'langs');
			
			if (!$langs)
			{
				$results['error']	=	'Missing language';
				break;
			}
			
			$langnums	=	[];
			
			foreach ($langs as $lang)
				$langnums[$lang]	=	DBTranslator::CodeToID($lang);
		
			$D->FetchLanguages(array_values($langnums));
			
			$topics	=	[];
			
			foreach ($D->Find('h_faq', 
					'GROUP BY topic
					ORDER BY section'
				) as $r
			)
			{
				$a				=	[
					'topicid'	=>	ToShortCode($r->topic),
				];
				foreach (['section', 'question', 'answer'] as $varname)
				{
					$langnum	=	1;
					
					foreach ($langnums as $code => $num)
					{
						$translation			=	$varname . '_' . $num;
						$a[$varname . '_' . $langnum]	=	$r->$translation;
						$langnum++;
					}					
				}
				$topics[]	=	$a;
			}
			
			$results['items']		= $topics;
			break;
		case 'list':
			$topicid	=	StrV($data, 'topicid');
			$langs		=	ArrayV($data, 'langs');
			
			if (!$topicid)
			{
				$results['error']	=	'Missing topic ID';
				break;
			}
			
			if (!$langs)
			{
				$results['error']	=	'Missing language';
				break;
			}
			
			$langnums	=	[];
			
			foreach ($langs as $lang)
				$langnums[$lang]	=	DBTranslator::CodeToID($lang);
		
			$D->FetchLanguages(array_values($langnums));
			
			$items	=	[];
			
			foreach ($D->Find(
					'h_faq', 
					'WHERE topic = ?',
					FromShortCode($topicid)
				) as $r)
			{
				if (!$topicid)
					$topicid	=	ToShortCode($r->topic);
			
				$a				=	[
					'sectionid'		=>	$r->section ? ToShortCode($r->section) : '',
				];
				foreach (['section', 'question', 'answer'] as $varname)
				{
					$langnum	=	1;
					
					foreach ($langnums as $code => $num)
					{
						$translation			=	$varname . '_' . $num;
						$a[$varname . '_' . $langnum]	=	$r->$translation;
						$langnum++;
					}					
				}
				$items[]	=	$a;
			}
			
			$results['items']		=	$items;
			$results['topicid']	=	ToShortCode($r->topic);
			break;
		case 'save':
			$topicid		=	StrV($data, 'topicid');
			$sectionid	=	StrV($data, 'sectionid');
			$section		=	StrV($data, 'section');
			$question		=	StrV($data, 'question');
			$answer			=	StrV($data, 'answer');
			$lang				=	StrV($data, 'lang');
			
			if (!$lang)
			{
				$results['error']	=	'Missing language';
				break;
			}
			
			if ($section != '0' && !$section)
			{
				$results['error']	=	'Missing section';
				break;
			}
			
			if (!$question)
			{
				$results['error']	=	'Missing question';
				break;
			}
			
			$D->Debug(1);
			
			$lang	=	DBTranslator::CodeToID($lang);
		
			$topicid		=	$topicid ? FromShortCode($topicid) : 0;
			$sectionid	=	$sectionid ? FromShortCode($sectionid) : 0;
			
			$o	=	$D->Create('h_faq')
				->Set([
					'topic'							=>	$topicid,
					'section'						=>	$sectionid,
					'section_' . $lang	=>	$section,
					'question_'	. $lang	=>	$question,
					'answer_' . $lang		=>	$answer,
				]);		
			
			if (!$topicid)
				$o->Set(['topic_' . $lang => $question]);
			
			$o->Replace();
			
			$results['topicid']		=	ToShortCode($o->topic);
			$results['sectionid']	=	ToShortCode($o->section);
			break;
		case 'delete':
			$topicid		=	StrV($data, 'topicid');
			$sectionid	=	StrV($data, 'sectionid');
			
			if (!$topicid)
			{
				$results['error']	=	'Missing parameters.';
				break;
			}
			
			if ($sectionid)
			{
				$D->Delete(
					'h_faq', 
					'WHERE topic = ? AND section = ?',
					[FromShortCode($topicid), FromShortCode($sectionid)]
				);
			} else
			{
				$D->Delete(
					'h_faq', 
					'WHERE topic = ?',
					FromShortCode($topicid)
				);
			}
			break;
		default:
			$results['error']	=	'Unknown command';
	};
}

Run();
