<?php

#####################################################################################################

define("ICEWEASEL_UA","Mozilla/5.0 (X11; Linux x86_64; rv:24.0) Gecko/20140429 Firefox/24.0 Iceweasel/24.5.0");

$url_blacklist=array("kidd","porn","goat","xxx","sex","fuck");

#####################################################################################################

function authorization_header_value($uname,$passwd,$prefix)
{
  return "$prefix ".base64_encode("$uname:$passwd");
}

#####################################################################################################

function shorten_url($url)
{
  if ($url=="")
  {
    return False;
  }
  $params=array();
  $params["url"]=$url;
  $response=wpost("o.my.to","/","80",ICEWEASEL_UA,$params,"",30);
  $short_url=trim(strip_headers($response));
  if ($short_url<>"")
  {
    return $short_url;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function lowercase_tags($html)
{
  $tags=explode("<",$html);
  for ($i=0;$i<count($tags);$i++)
  {
    $parts=explode(">",$tags[$i]);
    if (count($parts)==2)
    {
      $tags[$i]=strtolower($parts[0]).">".$parts[1];
    }
  }
  return implode("<",$tags);
}

#####################################################################################################

function check_url($url)
{
  global $url_blacklist;
  $lower_url=strtolower($url);
  for ($i=0;$i<count($url_blacklist);$i++)
  {
    if (strpos($lower_url,$url_blacklist[$i])!==False)
    {
      term_echo("*** blacklisted URL detected ***");
      return False;
    }
  }
  return True;
}

#####################################################################################################

function wtouch($host,$uri,$port,$timeout=5)
{
  if (check_url($host.$uri)==False) # check url against blacklist
  {
    return False;
  }
  $errno=0;
  $errstr="";
  if ($port==80)
  {
    $fp=@fsockopen($host,80,$errno,$errstr,$timeout);
  }
  elseif ($port==443)
  {
    $fp=@fsockopen("ssl://$host",443,$errno,$errstr,$timeout);
  }
  else
  {
    $fp=@fsockopen($host,$port,$errno,$errstr,$timeout);
  }
  if ($fp===False)
  {
    return False;
  }
  fwrite($fp,"GET $uri HTTP/1.0\r\nHost: $host\r\nConnection: Close\r\n\r\n");
  $response=fgets($fp,256);
  fclose($fp);
  return trim($response);
}

#####################################################################################################

function get_host_and_uri($url,&$host,&$uri,&$port)
{
  $url=trim($url);
  $comp=parse_url($url);
  $host="";
  if (isset($comp["host"])==True)
  {
    if ($comp["host"]<>"")
    {
      $host=$comp["host"];
    }
  }
  if ($host=="")
  {
    return False;
  }
  $port=80;
  if (isset($comp["scheme"])==True)
  {
    if ($comp["scheme"]=="https")
    {
      $port=443;
    }
  }
  $uri="/";
  if (isset($comp["path"])==True)
  {
    $uri=$comp["path"];
  }
  if (isset($comp["query"])==True)
  {
    if ($comp["query"]<>"")
    {
      $uri=$uri."?".$comp["query"];
    }
  }
  if (isset($comp["fragment"])==True)
  {
    if ($comp["fragment"]<>"")
    {
      $uri=$uri."#".$comp["fragment"];
    }
  }
  return True;
}

#####################################################################################################

function get_redirected_url($from_url,$url_list="")
{
  $url=trim($from_url);
  if ($url=="")
  {
    return False;
  }
  $comp=parse_url($url);
  $host="";
  if (isset($comp["host"])==False)
  {
    if (is_array($url_list)==True)
    {
      if (count($url_list)>0)
      {
        $host=parse_url($url_list[count($url_list)-1],PHP_URL_HOST);
        $scheme=parse_url($url_list[count($url_list)-1],PHP_URL_SCHEME);
        $url=$scheme."://".$host.$url;
      }
    }
  }
  else
  {
    $host=$comp["host"];
  }
  if ($host=="")
  {
    term_echo("redirect without host: ".$url);
    return False;
  }
  $uri="/";
  if (isset($comp["path"])==True)
  {
    $uri=$comp["path"];
  }
  if (isset($comp["query"])==True)
  {
    if ($comp["query"]<>"")
    {
      $uri=$uri."?".$comp["query"];
    }
  }
  if (isset($comp["fragment"])==True)
  {
    if ($comp["fragment"]<>"")
    {
      $uri=$uri."#".$comp["fragment"];
    }
  }
  $port=80;
  if (isset($comp["scheme"])==True)
  {
    if ($comp["scheme"]=="https")
    {
      $port=443;
    }
  }
  if (($host=="") or ($uri==""))
  {
    return False;
  }
  $headers=whead($host,$uri,$port,ICEWEASEL_UA,"",10);
  $location=trim(exec_get_header($headers,"location",False));
  if ($location=="")
  {
    return $url;
  }
  else
  {
    if (is_array($url_list)==True)
    {
      if (in_array($location,$url_list)==True)
      {
        return False;
      }
      else
      {
        $list=$url_list;
        $list[]=$url;
        return get_redirected_url($location,$list);
      }
    }
    else
    {
      $list=array($url);
      return get_redirected_url($location,$list);
    }
  }
}

#####################################################################################################

function whead($host,$uri,$port=80,$agent=ICEWEASEL_UA,$extra_headers="",$timeout=20)
{
  if (check_url($host.$uri)==False) # check url against blacklist
  {
    return "";
  }
  $errno=0;
  $errstr="";
  if ($port==443)
  {
    $fp=@fsockopen("ssl://$host",443,$errno,$errstr,$timeout);
  }
  else
  {
    $fp=@fsockopen($host,$port,$errno,$errstr,$timeout);
  }
  if ($fp===False)
  {
    $msg="Error connecting to \"$host\".";
    term_echo($msg);
    return $msg;
  }
  $headers="HEAD $uri HTTP/1.0\r\n";
  $headers=$headers."Host: $host\r\n";
  if ($agent<>"")
  {
    $headers=$headers."User-Agent: $agent\r\n";
  }
  if ($extra_headers<>"")
  {
    foreach ($extra_headers as $key => $value)
    {
      $headers=$headers.$key.": ".$value."\r\n";
    }
  }
  $headers=$headers."Connection: Close\r\n\r\n";
  fwrite($fp,$headers);
  $response="";
  while (!feof($fp))
  {
    $response=$response.fgets($fp,1024);
  }
  fclose($fp);
  return $response;
}

#####################################################################################################

function wget_ssl($host,$uri,$agent=ICEWEASEL_UA,$extra_headers="")
{
  return wget($host,$uri,443,$agent,$extra_headers);
}

#####################################################################################################

function wget($host,$uri,$port=80,$agent=ICEWEASEL_UA,$extra_headers="",$timeout=20,$breakcode="",$chunksize=1024,$check_url=True)
{
  if ($check_url==True)
  {
    if (check_url($host.$uri)==False) # check url against blacklist
    {
      return "";
    }
  }
  $errno=0;
  $errstr="";
  if ($port==443)
  {
    $fp=@fsockopen("ssl://$host",443,$errno,$errstr,$timeout);
  }
  else
  {
    $fp=@fsockopen($host,$port,$errno,$errstr,$timeout);
  }
  if ($fp===False)
  {
    $msg="Error connecting to \"$host\".";
    term_echo($msg);
    return $msg;
  }
  $headers="GET $uri HTTP/1.0\r\n";
  $headers=$headers."Host: $host\r\n";
  if ($agent<>"")
  {
    $headers=$headers."User-Agent: $agent\r\n";
  }
  if ($extra_headers<>"")
  {
    foreach ($extra_headers as $key => $value)
    {
      $headers=$headers.$key.": ".$value."\r\n";
    }
  }
  $headers=$headers."Connection: Close\r\n\r\n";
  #var_dump($headers);
  fwrite($fp,$headers);
  $response="";
  while (!feof($fp))
  {
    $response=$response.fgets($fp,$chunksize);
    if ($breakcode<>"")
    {
      if (eval($breakcode)===True)
      {
        break;
      }
    }
  }
  fclose($fp);
  return $response;
}

#####################################################################################################

function wpost($host,$uri,$port,$agent=ICEWEASEL_UA,$params,$extra_headers="",$timeout=20,$params_str=False,$dump_request=False)
{
  if (check_url($host.$uri)==False) # check url against blacklist
  {
    return "";
  }
  $errno=0;
  $errstr="";
  if ($port==443)
  {
    $fp=@fsockopen("ssl://$host",443,$errno,$errstr,$timeout);
  }
  else
  {
    $fp=@fsockopen($host,$port,$errno,$errstr,$timeout);
  }
  if ($fp===False)
  {
    term_echo("Error connecting to \"$host\".");
    return;
  }
  if ($params_str==False)
  {
    $content="";
    foreach ($params as $key => $value)
    {
      if ($content<>"")
      {
        $content=$content."&";
      }
      $content=$content.$key."=".rawurlencode($value);
    }
  }
  else
  {
    $content=$params;
  }
  $headers="POST $uri HTTP/1.0\r\n";
  $headers=$headers."Host: $host\r\n";
  $headers=$headers."User-Agent: $agent\r\n";
  $headers=$headers."Content-Type: application/x-www-form-urlencoded\r\n";
  $headers=$headers."Content-Length: ".strlen($content)."\r\n";
  if ($extra_headers<>"")
  {
    foreach ($extra_headers as $key => $value)
    {
      $headers=$headers.$key.": ".$value."\r\n";
    }
  }
  $headers=$headers."Connection: Close\r\n\r\n";
  $request=$headers.$content;
  if ($dump_request==True)
  {
    var_dump($request);
  }
  fwrite($fp,$request);
  $response="";
  while (!feof($fp))
  {
    $response=$response.fgets($fp,1024);
  }
  fclose($fp);
  return $response;
}

#####################################################################################################

function strip_headers($response)
{
  $delim="\r\n\r\n";
  $i=strpos($response,$delim);
  if ($i===False)
  {
    return False;
  }
  return substr($response,$i+strlen($delim));
}

#####################################################################################################

function extract_raw_tag($html,$tag)
{
  $delim1="<$tag";
  $delim2=">";
  $delim3="</$tag>";
  $i=strpos(strtolower($html),strtolower($delim1));
  if ($i===False)
  {
    return False;
  }
  $html=substr($html,$i+strlen($delim1));
  $i=strpos($html,$delim2);
  if ($i===False)
  {
    return False;
  }
  $html=substr($html,$i+strlen($delim2));
  $i=strpos(strtolower($html),strtolower($delim3));
  if ($i===False)
  {
    return False;
  }
  return substr($html,0,$i);
}

#####################################################################################################

function extract_void_tag($html,$tag)
{
  $delim1="<$tag";
  $delim2=">";
  $html=extract_text($html,$delim1,$delim2);
  if ($html===False)
  {
    return False;
  }
  if (substr($html,strlen($html)-1,1)=="/")
  {
    $html=substr($html,0,strlen($html)-1);
  }
  return trim($html);
}

#####################################################################################################

function strip_first_tag(&$html,$tag)
{
  $lhtml=strtolower($html);
  $i=strpos($lhtml,"<$tag");
  $end="</$tag>";
  $j=strpos($lhtml,$end);
  if (($i===False) or ($j===False))
  {
    return False;
  }
  $html=substr($html,0,$i).substr($html,$j+strlen($end));
  return True;
}

#####################################################################################################

function extract_meta_content($html,$name,$key="name")
{
  # <meta name="description" content="Researchers have made a breakthrough in blah blah blah." id="metasummary" />
  $lhtml=strtolower($html);
  $lname=strtolower($name);
  $parts=explode("<meta ",$lhtml);
  array_shift($parts);
  if (count($parts)==0)
  {
    return False;
  }
  $result="";
  for ($i=0;$i<count($parts);$i++)
  {
    $n=extract_text($parts[$i],"$key=\"","\"");
    if ($n===False)
    {
      continue;
    }
    if ($n<>$lname)
    {
      continue;
    }
    $result=extract_text($parts[$i],"content=\"","\"");
    break;
  }
  if ($result=="")
  {
    return False;
  }
  $i=strpos($lhtml,$result);
  if ($i===False)
  {
    return False;
  }
  $result=substr($html,$i,strlen($result));
  return $result;
}

#####################################################################################################

function strip_comments(&$html)
{
  $i=strpos($html,"<!--");
  $end="-->";
  $j=strpos($html,$end);
  if (($i===False) or ($j===False))
  {
    return False;
  }
  $html=substr($html,0,$i).substr($html,$j+strlen($end));
  strip_comments($html);
  return True;
}

#####################################################################################################

function strip_all_tag(&$html,$tag)
{
  while (strip_first_tag($html,$tag)==True)
  {
  }
}

#####################################################################################################

function exec_get_headers($response)
{
  $delim="\r\n\r\n";
  $i=strpos($response,$delim);
  if ($i===False)
  {
    return False;
  }
  return substr($response,0,$i);
}

#####################################################################################################

function exec_get_header($response,$header,$extract_headers=True)
{
  if ($extract_headers==True)
  {
    $headers=exec_get_headers($response);
  }
  else
  {
    $headers=$response;
  }
  $lines=explode("\n",$headers);
  for ($i=0;$i<count($lines);$i++)
  {
    $line=trim($lines[$i]);
    $parts=explode(":",$line);
    if (count($parts)>=2)
    {
      $key=trim($parts[0]);
      array_shift($parts);
      $value=trim(implode(":",$parts));
      if (strtolower($key)==strtolower($header))
      {
        return $value;
      }
    }
  }
  return "";
}

#####################################################################################################

function exec_get_cookies($response)
{
  $header="Set-Cookie";
  $values=array();
  $lines=explode("\n",exec_get_headers($response));
  for ($i=0;$i<count($lines);$i++)
  {
    $line=trim($lines[$i]);
    $parts=explode(":",$line);
    if (count($parts)>=2)
    {
      $key=trim($parts[0]);
      array_shift($parts);
      $value=trim(implode(":",$parts));
      if (strtolower($key)==strtolower($header))
      {
        $values[]=$value;
      }
    }
  }
  return $values;
}

#####################################################################################################

function extract_get($url,$name)
{
  $params=array();
  parse_str(parse_url($url,PHP_URL_QUERY),$params);
  if (isset($params[$name])==True)
  {
    return $params[$name];
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function html_decode($text)
{
  return html_entity_decode($text,ENT_QUOTES,"UTF-8");
}

#####################################################################################################

?>
