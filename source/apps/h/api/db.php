<?php

RequireGroup('admins');

// ====================================================================
function RunMethods($obj, $methods)
{
	global $results;

	$o	=	$obj->ToArray();

	foreach ($methods as $method)
	{
		if (is_array($method))
		{
			$methodname	=	$method[0];

			switch (count($method))
			{
				case 2:
					$o[$methodname]	=	$obj->$methodname($method[1]);
					break;
				case 3:
					$o[$methodname]	=	$obj->$methodname($method[1], $method[2]);
					break;
				case 4:
					$o[$methodname]	=	$obj->$methodname($method[1], $method[2], $method[3]);
					break;
				case 5:
					$o[$methodname]	=	$obj->$methodname($method[1], $method[2], $method[3], $method[4]);
					break;
			};

		} else
		{
			$o[$method]	=	$obj->$method();
		}
	}

	return $o;
}

// ====================================================================
function RunCommand($command)
{
	global $D, $T, $results;

	$runcommand	=	StrV($command, 'command');

	switch ($runcommand)
	{
		case 'query':
			RequireGroup('admins', true);

			$results[V($command, 'result')]	=	$D->Query(StrV($command, 'query'))->fetchAll();
			break;
		case 'eval':
			RequireGroup('admins', true);

			ob_start();
			var_dump(eval(StrV($command, 'code')));

			$results[V($command, 'result')]	=	ob_get_clean();
			break;
		case 'listtypes':
			$ls	=	[];

			foreach ($D->types as $t)
			{
				$ls[]	=	$t->typename;
			}
			$results[V($command, 'result')]	=	$ls;
			break;
		case 'fields':
			$model		=	StrV($command, 'model');

			if (!$D->IsRegistered($model))
			{
				$results['error']	=	'Model "' . $model . '" has not been registered.';
				break;
			}

			$results[$command['result']]	=	$D->GetModelFields($model);
			break;
		case 'load':
			$model		=	StrV($command, 'model');
			$ids			=	V($command, 'ids');
			$fields		=	StrV($command, 'fields');
			$methods	=	ArrayV($command, 'methods');

			if (!$D->IsRegistered($model))
			{
				$results['error']	=	'Model "' . $model . '" has not been registered.';
				break;
			}

			$obj	=	$D->Load($model, $ids, $fields);
			$a		=	RunMethods($obj, $methods);

			$results[V($command, 'result')]	=	$a;
			break;
		case 'loadmany':
			$model		=	StrV($command, 'model');
			$idslist	=	ArrayV($command, 'idslist');
			$fields		=	StrV($command, 'fields');
			$methods	=	ArrayV($command, 'methods');

			if (!$D->IsRegistered($model))
			{
				$results['error']	=	'Model "' . $model . '" has not been registered.';
				break;
			}

			$ls	=	[];

			foreach ($idslist as $ids)
			{
				$obj	=	$D->Load($model, $ids, $fields);
				$a		=	RunMethods($obj, $methods);

				$ls[]	=	$a;
			}

			$results[V($command, 'result')]	=	$ls;
			break;
		case 'find':
			$model		=	StrV($command, 'model');
			$cond			=	StrV($command, 'cond');
			$start		=	IntV($command, 'start');
			$limit		=	IntV($command, 'limit', 100);
			$orderby	=	StrV($command, 'orderby');
			$fields		=	StrV($command, 'fields', '*');
			$methods	=	ArrayV($command, 'methods');

			if (!$D->IsRegistered($model))
			{
				$results['error']	=	'Model "' . $model . '" has not been registered.';
				break;
			}

			if ($limit < 10)
			{
				$limit	=	10;
			} else if ($limit > 1000)
			{
				$limit	=	1000;
			}

			if ($orderby)
				$orderby	=	"ORDER BY {$orderby}";

			$items		=	[];

			foreach ($D->Find(
					$model,
					$cond . ' ' . $orderby,
					'',
					[	
						'start'		=>	$start,
						'limit'		=>	$limit,
						'fields'	=>	$fields,
					],
					$fields)
				as $obj
			)
			{
				$items[]	=	RunMethods($obj, $methods);
			}
			$results[V($command, 'result') . 'count']	=	$D->Count($model, $cond);
			$results[V($command, 'result')]	=	$items;
			break;
		case 'update':
			$model		=	StrV($command, 'model');
			$ids			=	ArrayV($command, 'ids');
			$data			=	ArrayV($command, 'data');

			if (!$D->IsRegistered($model))
			{
				$results['error']	=	'Model "' . $model . '" has not been registered.';
				break;
			}

			if (!$data)
			{
				$results['error']	=	'No data sent.';
				break;
			}

			$obj	=	$D->Load($model, $ids);

			$obj->Set($data);
			$D->Replace($obj);

			$results[V($command, 'result')]	=	$obj->ToArray();
			break;
		case 'save':
			$model		=	StrV($command, 'model');
			$data			=	ArrayV($command, 'data');

			if (!$D->IsRegistered($model))
			{
				$results['error']	=	'Model "' . $model . '" has not been registered.';
				break;
			}

			if (!$data)
			{
				$results['error']	=	'No data sent.';
				break;
			}

			$obj	=	$D->Create($model);
			$obj->Set($data);

			$D->Replace($obj);

			$results[V($command, 'result')]	=	$obj->ToArray();
			break;
		case 'savemany':
			$model		=	StrV($command, 'model');
			$data			=	ArrayV($command, 'data');

			if (!$D->IsRegistered($model))
			{
				$results['error']	=	'Model "' . $model . '" has not been registered.';
				break;
			}

			$l	=	count($data);
			$ls	=	[];

			for ($i = 0; $i < $l; $i++)
			{
				if (!$data[$i])
				{
					$results['error']	=	'No data sent.';
					break;
				}

				$obj	=	$D->Create($model);
				$obj->Set($data[$i]);
				$D->Replace($obj);

				$ls[]	=	$obj->ToArray();
			}

			$results[V($command, 'result')]	=	$ls;
			break;
		case 'savetranslation':
			$model					=	StrV($command, 'model');
			$ids						=	ArrayV($command, 'ids');
			$translations		=	ArrayV($command, 'translations');

			if (!$D->IsRegistered($model))
			{
				$results['error']	=	'Model "' . $model . '" has not been registered.';
				break;
			}

			if (!$model || !$ids || !$translations)
			{
				$results['error']	=	'Missing model, ids, or translations.';
				break;
			}

			$obj	=	$D->Load($model, $ids);
			$obj->Set($translations);
			$D->Replace($obj);

			$results[V($command, 'result')]	=	$obj->ToArray();
			break;
		case 'delete':
			$model		=	StrV($command, 'model');
			$ids			=	V($command, 'ids');
			$cond			=	StrV($command, 'cond');

			if (!$D->IsRegistered($model))
			{
				$results['error']	=	'Model "' . $model . '" has not been registered.';
				break;
			}

			if ($ids)
			{
				$obj	=	$D->Load($model, $ids);
				$D->Delete($obj);
			} else
			{
				$obj	=	$D->Delete($model, $cond);
			}

			break;
		case 'link':
		case 'linkarray':
			$model1		=	StrV($command, 'model1');
			$id1			=	V($command, 'id1');
			$model2		=	StrV($command, 'model2');
			$id2			=	V($command, 'id2');
			$type			=	IntV($command, 'linktype');
			$num			=	IntV($command, 'num');
			$comment	=	StrV($command, 'comment');
			
			if (!$D->IsRegistered($model1))
			{
				$results['error']	=	'Model "' . $model1 . '" has not been registered.';
				break;
			}

			if (!$D->IsRegistered($model2))
			{
				$results['error']	=	'Model "' . $model2 . '" has not been registered.';
				break;
			}

			$obj1	=	$D->Load($model1, $id1);

			if ($runcommand == 'linkarray')
			{
				if ($id2)
				{
					if (!is_array($id2))
					{
						$id2	=	[$id2];
					}
					
					$obj2	=	$D->LoadMany($model2, $id2);

					$D->Link($obj1, $obj2, $type, $num, $comment);
				} else
				{
					$D->UnlinkMany($obj1, $model2, $type);
					break;
				}
			} else
			{
				$obj2	=	$D->Load($model2, $id2);
				$D->Link($obj1, $obj2, $type, $num, $comment);
			}
			break;
		case 'linked':
			$model1			=	StrV($command, 'model1');
			$id1				=	V($command, 'id1');
			$model2			=	StrV($command, 'model2');
			$type				=	IntV($command, 'linktype');
			$orderby		=	StrV($command, 'orderby');
			$fields			=	StrV($command, 'fields', '*');
			$limit			=	IntV($command, 'limit');
			$methods		=	ArrayV($command, 'methods');

			if (!$D->IsRegistered($model1))
			{
				$results['error']	=	'Model "' . $model1 . '" has not been registered.';
				break;
			}

			if (!$D->IsRegistered($model2))
			{
				$results['error']	=	'Model "' . $model2 . '" has not been registered.';
				break;
			}

			$obj	=	$D->Load($model1, $id1);

			$ls	=	[];

			foreach ($D->Linked($obj, $model2, $type, $fields, $limit, $orderby) as $o)
			{
				$a		=	RunMethods($o, $methods);

				$a['dblinktype']				=	$o->dblinktype;

				$ls[]										=	$a;
			}

			$results[V($command, 'result')]	=	$ls;
			break;
		case 'linkedmany':
			$model1			=	StrV($command, 'model1');
			$id1s				=	ArrayV($command, 'id1s');
			$model2			=	StrV($command, 'model2');
			$type				=	IntV($command, 'linktype');
			$orderby		=	StrV($command, 'orderby');
			$fields			=	StrV($command, 'fields', '*');
			$limit			=	IntV($command, 'limit');
			$methods		=	ArrayV($command, 'methods');

			if (!$D->IsRegistered($model1))
			{
				$results['error']	=	'Model "' . $model1 . '" has not been registered.';
				break;
			}

			if (!$D->IsRegistered($model2))
			{
				$results['error']	=	'Model "' . $model2 . '" has not been registered.';
				break;
			}

			$returnedobjs	=	[];
			$idname1			=	$model1::$ID;

			foreach ($id1s as $id1)
			{
				$obj	=	$D->Load($model1, $id1);

				$ls	=	[];

				foreach ($D->Linked($obj, $model2, $type, $fields, $limit) as $o)
				{
					$a								=	RunMethods($o, $methods);
					$a['dblinktype']	=	$o->dblinktype;
					$ls[]							=	$members;
				}

				if ($orderby)
				{
					usort(
						$ls,
						function($a, $b)
						{
							return strcmp($a['orderby'], $b['orderby']);
						}
					);
				}

				$returnedobjs[$obj->$idname1]	=	$ls;
			}

			$results[V($command, 'result')]	=	$returnedobjs;
			break;
		case 'unlink':
			$model1		=	StrV($command, 'model1');
			$id1			=	V($command, 'id1');
			$model2		=	StrV($command, 'model2');
			$id2			=	V($command, 'id2');
			$type			=	IntV($command, 'linktype');

			if (!$D->IsRegistered($model1))
			{
				$results['error']	=	'Model "' . $model1 . '" has not been registered.';
				break;
			}

			if (!$D->IsRegistered($model2))
			{
				$results['error']	=	'Model "' . $model2 . '" has not been registered.';
				break;
			}

			$obj1	=	$D->Load($model1, $id1);
			$obj2	=	$D->Load($model2, $id2);

			if (isset($id1['dblinktype']))
				$type	=	$id1['dblinktype'];

			if (isset($id2['dblinktype']))
				$type	=	$id2['dblinktype'];

			$D->Unlink($obj1, $obj2, $type);
			break;
		case 'unlinkmany':
			$model1		=	StrV($command, 'model1');
			$ids1			=	V($command, 'ids1');
			$model2		=	StrV($command, 'model2');
			$type			=	IntV($command, 'linktype');

			if (!$D->IsRegistered($model1))
			{
				$results['error']	=	'Model "' . $model1 . '" has not been registered.';
				break;
			}

			if (!$D->IsRegistered($model2))
			{
				$results['error']	=	'Model "' . $model2 . '" has not been registered.';
				break;
			}

			$obj1	=	$D->Load($model1, $ids1);
			$D->UnlinkMany($obj1, $model2, $type);
			break;
		default:
			$results['error']	=	'Unknown command: ' . V($command, 'command');
	};
}

$data			=	json_decode(file_get_contents("php://input"), true);
$commands	=	ArrayV($data, 'commands');

if (!$data || !$commands)
	throw new Exception('Invalid data sent.');

$D->languagestofetch	=	array_keys(DBTranslator::$LANGUAGES);

foreach ($commands as $command)
{
	RunCommand($command);

	if (V($results, 'error'))
		break;
}
