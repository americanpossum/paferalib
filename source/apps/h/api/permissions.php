<?php

RequireLogin(1);

// ====================================================================
function Run()
{
	global $results, $pathargs, $D;

	$data			=	json_decode(file_get_contents("php://input"), true);
	$command	=	StrV($pathargs, 0);
	
	$model		=	StrV($data, 'model');
	$objtype	=	IntV($data, 'objtype');
	$objid		=	V($data, 'objid');
	
	if ((!$model && !$objtype) || !$objid)
	{
		$results['error']	=	'Missing object type or id';
		return;
	}
	
	if (!$model)
	{
		foreach ($D->types as $cls => $desc)
		{
			if ($objtype == $desc['suid1'])
			{
				$model	=	$cls;
				break;
			}
		}
				
		if (!$model)
		{
			$results['error']	=	'No model found with type ' . $objtype;
			return;
		}
	}
	
	switch ($command)
	{
		case 'get':
			$a	=	$D->Load($model, $objid)->GetPermissions();
			$a['ownername']	=	$D->Load('h_user', $a['owner'])->Username();
			
			if (V($a, 'users'))
			{
				$users	=	[];
			
				foreach ($D->Find(
					'h_user',
					'WHERE suid1 IN (' . join(', ', array_keys($a['users'])) . ')'
					) as $u
				)
				{
					$users[]	=	[
						'id'			=>	$u->suid1, 
						'name'		=>	$u->Username(), 
						'access'	=>	$a['users'][$u->suid1]
					];
				}
				
				$a['users']	=	$users;
			} else
			{
				$a['users']	=	[];
			}
			
			if (V($a, 'groups'))
			{
				$groups	=	[];
			
				foreach ($D->Find(
					'h_group',
					'WHERE suid1 IN (' . join(', ', array_keys($a['groups'])) . ')'
					) as $g
				)
				{
					$groups[]	=	[
						'id'			=>	$u->suid1, 
						'name'		=>	$u->Name(), 
						'access'	=>	$a['groups'][$u->suid1]
					];
				}
				
				$a['groups']	=	$groups;
			} else
			{
				$a['groups']	=	[];
			}
			
			$results['item']	=	$a;
			break;
		case 'set':
			$owner			=	IntV($data, 'owner');
			$ownername	=	StrV($data, 'ownername');
			$users			=	ArrayV($data, 'users');
			$groups			=	ArrayV($data, 'groups');
			
			if (!$owner && !$ownername)
			{
				$results['error']	=	'Missing owner.';
				return;
			}
			
			if ($ownername)
			{
				$results	=	$D->Query(
					'SELECT suid1 
					FROM h_users
					WHERE phonenumber = ?',
					$ownername
				)->fetch();
				
				if ($results)
				{
					$owner	=	$results['suid1'];
				} else
				{
					$results	=	$D->Query(
						'SELECT suid1 
						FROM h_users
						JOIN h_translations ON h_users.username = h_translations.textid
						WHERE app = ? AND collection = ? AND translation = ?',
						['h_user', 'username', $ownername]
					)->fetch();
					
					if ($results)
					{
						$owner	=	$results['suid1'];
					} else
					{
						throw new Exception('Could not find owner ' . $ownername);
					}
				}
			}
			
			$newusers	=	[];
			
			foreach ($users as $u)
			{
				$id			=	IntV($u, 'id');
				$name		=	StrV($u, 'name');
				$access	=	IntV($u, 'access');
				
				if ($id)
				{
					$newusers[$id]	=	$access;
					break;
				} 
				
				$results	=	$D->Query(
					'SELECT suid1 
					FROM h_users
					WHERE phonenumber = ?',
					$name
				)->fetch();
				
				if ($results)
				{
					$newusers[$results['suid1']]	=	$access;
					break;
				}
				
				$results	=	$D->Query(
					'SELECT suid1 
					FROM h_users
					JOIN h_translations ON h_users.username = h_translations.textid
					WHERE app = ? AND collection = ? AND translation = ?',
					['h_user', 'username', $name]
				)->fetch();
				
				if ($results)
				{
					$newusers[$results[0]['suid1']]	=	$access;
					break;
				} else
				{
					throw new Exception('Could not find user ' . $name);
				}
			}
		
			$newgroups	=	[];
			
			foreach ($groups as $u)
			{
				$id			=	IntV($u, 'id');
				$name		=	StrV($u, 'name');
				$access	=	IntV($u, 'access');
				
				if ($id)
				{
					$newgroups[$id]	=	$access;
					break;
				} 
				
				$results	=	$D->Query(
					'SELECT suid1 
					FROM h_groups
					WHERE groupname = ?',
					$name
				)->fetch();
				
				if ($results)
				{
					$newgroups[$results['suid1']]	=	$access;
					break;
				}
				
				throw new Exception('Could not find group ' . $name);
			}
			
			$D->Load($model, $objid)->SetPermissions($owner, IntV($data, 'access'), $newusers, $newgroups)->Save();
			break;
		default:
			$results['error']	=	'Unknown command: ' . $command;
	};
}


$R->IncludeAllModels();
set_time_limit(0);
Run();
