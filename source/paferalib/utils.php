<?php

const UPPER_CASE  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
const LOWER_CASE  = 'abcdefghijklmnopqrstuvwxyz';
const NUMBERS     = '0123456789';
const ALPHA       = UPPER_CASE . LOWER_CASE;
const ALNUM       = NUMBERS . UPPER_CASE . LOWER_CASE;
const SHORTCODE   = ALNUM . '_-';

function MissingArgs($data, $neededargs)
{
  $valid  = [];

  foreach ($neededargs as $key)
  {
    if (!V($data, $key))
      throw new Exception('Missing ' . $key);
      
    $valid[]  = $data[$key];
  }
  
  return $valid;
}

function MakeSUID()
{
  for (;;)
  {
    $r  = mt_rand(-2147483648, 2147483647);

    if ($r)
      return $r;
  }
}

function ToShortCode($i)
{
  $i  = intval($i);
  $s  = [];
  $l  = strlen(SHORTCODE) - 1;

  for ($shift = 0; $shift <= 30; $shift += 6)
  {
    $s[]  = SHORTCODE[($i >> $shift) & $l];
  }

  return join('', $s);
}

function FromShortCode($s)
{
  $s      = strval($s);
  $result = 0;
  $l      = strlen(SHORTCODE) - 1;
  $strl   = strlen($s);

  for ($i = 0; $i < $strl; $i++)
    $result  +=  strpos(SHORTCODE, $s[$i]) << ($i * 6);

  // Do some additional work for 64-bit versions of PHP
  $result = $result & 0xffffffff;

  if ($result > 2147483647)
    $result = -2147483648 + ($result - 2147483647 - 1);

  return $result;
}

function SUIDToShortCodes($suids)
{
  $results  = [];

  foreach ($suids as $s)
  {
    $results[]  = ToShortCode($s);
  }

  return $results;
}

function ShortCodeToSUID($shortcodes)
{
  $results  = [];

  foreach ($shortcodes as $s)
  {
    $results[]  = FromShortCode($s);
  }

  return $results;
}

# Thanks to http://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
function UUID4()
{
    $data = openssl_random_pseudo_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function RandSeq($arr, $num = 1)
{
    $keys = array_keys($arr);
    shuffle($keys);

    $r = array();
    for ($i = 0; $i < $num; $i++) {
        $r[$keys[$i]] = $arr[$keys[$i]];
    }
    return $r;
}

// ====================================================================
function ChooseOne($a)
{
  return $a[rand(0, count($a) - 1)];
}

// ====================================================================
function Average($a)
{
  return array_sum($a) / count($a);
}

// ====================================================================
function ShuffleArray($ls)
{
  if (!is_array($ls))
    return $ls;

  $keys = array_keys($ls);
  shuffle($keys);

  $random = array();

  foreach ($keys as $key)
    $random[$key] = $ls[$key];

  return $random;
}

// ====================================================================
function KeyFromValue($a, $v)
{
  return array_keys($a, $v)[0];
}

// ====================================================================
function Bound($v, $min = 0, $max = 99999999)
{
  if ($v < $min)
    $v  = $min;
    
  if ($v > $max)
    $v  = $max;
    
  return $v;
}

// ====================================================================
function First($a)
{
  foreach ($a as $k => $v)
    return $v;
}

// ====================================================================
function Last($a)
{
  $lastv  = 0;

  foreach ($a as $k => $v)
    $lastv  = $v;
  
  return $lastv;
}

// ====================================================================
function DefaultValue($a, $keys)
{
  foreach ($keys as $k)
  {
    if (isset($a[$k]))
      return $a[$k];
  }
  
  return First($a);
}

// ====================================================================
// Utility function to avoid PHP sprouting warnings about missing
// array keys
function V($a, $k, $d = NULL)
{
  if (is_object($a))
  {
    $a  = (array)$a;
  }

  if (!is_array($a))
  {
    debug_print_backtrace();
    throw new Exception(var_export($a, true) . ' is not an array!');
  }

  if (isset($a) && array_key_exists($k, $a))
  {
    return $a[$k];
  }

  return $d;
}

// ====================================================================
function IntV($a, $k, $d = 0, $min = -1, $max = -1)
{
  $v  = intval(V($a, $k, $d));

  if ($min != -1 && $v < $min)
    $v  = $min;

  if ($max != -1 && $v > $max)
    $v  = $max;

  return $v;
}

// ====================================================================
function FloatV($a, $k, $d = 0.0, $min = -1, $max = -1)
{
  $v  = floatval(V($a, $k, $d));

  if ($min != -1 && $v < $min)
    $v  = $min;

  if ($max != -1 && $v > $max)
    $v  = $max;

  return $v;
}

// ====================================================================
function StrV($a, $k, $d = '')
{
  return strval(V($a, $k, $d));
}

// ====================================================================
function ArrayV($a, $k, $d = [])
{
  $ar = V($a, $k, $d);

  if ($ar && !is_array($ar))
    $ar = [$ar];

  return $ar;
}

// ====================================================================
function TimeV($a, $k, $d = 0)
{
  $v = V($a, $k, $d);

  if (!preg_match('#^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$#', $v))
    return $d;

  return $v;
}

// ====================================================================
function SameArray($a, $b)
{
  if (!is_array($a) || !is_array($b))
    return false;

  $keysa  = array_keys($a);
  $keysb  = array_keys($b);
  
  if (array_diff($keysa, $keysb))
    return false;
    
  foreach ($keysa as $k)
  {
    if (is_array($a[$k]))
    {
      if (!SameArray($a[$k], $b[$k]))
        return false;
    } else
    {
      if ($a[$k] != $b[$k])
        return false;
    }
  }

  return true;
}

// Original PHP code by Chirp Internet: www.chirp.com.au
// Please acknowledge use of this code by including this header.
function BlowFish($input, $rounds = 7)
{
  $salt = "";
  $salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
  for ($i = 0; $i < 22; $i++)
  {
    $salt .= $salt_chars[array_rand($salt_chars)];
  }
  return crypt($input, sprintf('$2a$%02d$', $rounds) . $salt);
}

function EscapeSQL($s)
{
  return str_replace(["'", '"'], ["''", '""'], $s);
}

// Checks an uploaded file for errors. Returns the path to the uploaded file
function CheckUpload($filename, $sizelimit = 1024 * 1024 * 10)
{
  if (!isset($_FILES[$filename]['error'])
    || is_array($_FILES[$filename]['error']))
    throw new RuntimeException('Invalid parameters.');

  switch ($_FILES[$filename]['error'])
  {
    case UPLOAD_ERR_OK:
      break;
    case UPLOAD_ERR_NO_FILE:
     throw new RuntimeException('No file sent.');
   case UPLOAD_ERR_INI_SIZE:
   case UPLOAD_ERR_FORM_SIZE:
     throw new RuntimeException('Exceeded filesize limit.');
   default:
     throw new RuntimeException('Unknown errors.');
  }

  if ($_FILES[$filename]['size'] > $sizelimit)
    throw new RuntimeException('Exceeded filesize limit.');

  $extension    = pathinfo($_FILES[$filename]['tmp_name'], PATHINFO_EXTENSION);
  $newfilename  = sprintf('./uploads/%s.%s', uuid4(), $extension);

  if (!move_uploaded_file($_FILES[$filename]['tmp_name'], $newfilename))
    throw new RuntimeException('Failed to move uploaded file.');

  return $newfilename;
}

# Thanks to http://blog-en.openalfa.com/how-to-read-and-write-json-files-in-php/
function code2utf($num){
    if($num<128)
        return chr($num);
    if($num<1024)
          return chr(($num>>6)+192).chr(($num&63)+128);
    if($num<32768)
        return chr(($num>>12)+224).chr((($num>>6)&63)+128)
              .chr(($num&63)+128);
    if($num<2097152)
        return chr(($num>>18)+240).chr((($num>>12)&63)+128)
                .chr((($num>>6)&63)+128).chr(($num&63)+128);
    return '';
}

function unescape($strIn, $iconv_to = 'UTF-8') {
    $strOut = '';
    $iPos = 0;
    $len = strlen ($strIn);
    while ($iPos < $len) {
        $charAt = substr ($strIn, $iPos, 1);
        if ($charAt == '\'') {
            $iPos++;
            $charAt = substr ($strIn, $iPos, 1);
            if ($charAt == 'u') {
                // Unicode character
                $iPos++;
                $unicodeHexVal = substr ($strIn, $iPos, 4);
                $unicode = hexdec ($unicodeHexVal);
                $strOut .= code2utf($unicode);
                $iPos += 4;
            }
            else {
                // Escaped ascii character
                $hexVal = substr ($strIn, $iPos, 2);
                if (hexdec($hexVal) > 127) {
                    // Convert to Unicode
                    $strOut .= code2utf(hexdec ($hexVal));
                }
                else {
                    $strOut .= chr (hexdec ($hexVal));
                }
                $iPos += 2;
            }
        }
        else {
            $strOut .= $charAt;
            $iPos++;
        }
    }
    if ($iconv_to != "UTF-8") {
        $strOut = iconv("UTF-8", $iconv_to, $strOut);
    }
    return $strOut;
}

// Thanks to http://stackoverflow.com/questions/9802033/json-encode-and-replacement-for-json-unescaped-unicode
// Custom JSON encoder for human readability and unescaped Unicode characters
function EncodeJSON($data)
{
  switch ($type = gettype($data))
  {
    case 'NULL':
      return 'null';
    case 'boolean':
      return ($data ? 'true' : 'false');
    case 'integer':
    case 'double':
    case 'float':
      return $data;
    case 'string':
      return '"' . str_replace('"', '\"', $data) . '"';
    case 'object':
      $data = get_object_vars($data);
    case 'array':
      $output_index_count = 0;
      $output_indexed     = array();
      $output_associative = array();

      foreach ($data as $key => $value)
      {
        $output_indexed[]     = EncodeJSON($value);

        switch (gettype($key))
        {
          case 'integer':
          case 'double':
          case 'float':
            $key  = '"' . $key . '"';
            break;
          default:
            $key  = EncodeJSON($key);
        };
        $output_associative[] =  $key . ':' . EncodeJSON($value);

        if ($output_index_count !== NULL && $output_index_count++ !== $key)
          $output_index_count = NULL;
      }
      if ($output_index_count !== NULL)
        return '[' . implode(",\n", $output_indexed) . ']';

      return '{' . implode(",\n", $output_associative) . '}';
    default:
        return ''; // Not supported
  }
}

// Gives a path for an ID separated into subdirectories by thousands
// for better performance and FTP lookup on free hosting plans that
// won't support more than 1000 files per directory.
function IDToPath($id)
{
  $path     = strval($id);

  if (strlen($path) < 12)
  {
    for ($i = strlen($path); $i < 12; $i++)
      $path = '0' . $path;
  }

  $dirname  = substr($path, 0, 3) . '/' . substr($path, 3, 3) . '/' . substr($path, 6, 3);;
  $filename = substr($path, 9, 3);

  return [$dirname, $filename, $dirname . '/' . $filename, $path];
}

// Gives a path for an ID separated into subdirectories for better
// performance and organization
function IDToAlnumPath($id)
{
  $path     = ToShortCode($id);

  if (strlen($path) < 6)
    for ($i = strlen($path); $i < 6; $i++)
      $path = $path . '0';

  $dirname  = substr($path, 0, 3);
  $filename = substr($path, 3, 3);

  return [$dirname, $filename, $dirname . '/' . $filename, $path];
}

function GMTToLocal($gmt)
{
  $timestamp  = strtotime($gmt);
  $offset     = IntV($_COOKIE, 'timeoffset');
  return date('c', $timestamp + $offset * 60);
}

function LocalToGMT($local)
{
  $timestamp  = strtotime($local);
  $offset     = IntV($_COOKIE, 'timeoffset');
  return date('c', $timestamp - $offset * 60);
}

function ToUnsigned($n)
{
  return strval(floatval($n) + 2147483648);
}

function ToSigned($n)
{
  return strval(floatval($n) - 2147483648);
}

// Thanks to PHPCoder at niconet2k dot com ¶
function ToBase($numberInput, $fromBaseInput, $toBaseInput)
{
  if ($fromBaseInput == $toBaseInput) return $numberInput;

  $fromBase = str_split($fromBaseInput,1);
  $toBase = str_split($toBaseInput,1);
  $number = str_split($numberInput,1);
  $fromLen=strlen($fromBaseInput);
  $toLen=strlen($toBaseInput);
  $numberLen=strlen($numberInput);
  $retval='';

  if ($toBaseInput == NUMBERS)
  {
    $retval=0;
    for ($i = 1;$i <= $numberLen; $i++)
        $retval = bcadd($retval, bcmul(array_search($number[$i-1], $fromBase),bcpow($fromLen,$numberLen-$i)));
    return $retval;
  }

  if ($fromBaseInput != NUMBERS)
      $base10 = convBase($numberInput, $fromBaseInput, NUMBERS);
  else
      $base10 = $numberInput;

  if ($base10 < strlen($toBaseInput))
      return $toBase[$base10];

  while ($base10 != '0')
  {
      $retval = $toBase[bcmod($base10,$toLen)].$retval;
      $base10 = bcdiv($base10,$toLen,0);
  }
  return $retval;
}


// ====================================================================
// Thanks to emanueledelgrande ad email dot it
function global_include($script_path)
{
	// check if the file to include exists:
	if (isset($script_path) && is_file($script_path))
	{
		// extract variables from the global scope:
		extract($GLOBALS, EXTR_REFS);
		ob_start();
		include_once($script_path);
		return ob_get_clean();
	} else
	{
		ob_clean();
		trigger_error('The script to parse in the global scope was not found');
	}
}
