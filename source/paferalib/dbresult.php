<?php

/* ********************************************************************
 * Convenience class for the database. This handles security,
 * retrieving by chunks instead of all at once, and caching of 
 * large result sets.
 */
class DBResult implements Iterator
{
	// ------------------------------------------------------------------
	public function __construct($db, $table, $query, $countquery, $params, $options)
	{
		global $C;
	
		$this->db						=	$db;
		$this->query				=	$query;
		$this->params				=	$params;
		
		$this->limit				= IntV($options, 'limit', 20, 20, 2147483647);
		$this->cachesize		= IntV($options, 'cachesize', 0, 100, 2147483647);
		$this->chunksize		= IntV($options, 'chunksize', 10000, 100, 100000);
		
		foreach ($options as $k => $v)
			$this->$k	=	$v;
			
		$this->index			=	0;
		$this->processed	=	0;
		$this->obj				=	0;
		$this->cache			=	[];
		$this->count			=	$db->Query($countquery, $params)->fetch()['COUNT(*)'];
		$this->chunk			=	0;
		
		if ($this->cachesize)
		{
			$results		= [];
			$queries		= [];
			$queryinfo	= 0;
			
			$encodedparams	=	json_encode($params);
			
			if (V($options, 'userunique'))
				$encodedparams	.= 'User' . $db->userid;
		
			if (is_file('data/querycache'))
			{
				$queries				= json_decode(file_get_contents('data/querycache'), 1);
				$queryinfo			= V($queries, $query . $encodedparams);
				
				if ($queryinfo)
				{
					$previouscount	= $queryinfo['count'];
					$cachefile			= 'querycache/' . $queryinfo['id'];
					
					if ($previouscount
						&& $previouscount == $this->count
						&& $C->IsFresh($cachefile)
					)
					{
						$results	= unserialize($C->Read($cachefile));
					}
				}
			}
			
			# Retrieve new results if the old ones are missing or don't include
			# the desired rows
			if (!$results || ($this->start + $this->limit) > count($results))
			{
				$start	=	$this->start;
				$limit	=	$this->limit;
				
				if (($start + $this->limit) > $this->cachesize)
				{
					# If we need to go past the set cache size, round up to the next chunk
					# for efficiency
					$this->limit	= ceil($this->start / $this->chunksize) * $this->chunksize;
				} else
				{
					$this->limit	= $this->cachesize;
				}
				
				$this->index			= 0;
				
				$count		=	0;
				
				$this->conn			=	$db->Query($query . ' LIMIT ' . $this->index . ', ' . $this->chunksize, $params);
				$cache		=	$this->All();
				$this->cache	= $cache;
				
				$this->start		=	$start;
				$this->limit		=	$limit;
				
				if (!$queryinfo)
				{
					$oldids	= [];
					
					foreach ($queries as $q)
						$oldids[]	= $q['id'];
				
					for (;;)
					{
						$newid	= MakeSUID();
						
						if (!array_search($newid, $oldids))
							break;
					}
					
					$queries[$query . $encodedparams]	= [
						'id'		=> $newid,
						'count'	=> $this->count,
					];
					
					$cachefile			= 'querycache/' . $newid;
					
					@file_put_contents('data/querycache', json_encode($queries, JSON_UNESCAPED_UNICODE));
				}
				
				@$C->Write($cachefile, serialize($this->cache));
			} else
			{
				$this->cache	= $results;
			}
		} else
		{
			$this->chunk			=	floor($this->start / $this->chunksize);
			$this->chunksize	=	IntV($this, 'limit', $this->limit, 20, 100000);
			$this->conn				=	$db->Query($query . ' LIMIT ' . $this->index . ', ' . $this->chunksize, $params);
		}
		
		$this->index	=	$this->start;
#		echo "{$this->index}, {$this->start}, {$this->limit}, {$this->chunksize}, {$this->cachesize}";
	}

	// ------------------------------------------------------------------
	public function __destruct()
	{
		$this->Close();
	}
	
	// ------------------------------------------------------------------
	public function Close()
	{
		if (isset($this->conn))
			$this->conn->closeCursor();
	}
	
	// ------------------------------------------------------------------
	public function rewind()
	{
		$this->next();
	}
	
	// ------------------------------------------------------------------
	public function key()
	{
		return $this->index;
	}
	
	// ------------------------------------------------------------------
	public function current()
	{
		return $this->obj;
	}
	
	// ------------------------------------------------------------------
	public function next()
	{
		if ($this->cache)
		{
			if ($this->start && $this->index < $this->start)
				$this->index	= $this->start;
			
			if ($this->limit && $this->index > ($this->start + $this->limit))
			{
				$this->obj	=	0;
				return;
			}
					
			$this->obj	=	V($this->cache, $this->index);
			$this->index++;
			return;
		}
	
		for (;;)
		{
			// Get a new chunk if necessary
			if ($this->chunksize && $this->index >= (($this->chunk + 1) * $this->chunksize))
			{
				$this->conn->closeCursor();
				$this->chunk++;
				$this->conn	=	$this->db->Query($this->query . ' LIMIT ' . $this->index . ', ' . $this->chunksize, $this->params);
			}
			
			$row	=	$this->conn->fetch();
			$this->processed++;
			
			if (!$row)
			{
				$this->obj	=	0;
				break;
			}

			if ($this->usesecurity 
				&& !$this->isadmin 
				&& $row['dbowner'] != $this->db->userid
				&& !($this->db->Access($this->model, $row) & $this->access)
			)
			{
				continue;
			}

			$this->index++;
			
			if ($this->start && $this->index < $this->start)
				continue;
			
			if ($this->limit && $this->index > ($this->start + $this->limit))
			{
				$this->obj	=	0;
				break;
			}
			
			$obj	=	new $this->model();
			$obj->_db	=	$this->db;

			if ($this->usesecurity)
			{
				$obj->dbowner		=	$this->db->userid ? $this->db->userid : 1;
				$obj->dbaccess	=	0;
				$obj->aclid			=	0;
			}

			$this->db->ImportFields($this->model, $obj, $row);

			if ($this->trackaccess)
				$this->db->AddChangeLog($this->model, $obj, h_changelog::ACCESSED, '');

			$this->obj	=	$obj;
			
			if ($this->db->flags & DB::DEBUG)
			{
				echo "ROW: \n";
				print_r($obj->ToJSON());
				echo "\n\n";
			}
			break;
		}
	}

	// ------------------------------------------------------------------
	public function valid()
	{
		return $this->obj;
	}

	// ------------------------------------------------------------------
	public function One()
	{
		$this->next();
		return $this->obj;
	}
	
	// ------------------------------------------------------------------
	public function All()
	{
		$ls	=	[];
		
		for (;;)
		{
			$this->next();
			
			if (!$this->obj)
				break;
		
			$ls[]	=	$this->obj;
		}
		
		return $ls;
	}
}
