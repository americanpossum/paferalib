<?php

include_once('dbtranslator.php');

/* ********************************************************************
*/
function FakeValidator($name, $v)
{
}

/* ********************************************************************
*/
function NullValidator($name, $v)
{
	global $T;

	if (!isset($v))
		throw new Exception($name . ' cannot be NULL.');
}

/* ********************************************************************
*/
function EmptyValidator($name, $v)
{
	global $T;

	if (!isset($v) || !$v)
		throw new Exception($name . ' cannot be empty.');
}

/* ********************************************************************
*/
function EmailValidator($name, $v)
{
	global $T;

	if ($v)
	{
		if (!preg_match("/[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/i", $v))
		{
			throw new Exception($name . ' is not an email address.');
		}
	}
}

/* ********************************************************************
*/
function DateValidator($name, $v)
{
	global $T;

	if ($v)
	{
		if (date_create_from_format('Y-m-d', $v) === False)
		{
			throw new Exception($name . ' is not a date.');
		}
	}
}

/* ********************************************************************
*/
function DateTimeValidator($name, $v)
{
	global $T;

	if ($v)
	{
		if (date_create_from_format(DATE_ISO8601, $v) === False)
		{
			throw new Exception($name . ' is not a date and time.');
		}
	}
}

/* ********************************************************************
*/
function TimeValidator($name, $v)
{
	global $T;

	if ($v)
	{
		if (date_create_from_format('H:i:s', $v) === False)
		{
			if (date_create_from_format('H:i', $v) === False)
			{
				throw new Exception($name . ' is not a time.');
			}
		}
	}
}

/* ********************************************************************
*/
function RangeValidator($min, $max)
{
	global $T;

	return function($name, $v)
	{
		if ($v)
		{
			if ($v < $min)
			{
				throw new Exception($name . ' is too low.');
			} else if ($v > $max)
			{
				throw new Exception($name . ' is too high.');
			}
		}
	};
}

/* ********************************************************************
* Inherited models should provide a static variable named $DESC
* which can contain the following fields:
*		* $type				Class ID set by DB->Register().
*		*	$fields			The field definitions of the database table.
*
* The following fields are optional and will be initialized to
* defaults if not set.
*		*	$table			The name of the database table.
*		* $flags			Database flags for this model.
*		* $indexes		Any additional indexes to create for this model.
*/
class ModelBase
{
	// ------------------------------------------------------------------
	public function Count($cond = '', $params = [])
	{
		return $this->_db->Count(get_class($this), $cond, $params);
	}
	
	// ------------------------------------------------------------------
	public function Create()
	{
		return $this->_db->Create(get_class($this));
	}
	
	// ------------------------------------------------------------------
	public function Load($id, $fieldstoload = '*')
	{
		return $this->_db->Load(get_class($this), $id, $fieldstoload, $this);
	}
	
	// ------------------------------------------------------------------
	public function LoadMany($ids, $fieldstoload = '*')
	{
		return $this->_db->LoadMany(get_class($this), $id, $fieldstoload, $this);
	}
	
	// ------------------------------------------------------------------
	public function Find($cond = '', $params = [], $fieldstoget = '*', $callback = null)
	{
		return $this->_db->Find(get_class($this), $cond, $params, $fieldstoget, $callback);
	}
	
	// ------------------------------------------------------------------
	public function Insert($idmethod = 0)
	{
		$this->_db->Insert($this, $idmethod);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function Update($idmethod = 0)
	{
		$this->_db->Update($this, $idmethod);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function Save($insert = false, $idmethod = 0)
	{
		$this->_db->Save($this, $insert);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function Replace()
	{
		$this->_db->Replace($this);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function Delete($cond = '', $params = [])
	{
		$this->_db->Delete($this, $cond, $params);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function Link($o2, $type = 0, $num = 0, $comment = '')
	{
		$this->_db->Link($this, $o2, $type, $num, $comment);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function Unlink($o2, $type = 0)
	{
		$this->_db->Unlink($this, $o2, $type);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function UnlinkMany($model, $type = 0)
	{
		$this->_db->UnlinkMany($this, $model, $type);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function Linked($model2, $type = 0, $options = [])
	{
		return $this->_db->Linked($this, $model2, $type, $options);
	}
	
	// ------------------------------------------------------------------
	public function LinkedAll()
	{
		$ls	=	[];
		
		foreach ($this->_db->types as $model => $desc)
		{
			$objs	=	array_map(
				function($o)
				{
					return $o->ToArray();
				},
				$this->_db->Linked($this, $model)
			);
			
			if ($objs)
			{
				$ls[$model]	=	$objs;
			}
		}
		
		return $ls;
	}
	
	// ------------------------------------------------------------------
	public function MaxID()
	{
		return $this->_db->MaxID(get_class($this));
	}
	
	// ------------------------------------------------------------------
	public function Tag($tag)
	{
		$this->_db->Tag($this, $tag);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function Tags($language = 0)
	{
		return $this->_db->ListAllTags($this, $language);
	}
	
	// ------------------------------------------------------------------
	public function UnTag($tag)
	{
		$this->_db->UnTag($this, $tag);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function HasTag($tag)
	{
		return $this->_db->HasTag($this, $tag);
	}
	
	// ------------------------------------------------------------------
	public function SetProp($propname, $value)
	{
		$this->_db->SetProp($this, $propname, $value);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function GetProp($propname)
	{
		return $this->_db->GetProp($this, $propname);
	}
	
	// ------------------------------------------------------------------
	public function GetPermissions()
	{
		return $this->_db->GetPermissions($this);
	}
	
	// ------------------------------------------------------------------
	public function SetPermissions($owner, $access, $users, $groups)
	{
		$this->_db->SetPermissions($this, $owner, $access, $users, $groups);
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function ToArray()
	{
		$model						=	get_class($this);

		$a	=	[];
		$accessprotected	=	-1;

		$desc			=	$model::$DESC;
		$fields		=	$desc['fields'];
		$numsuids	=	$desc['numsuids'];

		foreach ($fields as $k => $v)
		{
			if (in_array($k, ['dbowner', 'dbaccess', 'acl'])
				||strpos($v[0], 'PRIVATE') !== false
			)
				continue;

			if (strpos($v[0], 'PROTECTED') !== false)
			{
				if ($accessprotected == -1)
					$accessprotected	=	$this->_db->CheckAccess($model, $this);

				if (!($accessprotected & DB::CAN_VIEW_PROTECTED))
					continue;
			}

			if (property_exists($this, $k))
				$a[$k]	=	$this->$k;

			if (false !== strpos($v[0], 'TRANSLATION'))
			{
				$translationname			=	$k . 's';
				$a[$translationname]	=	ArrayV($this, $translationname);
			}
		}

		if ($numsuids)
		{
			for ($i = 1; $i <= $numsuids; $i++)
			{
				$suidname	=	'suid' . $i;
				$a[$suidname]	=	$this->$suidname;
			}
		}
		
		if (property_exists($this, 'dblinktype'))
		{
			$a['dblinktype']	=	$this->dblinktype;
		}

		return $a;
	}

	// ------------------------------------------------------------------
	public function Changed()
	{
		return !!$this->_changed;
	}
	
	// ------------------------------------------------------------------
	public function Set($vars)
	{
		if (!is_array($vars))
			throw new Exception('ModelBase.Set: vars is not an array!');

		$model		=	get_class($this);
		$desc			=	$model::$DESC;
		$keys			=	array_keys($desc['fields']);
		$numsuids	=	$desc['numsuids'];

		if ($numsuids)
		{
			for ($i = 1; $i <= $numsuids; $i++)
			{
				$keys[]	=	'suid' . $i;
			}
		}

		foreach ($vars as $k => $v)
		{
			$set	=	isset($this->$k);

			if (!$set || $this->$k != $v)
			{
				$this->_changed[$k]	=	$set ? $this->$k : null;
				$this->$k	=	$v;
			}
		}
		
		return $this;
	}

	// ------------------------------------------------------------------
	public function __sleep()
	{
		$model		=	get_class($this);
		$desc			=	$model::$DESC;
		$keys			=	array_keys($desc['fields']);
		$numsuids	=	$desc['numsuids'];

		if ($numsuids)
		{
			for ($i = 1; $i <= $numsuids; $i++)
			{
				$keys[]	=	'suid' . $i;
			}
		}
		
		if (($this->flags & DB::SECURE) && ($this->_db->flags & DB::SECURE))
		{
			$keys[]	= 'dbowner';
			$keys[]	= 'dbaccess';
			$keys[]	= 'aclid';
		}

		return $keys;
	}
	
	// ------------------------------------------------------------------
	public function ToJSON()
	{
		$values	= [];
		
		foreach ($this->__sleep() as $k)
			$values[$k]	= $this->$k;
		
		return $values;
	}
	
	// ------------------------------------------------------------------
	public function OnLoad($db, $fields)
	{
		return $this;
	}

	// ------------------------------------------------------------------
	public function OnSave($db, $fields)
	{
		return $this;
	}

	// ------------------------------------------------------------------
	public function OnDelete($db)
	{
		return $this;
	}

	// ------------------------------------------------------------------
	public function PostSave($db, $fields)
	{
		return $this;
	}

	// ------------------------------------------------------------------
	public function OnSetPermissions($db, $owner, $access, $aclid)
	{
		return $this;
	}
	
	// ------------------------------------------------------------------
	public function CanChange()
	{
		return $this->_db->Access(get_class($this), $this) & DB::CAN_CHANGE;
	}
	
	// ------------------------------------------------------------------
	public function CanDelete()
	{
		return $this->_db->Access(get_class($this), $this) & DB::CAN_DELETE;
	}
}

