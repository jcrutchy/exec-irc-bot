<?php

#####################################################################################################

ini_set("display_errors","on");

require_once("lib_buckets.php");
require_once("lib_http.php");
require_once("users_lib.php");

define("NICK_EXEC","exec");

define("DATA_PATH","../data/");

define("VALID_UPPERCASE","ABCDEFGHIJKLMNOPQRSTUVWXYZ");
define("VALID_LOWERCASE","abcdefghijklmnopqrstuvwxyz");
define("VALID_NUMERIC","0123456789");

# VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC

define("BUCKET_IGNORE_NEXT","<<BOT_IGNORE_NEXT>>");

#####################################################################################################

function parse_parameters($text,$delim="=",$sep=",")
{
  # param 1 = fluff, and stuff, param 2 = fart, param 3 = butt
  # "param 1"=>"fluff, and stuff","param 2"=>"fart","param 3"=>"butt"
  $results=array();
  $parts=explode($delim,$text);
  if (count($parts)==1)
  {
    return False;
  }
  $key=trim($parts[0]);
  for ($i=1;$i<(count($parts)-1);$i++)
  {
    $subparts=explode($sep,$parts[$i]);
    do
    {
      $next=trim(array_pop($subparts));
    }
    while (($next=="") and (count($subparts)>0));
    $results[$key]=trim(implode($sep,$subparts));
    $key=$next;
  }
  $results[$key]=trim(array_pop($parts));
  return $results;
}

#####################################################################################################

function load_settings($filename,$delim="=")
{
  if (file_exists($filename)==False)
  {
    term_echo("*** FILE NOT FOUND: $filename");
    return False;
  }
  $data=file_get_contents($filename);
  if ($data===False)
  {
    term_echo("*** ERROR READING FILE: $filename");
    return False;
  }
  $data=explode("\n",$data);
  $settings=array();
  for ($i=0;$i<count($data);$i++)
  {
    $parts=explode($delim,$data[$i]);
    if (count($parts)<>2)
    {
      continue;
    }
    $settings[trim($parts[0])]=trim($parts[1]);
  }
  return $settings;
}

#####################################################################################################

function save_settings(&$data,$filename,$delim="=")
{
  $content="";
  foreach ($data as $key => $value)
  {
    $content=$content.$key.$delim.$value."\n";
  }
  if (file_put_contents($filename,$content)===False)
  {
    return False;
  }
  else
  {
    return True;
  }
}

#####################################################################################################

function internal_macro($commands,$sleep=0)
{
  $n=count($commands);
  for ($i=0;$i<$n;$i++)
  {
    echo "/IRC :".NICK_EXEC." INTERNAL :".$commands[$i]."\n";
    if (($sleep>0) and ($i<($n-1)))
    {
      sleep($sleep);
    }
  }
}

#####################################################################################################

function exec_file_delete($filename)
{
  if (file_exists(DATA_PATH.$filename)==True)
  {
    unlink(DATA_PATH.$filename);
    if (file_exists(DATA_PATH.$filename)==False)
    {
      return True;
    }
  }
  return False;
}

#####################################################################################################

function exec_file_append($filename,$data)
{
  file_put_contents(DATA_PATH.$filename,$data."\n",FILE_APPEND);
}

#####################################################################################################

function exec_file_write($filename,$data)
{
  file_put_contents(DATA_PATH.$filename,implode("\n",$data));
}

#####################################################################################################

function exec_file_read($filename)
{
  $fn=DATA_PATH.$filename;
  if (file_exists($fn)==True)
  {
    $data=file_get_contents($fn);
    return explode("\n",$data);
  }
  return array();
}

#####################################################################################################

function irc_pause()
{
  echo "/BOT_IRC_PAUSE\n";
}

#####################################################################################################

function irc_unpause()
{
  echo "/BOT_IRC_UNPAUSE\n";
}

#####################################################################################################

function bot_ignore_next()
{
  set_bucket(BUCKET_IGNORE_NEXT,"1");
}

#####################################################################################################

function convert_timestamp($time,$format)
{
  $arr=date_parse_from_format($format,$time);
  return mktime($arr["hour"],$arr["minute"],$arr["second"],$arr["month"],$arr["day"],$arr["year"]);
}

#####################################################################################################

function delete_empty_elements(&$array)
{
  for ($i=0;$i<count($array);$i++)
  {
    if ($array[$i]=="")
    {
      unset($array[$i]);
    }
  }
  $array=array_values($array);
}

#####################################################################################################

function exec_is_integer($value)
{
  return ctype_digit(strval($value));
}

#####################################################################################################

function random_string($length)
{
  $legal=VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC;
  $result="";
  for ($i=0;$i<$length;$i++)
  {
    $result=$result.$legal[mt_rand(0,strlen($legal)-1)];
  }
  return $result;
}

#####################################################################################################

function term_echo($msg)
{
  echo "\033[34m$msg\033[0m\n";
}

#####################################################################################################

function privmsg($msg)
{
  echo "/PRIVMSG $msg\n";
}

#####################################################################################################

function action($msg)
{
  privmsg(chr(1)."ACTION $msg".chr(1));
}

#####################################################################################################

function rawmsg($msg)
{
  echo "/IRC $msg\n";
}

#####################################################################################################

function pm($nick,$msg)
{
  echo "/IRC :".NICK_EXEC." PRIVMSG $nick :$msg\n";
}

#####################################################################################################

function pm_action($nick,$msg)
{
  pm($nick,chr(1)."ACTION $msg".chr(1));
}

#####################################################################################################

function notice($nick,$msg)
{
  echo "/IRC :".NICK_EXEC." NOTICE $nick :$msg\n";
}

#####################################################################################################

function err($msg)
{
  term_echo($msg);
  die();
}

#####################################################################################################

function clean_text($text)
{
  $text=trim(replace_ctrl_chars($text," "));
  while (strpos($text,"  ")!==False)
  {
    $text=str_replace("  "," ",$text);
  }
  return trim($text);
}

#####################################################################################################

function strip_ctrl_chars($url)
{
  return replace_ctrl_chars($url,"");
}

#####################################################################################################

function replace_ctrl_chars($url,$replace)
{
  $url=str_replace("\t",$replace,$url);
  $url=str_replace("\n",$replace,$url);
  $url=str_replace("\r",$replace,$url);
  $url=str_replace("\0",$replace,$url);
  return str_replace("\x0B",$replace,$url);
}

#####################################################################################################

function extract_text($text,$delim1,$delim2,$delim2opt=False)
{
  $i=strpos(strtolower($text),strtolower($delim1));
  if ($i===False)
  {
    #term_echo("*** lib.php->extract_text: delim1 not found");
    return False;
  }
  $text=substr($text,$i+strlen($delim1));
  $i=strpos($text,$delim2);
  if ($i===False)
  {
    if ($delim2opt==True)
    {
      return trim($text);
    }
    else
    {
      #term_echo("*** lib.php->extract_text: required delim2 not found");
      return False;
    }
  }
  $text=substr($text,0,$i);
  return trim($text);
}

#####################################################################################################

function extract_text_nofalse($text,$delim1,$delim2,$delim2opt=False)
{
  $result=extract_text($text,$delim1,$delim2,$delim2opt);
  if ($result===False)
  {
    return "";
  }
  else
  {
    return $result;
  }
}

#####################################################################################################

function replace_first($search,$replace,$subject)
{
  $lsubject=strtolower($subject);
  $lsearch=strtolower($search);
  $n=count($search);
  $i=strpos($lsubject,$lsearch);
  if ($i===False)
  {
    return False;
  }
  $s1=substr($subject,0,$i);
  $s2=substr($subject,$i+strlen($search));
  return $s1.$replace.$s2;
}

#####################################################################################################

function is_valid_chars($value,$valid_chars)
{
  for ($i=0;$i<strlen($value);$i++)
  {
    if (strpos($valid_chars,$value[$i])===False)
    {
      return False;
    }
  }
  return True;
}

#####################################################################################################

function filter($value,$valid_chars)
{
  $result="";
  for ($i=0;$i<strlen($value);$i++)
  {
    if (strpos($valid_chars,$value[$i])!==False)
    {
      $result=$result.$value[$i];
    }
  }
  return $result;
}

#####################################################################################################

function filter_non_alpha_num($value)
{
  return filter($value,VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC);
}

#####################################################################################################

function devutils__CopyDirectoryFiles($SourceDirectory,$TargetDirectory,&$Content)
{
  $Content="<p>SourceDirectory=\"".htmlspecialchars($SourceDirectory)."\"";
  $Content=$Content."<br>\nTargetDirectory=\"".htmlspecialchars($TargetDirectory)."\"</p>\n";
  if ((file_exists($SourceDirectory)===True) and (file_exists($TargetDirectory)===True) and (is_dir($SourceDirectory)===True) and (is_dir($TargetDirectory)===True))
  {
    $SourceDirectoryHandle=opendir($SourceDirectory);
    $success_count=0;
    $failure_count=0;
    $Content=$Content."<table>\n";
    $Content=$Content."<tr><td><b>File</b></td><td><b>Status</b></td></tr>\n";
    while(($SourceFile=readdir($SourceDirectoryHandle))!==False)
    {
      $FullSourceFileName=$SourceDirectory."/".$SourceFile;
      $FullTargetFileName=$TargetDirectory."/".$SourceFile;
      if(($SourceFile!=".") and ($SourceFile!="..") and (is_dir($FullSourceFileName)===False))
      {
        $Content=$Content."<tr>\n";
        $Content=$Content."<td>".$SourceFile."</td>";
        if (copy($FullSourceFileName,$FullTargetFileName)===True)
        {
          $success_count=$success_count+1;
          $Content=$Content."<td><span style=\"color: green;\">SUCCESS</span></td>";
        }
        else
        {
          $failure_count=$failure_count+1;
          $Content=$Content."<td><span style=\"color: red;\">FAILURE</span></td>";
        }
        $Content=$Content."</tr>\n";
      }
    }
    closedir($SourceDirectoryHandle);
    $Content=$Content."</table>\n";
    $Content=$Content."<p>Succesfully copied $success_count files. Failed to copy $failure_count files.</p>\n";
    if ($failure_count>0)
    {
      return False;
    }
    else
    {
      return True;
    }
  }
  else
  {
    $Content=$Content."<p>Source and/or target directory not found.</p>\n";
    return False;
  }
}

#####################################################################################################

?>
