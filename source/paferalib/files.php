<?php

class PFDir
{
  const ONLY_DIRECTORIES  = 0x01;
  const ONLY_FILES        = 0x02;
  const FOLLOW_LINKS      = 0x04;

  // ------------------------------------------------------------------
  function __construct($path)
  {
    if (!$path)
      throw new Exception('PFDir(): No path provided');
  
    $this->path = $path;
    
    if (!realpath($this->path))
      throw new Exception('PFDir(): Invalid path provided: ' . $path . ' -> ' . $this->path);
  }
  
  // ------------------------------------------------------------------
  function Scan($pattern = '', $flags = 0, $path = '')
  {
		$path	=	$path ? $path : $this->path;
	
    if (!is_readable($path))
      throw new Exception('PFDir.Scan: ' . $path . ' is not readable!');

    $dir   = opendir($path);

    if (!$dir)
      throw new Exception("Could not list directory {$dirname}.");

    $dirs   = [];
    $files  = [];
    $isdir  = false;
    
    while ($filename = readdir($dir))
    {
      if ($filename != "." && $filename != "..")
      {
        $fullpath = $path . '/' . $filename;
        
        if (is_link($fullpath) 
            && !($flags & self::FOLLOW_LINKS) 
          )
          continue;
        
        $isdir  = is_dir($fullpath);
      
        if ($pattern)
        {
          if (preg_match($pattern, $filename))
          {
            $isdir ? $dirs[]  = $filename : $files[]  = $filename;
          }
        } else
        {
          if ($flags & self::ONLY_DIRECTORIES && $isdir)
          {
            $dirs[] = $filename;
          } else if ($flags & self::ONLY_FILES && !$isdir)
          {
            $files[] = $filename;
          } else
          {
            $isdir ? $dirs[]  = $filename : $files[]  = $filename;
          }
        }
      }
    }

    closedir($dir);
    
    return [$dirs, $files];
  }

  // ------------------------------------------------------------------
  function Walk($callback) 
  {
    if (!$callback) 
      throw new Exception('FileDir.Walk: No callback specified!');
      
    $this->ReallyWalk($this->path, $callback);
    return True;
  }

  // ------------------------------------------------------------------
  function ReallyWalk($path, $callback) 
  {
    list($dirs, $files) = $this->Scan('', 0, $path);
    
    $callback($path, $dirs, $files);
    
    foreach ($dirs as $d)
		{
      $this->ReallyWalk($path . '/' . $d, $callback);
		}
  }
};

function FileDir($path)
{
  return new PFDir($path);
}

/* *******************************************************
Returns the last line of a file, assuming that your lines
are smaller than the buffer size.
******************************************************** */
function LastLine($filename, $buffersize = 1024)
{
  if (!is_file($filename))
    return "[File doesn't exist]";
  
  $startat     = filesize($filename) - $buffersize;
  
  if ($startat < 0)
    $startat  = 0;
  
  $f        = fopen($filename, "r");
  fseek($f, -$buffersize);
  $lastline = '';
  
  for (;;)
  {
    $line = fgets($f);
    
    if ($line === false)
      break;
      
    if ($line)
      $lastline = $line;
  }
  
  fclose($f);
  return $lastline;
}

