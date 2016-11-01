<?php

function Run()
{
	global $D, $pathargs, $results, $T, $R;

	$command	=	StrV($pathargs, 0);
	$data			=	json_decode(file_get_contents("php://input"), true);

	switch ($command)
	{
		case 'listlanguages':
			$results['items']	=	DBTranslator::$LANGUAGES;
			break;
		case 'setlang':
			$code	=	StrV($data, 'langcode');

			if (!$code)
			{
				echo '{"error":"No language code specified"}';
				break;
			}
		
			$id	=	$T->CodeToID($code);

			if (!$id)
			{
				$results['error']	=	'The language code ' . $code . ' does not exist';
				break;
			}

			$_SESSION['lang']			=	$id;
			$_SESSION['langcode']	=	$code;
			break;
		case 'load':
			$collection	=	StrV($data, 'collection');
			$langcode		=	StrV($data, 'langcode', $D->langcode);

			if (!$collection)
			{
				$results['error']	=	'Missing params.';
				break;
			}

			$results['items']	= $T->Load($collection, $langcode);
			break;
		case 'listapps':
			$apps	=	$R->apps;
			$results['count']	=	count($apps);
			$results['items']	=	$apps;
			break;
		case 'listcollections':
			$appname	=	StrV($data, 'appname');
			$langcode		=	StrV($data, 'langcode', $D->langcode);

			$collections	=	$T->ListCollections($appname, $langcode);
			$results['count']	=	count($collections);
			$results['items']	=	$collections;
			break;
		case 'translations':
			$collection		=	StrV($data, 'collection');
			$langcode1		=	StrV($data, 'langcode1');
			$langcode2		=	StrV($data, 'langcode2');
			
			if (!$collection || !$langcode1 || !$langcode2)
			{
				$results['error']	=	'Missing arguments';
				break;
			}

			$ls1	=	$T->Load($collection, $langcode1)->data;

			try
			{
				$ls2	=	$T->Load($collection, $langcode2)->data;
			} catch (Exception $e)
			{
				$ls2	=	[];
			}

			$results['items'] =	[
				$langcode1	=>	$ls1,
				$langcode2	=>	$ls2,
			];
			break;
		case 'save':
			RequireGroup(['admins', 'translators'], true);
			
			$collection		=	StrV($data, 'collection');
			$translations	=	ArrayV($data, 'translations');
			$langcode			=	StrV($data, 'langcode', $D->langcode);

			if (!$collection || !$translations || !$langcode)
			{
				$results['error']	=	'Missing arguments';
				break;
			}
			
			$T->Save($collection, $translations, $langcode);
			break;	
		case 'saveone':
			RequireGroup(['admins', 'translators'], true);
			
			$collection		=	StrV($data, 'collection');
			$textid				=	IntV($data, 'textid');
			$translation	=	StrV($data, 'translation');
			$langcode			=	StrV($data, 'langcode', $D->langcode);
			
			if (!$collection || !$translation || !$langcode)
			{
				$results['error']	=	'Missing arguments';
				break;
			}
			
			$translations	=	$T->Load($collection, $langcode);
			
			$translations[$textid]	=	$translation;

			$T->Save($collection, $translations, $langcode);
			break;	
		case 'create':
		case 'rename':
			RequireGroup(['admins', 'translators'], true);
			$collection		=	StrV($data, 'collection');
			$newname			=	StrV($data, 'newname');
			
			if (($command == 'rename' && !$collection) || !$newname)
			{
				$results['error']	=	'Missing arguments';
				break;
			}
			
			if ($command == 'rename')
			{
				$T->RenameCollection($collection, $newname);
			} else
			{
				$T->CreateCollection($newname);
			}
			break;
		case 'delete':
			RequireGroup(['admins', 'translators'], true);
			$collection		=	StrV($data, 'collection');
			
			if (!$collection)
			{
				$results['error']	=	'Missing arguments';
				break;
			}
			
			$T->DeleteCollection($newname);
			break;
		case 'verify':
			$D->Begin();
			foreach ($D->Find('h_translation') as $t)
			{
				if (!$D->Query("SELECT {$t->collection}
						FROM {$t->app}s
						WHERE {$t->collection} = ?",
						$t->textid
					)->fetch()
				)
				{
					$D->Query("DELETE FROM h_translations
						WHERE app = ? AND collection = ? AND textid = ?",
						[$t->app, $t->collection, $t->textid]
					);
				}
			}
			$D->Commit();
			break;
		default:
			$results['error']	=	'Unknown command';
	};
}

Run();
