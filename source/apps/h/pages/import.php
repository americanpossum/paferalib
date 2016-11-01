<?php

echo '<pre>';

$db->TrackChanges(false);
$db->TrackValues(false);
$db->Begin();

$data	=	json_decode(file_get_contents('users.json'), true);

foreach ($data as $id => $user)
{
	if ($id == 1)
		continue;

	$newuser			=	$db->Create('User');
	$newstudent		=	$db->Create('Teach_Student');
	$achievement	=	$db->Create('Teach_Achievement');
	
	$user['wallpaper']	=	'blue';
	$user['texttheme']	=	'dark';
	$user['tuition']		=	$user['balance'];
	$user['dbowner']		=	$id;
	$user['userid']			=	$id;
	$user['password']		=	$user['username'];
	
	switch ($user['referredbyid'])
	{
		case 1:
			$user['referredbyid']	=	100;
			break;
		case 87:
			$user['referredbyid']	=	102;
			break;
		case 88:
			$user['referredbyid']	=	103;
			break;
		case 89:
			$user['referredbyid']	=	104;
			break;
	}
	
	$newuser->Assign($user);
	$newstudent->Assign($user);
	$achievement->Assign([
		'userid'			=>	$id,
		'activityid'	=>	1,
		'eventtime'		=>	DB::Date(),
		'score'				=>	$user['score'],
	]);
	
	try
	{
		$db->Replace($newuser);
		$db->Replace($newstudent);
		$db->Replace($achievement);			
	} catch (Exception $e)
	{
		echo "Teach_Problem processing user {$id}: {$e}";
	}
}

$data	=	json_decode(file_get_contents('classes.json'), true);

foreach ($data as $id => $cls)
{
	$newclass		=	$db->Create('Teach_Class');	
	$newclass->Assign([
		'id'				=>	$id,
		'title'			=>	$cls['classname'],
		'teacherid'	=>	11,
		'dbowner'		=>	11,
		'dbaccess'	=>	DB::CANNOT_CHANGE | DB::CANNOT_DELETE | DB::CANNOT_VIEW,
	]);
	
	try
	{
		$db->Replace($newclass);
	} catch (Exception $e)
	{
		echo "Teach_Problem processing invoice {$id}: {$e}";
	}
	
	$users	=	[];
	
	foreach ($cls['students'] as $userid)
	{
		$users[]	=	$db->Load('User', $userid);
	}
	
	$db->Link($newclass, $users);
}

$db->Delete('Teach_ClassSession');

$data	=	json_decode(file_get_contents('sessions.json'), true);

foreach ($data as $id => $o)
{
	$newinvoice		=	$db->Create('Teach_ClassSession');
	$newinvoice->Assign($o);
	$newinvoice->Assign([
		'teacherid'	=>	11,
		'dbowner'		=>	11,
		'dbaccess'	=>	DB::CANNOT_CHANGE | DB::CANNOT_DELETE | DB::CANNOT_VIEW,
	]);
	
	try
	{
		$db->Replace($newinvoice);
	} catch (Exception $e)
	{
		echo "Teach_Problem processing invoice {$id}: {$e}";
	}
}

$db->Delete('Teach_Invoice');

$data	=	json_decode(file_get_contents('invoices.json'), true);

foreach ($data as $id => $invoice)
{
	$newinvoice		=	$db->Create('Teach_Invoice');
	
	if ($invoice['userid'] == 1)
		$invoice['userid']	=	100;
	
	$newinvoice->Assign($invoice);
	$newinvoice->Assign([
		'dbowner'		=>	11,
		'dbaccess'	=>	DB::CANNOT_CHANGE | DB::CANNOT_DELETE | DB::CANNOT_VIEW,
		'amount'		=>	$invoice['amount'],
	]);
	
	switch ($invoice['titleid'])
	{
		// Attended class
		case 1:
			$newinvoice->Assign([
				'data1'		=>	$invoice['sessionid'],
				'data2'		=>	$invoice['score'],
			]);
			break;
		// Large class deduction
		case 2:
			$newinvoice->Assign([
				'data1'		=>	$invoice['sessionid'],
			]);
			break;
		// Referral deduction
		case 3:
			$newinvoice->Assign([
				'data1'		=>	$invoice['sessionid'],
				'data2'		=>	$invoice['referralid'],
			]);
			break;
		// Payment
		case 4:
			$newinvoice->Assign([
				'data1'		=>	$invoice['paymentmethod'],
			]);
			break;
		default:
			echo "No action found for invoice titleid {$invoice['titleid']}\n";
	};
	
	try
	{
		$db->Replace($newinvoice);
	} catch (Exception $e)
	{
		echo "Teach_Problem processing invoice {$id}: {$e}";
	}
}

$data					=	json_decode(file_get_contents('phrases.json'), true);
$soundid			=	1;
$maxphraseid	=	0;

foreach ($data as $id => $phrase)
{
	$id	=	intval($id);
	
	$results	=	$db->Find('Teach_Phrase', 'WHERE languageid = ? AND content = ?', [$phrase['language'], $phrase['phrase']]);
	
	if ($results)
	{
		$newphrase		=	$results[0];
		echo "Found previous phrase {$phrase['phrase']}\n";
	} else
	{	
		$newphrase		=	$db->Create('Teach_Phrase');
	}
	
	$phrase['id']					=	null;
	$phrase['languageid']	=	$phrase['language'];
	$phrase['content']		=	$phrase['phrase'];
	$phrase['dbowner']		=	11;
	$phrase['dbaccess']		=	DB::CANNOT_CHANGE | DB::CANNOT_DELETE;
	
	try
	{
		$from	=	"/srv/http/80/sounds/phrases/{$id}.mp3";
	
		if (is_readable($from))
		{
			$newpath	=	sprintf('%09d', intval($soundid));
			
			$dirname	=	'/srv/http/52001/sounds/' . substr($newpath, 0, 3) . '/' . substr($newpath, 3, 3);
			
			if (!is_dir($dirname))
				mkdir($dirname, 0777, true);
			
			$to				=	$dirname . '/' . substr($newpath, 6, 3) . '.mp3';
			
			copy($from, $to);
			
			$phrase['soundid']		=	$soundid;
			$soundid++;			
		}
	} catch (Exception $e)
	{
		echo "Teach_Problem copying sound file for phrase {$id}: {$e}";
	}
	
	$newphrase->Assign($phrase);
	
	if ($newphrase->languageid == 1 && !$newphrase->soundid)
		continue;
	
	try
	{
		$db->Replace($newphrase);
	} catch (Exception $e)
	{
		echo "Teach_Problem processing phrase {$id}: {$e}";
	}	
}

$data					=	json_decode(file_get_contents('vocabulary.json'), true);

foreach ($data as $id => $phrase)
{
	$id	=	intval($id);
	
	$results	=	$db->Find('Teach_Phrase', 'WHERE languageid = ? AND content = ?', [$phrase['language'], $phrase['word']]);
	
	if ($results)
	{
		$newphrase		=	$results[0];
		echo "Found previous phrase {$phrase['word']}\n";
	} else
	{	
		$newphrase		=	$db->Create('Teach_Phrase');
	}
	
	$phrase['id']					=	null;
	$phrase['languageid']	=	$phrase['language'];
	$phrase['content']		=	$phrase['word'];
	$phrase['dbowner']		=	11;
	$phrase['dbaccess']		=	DB::CANNOT_CHANGE | DB::CANNOT_DELETE;
	
	try
	{
		$from	=	"/srv/http/80/sounds/words/{$id}.mp3";
	
		if (is_readable($from))
		{
			$pathparts	=	IDToPath($soundid);
			
			$dirname	=	'/srv/http/52001/sounds/' . $pathparts[0];
			
			if (!is_dir($dirname))
				mkdir($dirname, 0777, true);
			
			$to				=	$dirname . '/' . $pathparts[1] . '.mp3';
			
			copy($from, $to);
			
			$phrase['soundid']		=	$soundid;
			$soundid++;			
		} 
	} catch (Exception $e)
	{
		echo "Teach_Problem copying sound file for phrase {$id}: {$e}";
	}
	
	$newphrase->Assign($phrase);
	
	if ($newphrase->languageid == 1 && !$newphrase->soundid)
		continue;
	
	try
	{
		$db->Replace($newphrase);
	} catch (Exception $e)
	{
		echo "Teach_Problem processing phrase {$id}: {$e}";
	}	
}

$data				=	json_decode(file_get_contents('lessons.json'), true);
$imageid		=	1;
$problemid	=	1;

$chineselessons	=	[];
$englishlessons	=	[];

foreach ($data as $id => $lesson)
{
	if (!$lesson['problems'])
		continue;

	$englishlesson		=	$db->Create('Teach_Lesson');
	$chineselesson		=	$db->Create('Teach_Lesson');
	
	$englishlesson->Assign($lesson);	
	$englishlesson->Assign(
			[
				'title'				=>	$lesson['englishtitle'],
				'description'	=>	$lesson['englishdescription'],
				'lessonid'		=>	$id,
				'language1id'	=>	1,
				'language2id'	=>	2,
				'dbowner'			=>	11,
				'dbaccess'		=>	DB::CANNOT_CHANGE | DB::CANNOT_DELETE,
			]
	);
	
	echo "Processing lesson {$lesson['englishtitle']}\n";
	
	$chineselesson->Assign(
			[
				'title'				=>	$lesson['chinesetitle'],
				'description'	=>	$lesson['chinesedescription'],
				'lessonid'		=>	$id,
				'language1id'	=>	2,
				'language2id'	=>	1,
				'dbowner'			=>	11,
				'dbaccess'		=>	DB::CANNOT_CHANGE | DB::CANNOT_DELETE,
			]
	);
	
	try
	{
		$db->Replace($englishlesson);
		$db->Replace($chineselesson);
		
		$englishlessons[$id]	=	$englishlesson;
		$chineselessons[$id]	=	$chineselesson;
	} catch (Exception $e)
	{
		echo "Teach_Problem processing lesson {$id}: {$e}";
	}
	
	$englishproblems	=	[];
	$chineseproblems	=	[];
	
	$firstimageid			=	0;
	
	foreach ($lesson['problems'] as $o)
	{
		try
		{
			$phrase1	=	$db->Find('Teach_Phrase', 'WHERE content = ?', $o['phrase1'])[0];
			$phrase2	=	$db->Find('Teach_Phrase', 'WHERE content = ?', $o['phrase2'])[0];
		} catch (Exception $e)
		{
			continue;
		}
		
		echo "  Adding phrase {$phrase1->content}\n";
	
		$englishproblem	=	$db->Create('Teach_Problem');
		$chineseproblem	=	$db->Create('Teach_Problem');
		
		$englishproblem->Assign([
			'problemid'		=>	$phrase1->id,
			'answerid'		=>	$phrase2->id,
			'dbowner'			=>	11,
			'dbaccess'		=>	DB::CANNOT_CHANGE | DB::CANNOT_DELETE,
		]);
		$chineseproblem->Assign([
			'problemid'		=>	$phrase2->id,
			'answerid'		=>	$phrase1->id,
			'dbowner'			=>	11,
			'dbaccess'		=>	DB::CANNOT_CHANGE | DB::CANNOT_DELETE,
		]);
		
		if ($o['imageid'])
		{
			$from	=	"/srv/http/80/images/flashcards/{$o['imageid']}.jpg";
			
			if (is_readable($from))
			{
				$pathparts	=	IDToPath($imageid);
				
				$dirname	=	'/srv/http/52001/images/flashcards/' . $pathparts[0];
				
				if (!is_dir($dirname))
					mkdir($dirname, 0777, true);
				
				$to				=	$dirname . '/' . $pathparts[1] . '.jpg';
				
				copy($from, $to);
				
				$phrase1->Assign(['imageid'	=>	$imageid]);
				$phrase2->Assign(['imageid'	=>	$imageid]);
				
				$englishproblem->Assign([
					'problemimageid'	=>	$imageid,
					'answerimageid'		=>	$imageid,
				]);
				$chineseproblem->Assign([
					'problemimageid'	=>	$imageid,
					'answerimageid'		=>	$imageid,
				]);
				
				if (!$firstimageid)
					$firstimageid	=	$imageid;
				
				$imageid++;			
			} else
			{
				echo "Image {$from} is not readable!\n";
			}
		}		
		
		$db->Replace($englishproblem);
		$db->Replace($chineseproblem);
		
		$englishproblems[]	=	$englishproblem;
		$chineseproblems[]	=	$chineseproblem;
		
		$problemid++;
		
		try
		{
			$db->Replace($phrase1);
			$db->Replace($phrase2);
		} catch (Exception $e)
		{
			echo "Teach_Problem saving lesson {$id}: {$e}";
		}
		
		try
		{
			$db->Link($phrase1, $phrase2, 2);
			$db->Link($phrase2, $phrase1, 1);
		} catch (Exception $e)
		{
			echo "Teach_Problem linking phrases: " . $e->getMessage();
		}
	}
	
	if ($englishproblems)
		$db->Link($englishlesson, $englishproblems);
		
	if ($chineseproblems)
		$db->Link($chineselesson, $chineseproblems);
		
	if ($firstimageid)
	{
		$englishlesson->Assign(['imageid'	=>	$firstimageid]);
		$chineselesson->Assign(['imageid'	=>	$firstimageid]);
		$db->Replace($englishlesson);
		$db->Replace($chineselesson);
	}
}

$data				=	json_decode(file_get_contents('courses.json'), true);

foreach ($data as $id => $course)
{
	if (!$course['lessons'])
		continue;

	$englishcourse		=	$db->Create('Teach_Course');
	$chinesecourse		=	$db->Create('Teach_Course');
	
	$englishcourse->Assign(
			[
				'title'				=>	$course['englishtitle'],
				'courseid'		=>	$id,
				'language1id'	=>	1,
				'language2id'	=>	2,
				'teacherid'		=>	11,
				'dbowner'			=>	11,
				'dbaccess'		=>	DB::CANNOT_CHANGE | DB::CANNOT_DELETE,
			]
	);
	
	$chinesecourse->Assign(
			[
				'title'				=>	$course['chinesetitle'],
				'courseid'		=>	$id,
				'language1id'	=>	2,
				'language2id'	=>	1,
				'dbowner'			=>	11,
				'dbaccess'		=>	DB::CANNOT_CHANGE | DB::CANNOT_DELETE,
			]
	);
	
	try
	{
		$db->Replace($englishcourse);
		$db->Replace($chinesecourse);
	} catch (Exception $e)
	{
		echo "Teach_Problem processing course {$id}: {$e}";
	}
	
	$english	=	[];
	$chinese	=	[];
	
	$firstimageid	=	0;
	
	foreach ($course['lessons'] as $lessonid)
	{
		if (array_key_exists($lessonid, $englishlessons))
		{
			$english[]	=	$englishlessons[$lessonid];
			
			if ($englishlessons[$lessonid]->imageid && !$firstimageid)
				$firstimageid	=	$englishlessons[$lessonid]->imageid;
		}
			
		if (array_key_exists($lessonid, $chineselessons))
			$chinese[]	=	$chineselessons[$lessonid];
	}
	
	if ($english)
		$db->Link($englishcourse, $english);
		
	if ($chinese)
		$db->Link($chinesecourse, $chinese);
		
	if ($firstimageid)
	{
		$englishcourse->Assign(['imageid'	=>	$firstimageid]);
		$chinesecourse->Assign(['imageid'	=>	$firstimageid]);
		$db->Replace($englishcourse);
		$db->Replace($chinesecourse);
	}
}

$db->Commit();

echo "Finished importing</pre>";
exit();
