<?php

#####################################################################################################

echo "blah";



/*

$ID=0;
$HeadContent=GetHeadContent();

function GetHeadContent()
{
  $Result="<style type=\"text/css\">\n";
  $Result=$Result."td\n";
  $Result=$Result."  {\n";
  $Result=$Result."    border: 0px;\n";
  $Result=$Result."  }\n";
  $Result=$Result."iframe\n";
  $Result=$Result."  {\n";
  $Result=$Result."    border: 0px solid #FFFFFF;\n";
  $Result=$Result."  }\n";
  $Result=$Result."</style>\n";
  $Result=$Result."<script language=\"JavaScript\" type=\"text/javascript\">\n";
  $Result=$Result."function PartDetails(ID,ShowDetail)\n";
  $Result=$Result."{\n";
  $Result=$Result."  if (ShowDetail=='1')\n";
  $Result=$Result."  {\n";
  $Result=$Result."    document.forms['Frm'+ID].submit();\n";
  $Result=$Result."  }\n";
  $Result=$Result."  if (document.getElementById('N'+ID).style.display=='none')\n";
  $Result=$Result."  {\n";
  $Result=$Result."    document.getElementById('N'+ID).style.display='block';\n";
  $Result=$Result."    document.getElementById('Img'+ID).src='minus.gif';\n";
  $Result=$Result."  }\n";
  $Result=$Result."  else\n";
  $Result=$Result."  {\n";
  $Result=$Result."    document.getElementById('N'+ID).style.display='none';\n";
  $Result=$Result."    document.getElementById('Img'+ID).src='plus.gif';\n";
  $Result=$Result."  }\n";
  $Result=$Result."}\n";
  $Result=$Result."</script>\n";
  return $Result;
}

function GetPartID($Description)
{
  $Query="SELECT * FROM ERP.ERP_Parts WHERE (Description=\"$Description\")";
  if (!$Q=mysql_query($Query))
  {
    QueryError("GetPartID",$query);
    return -1;
  }
  $nrows=mysql_num_rows($Q);
  if ($nrows<=0)
  {
    MessagePage("<p>No parts found with description \"$Description\".</p>\n");
    return -1;
  }
  $row=mysql_fetch_array($Q, MYSQL_BOTH);
  extract($row);
  return $PartID;
}

function AssemblyTree()
{
  if (isset($_GET["PartID"])==True)
  {
    global $PageOverride;
    $PageOverride=True;
    $PartID=$_GET["PartID"];
    if (IsPositiveNumber($PartID)==True)
    {
      ShowPartDetail($PartID);
      return;
    }
    else
    {
      echo "<html><head></head><body><p>No part specified.</p></body></html>";
      return "";
    }
  }
  $Content="<div name=\"script\" id=\"script\" style=\"display: none\">\n";
  $Content=$Content."<table>\n<tr>\n<td style=\"vertical-align:top; width: 300px;\">\n".RecurseMenu("Aircraft")."</td>\n<td style=\"vertical-align:top; width: 500px; height: 400px;\">\n";
  $Content=$Content."<form id=\"Frm0\" name=\"Frm0\" action=\"?PartID=".GetPartID("Aircraft")."\" method=\"POST\" target=\"content\">".TemporarySessionPageIDFields("AssemblyTree")."</form>\n";
  $Content=$Content."<iframe src=\"\" name=\"content\" id=\"content\" width=\"500px\" height=\"400px\"></iframe>\n";
  $Content=$Content."</td>\n</tr>\n</table>\n";
  $Content=$Content."<script language=\"JavaScript\" type=\"text/javascript\">\n";
  $Content=$Content."  document.Frm0.submit();\n";
  $Content=$Content."</script>\n";
  $Content=$Content."</div>\n";
  $Content=$Content."<div name=\"noscript\" id=\"noscript\">\n<p>This page requires JavaScript.</p>\n</div>\n";
  $Content=$Content."<script language=\"JavaScript\" type=\"text/javascript\">\n";
  $Content=$Content."  document.getElementById(\"script\").style.display=\"block\";\n";
  $Content=$Content."  document.getElementById(\"noscript\").style.display=\"none\";\n";
  $Content=$Content."</script>\n";
  return $Content;
}

function ShowPartDetail($PartID)
{
  $Query="SELECT * FROM ERP.ERP_Parts WHERE (PartID=$PartID)";
  if (!$Q=mysql_query($Query))
  {
    return QueryError("ShowPartDetail",$query);
  }
  $nrows=mysql_num_rows($Q);
  if ($nrows<>1)
  {
    return MessagePage("<p>\$nrows<>1 (ShowPartDetail): PartID=$PartID</p>\n");
  }
  $row=mysql_fetch_array($Q, MYSQL_BOTH);
  extract($row);
  echo "<html>\n";
  echo "<body style=\"background-color: #BEAF9C;\">\n";
  echo "<p><b><u>$Description</u></b></p>\n";
  echo "</body>\n";
  echo "</html>\n";
  return;
}

function MenuItem($Level,$Caption,$Target,$SubMenu=False)
{
  global $ID;
  $Result="<table>\n<tr>\n";
  for ($i=1;$i<=$Level;$i++)
  {
    if (($i==$Level) and ($SubMenu==True))
    {
      if ($Level>1)
      {
        $Result=$Result."<td>\n<img src=\"plus.gif\" id=\"Img$ID\" onclick=\"javascript:PartDetails('$ID','0')\" alt=\"+\">\n</td>\n";
      }
      else
      {
        $Result=$Result."<td>\n<img src=\"minus.gif\" id=\"Img$ID\" onclick=\"javascript:PartDetails('$ID','0')\" alt=\"-\">\n</td>\n";
      }
    }
    else
    {
      $Result=$Result."<td>\n<img src=\"blank.gif\" alt=\"&nbsp;\">\n</td>\n";
    }
  }
  $Result=$Result."<td>\n<form id=\"Frm$ID\" name=\"Frm$ID\" action=\"?$Target\" method=\"POST\" target=\"content\">\n".TemporarySessionPageIDFields("AssemblyTree")."</form>\n<a href=\"javascript:PartDetails('$ID','1')\">$Caption</a>\n</td>\n";
  $Result=$Result."</tr>\n</table>\n";
  return $Result;
}

function RecurseMenu($ParentDescription,$Level=1)
{
  global $ID;
  $Result="";
  $Query="SELECT * FROM ERP.ERP_Parts WHERE (Description=\"$ParentDescription\")";
  if (!$Q=mysql_query($Query))
  {
    return QueryError("RecurseMenu",$query);
  }
  $nrows=mysql_num_rows($Q);
  if ($nrows<>1)
  {
    ShowPage("<p>Duplicate Part Description or Part Description Not Found (RecurseMenu): ParentDescription=\"$ParentDescription\"</p>\n");
    return "";
  }
  $row=mysql_fetch_array($Q, MYSQL_BOTH);
  extract($row);
  $Query="SELECT * FROM ERP.ERP_Assemblies WHERE (ParentID=$PartID)";
  if (!$Q=mysql_query($Query))
  {
    return QueryError("RecurseMenu",$query);
  }
  $nrows=mysql_num_rows($Q);
  $ID=$ID+1;
  if ($nrows>0)
  {
    $Result=$Result.MenuItem($Level,$Description,"PartID=$PartID",True);
    if ($Level<>1)
    {
      $Result=$Result."<div id=\"N$ID\" style=\"display:none;\">\n";
    }
    else
    {
      $Result=$Result."<div id=\"N$ID\">\n";
    }
  }
  else
  {
    $Result=$Result.MenuItem($Level,$Description,"PartID=$PartID");
  }
  for ($i=0;$i<$nrows;$i++)
  {
    $row=mysql_fetch_array($Q, MYSQL_BOTH);
    extract($row);
    $Query2="SELECT * FROM ERP.ERP_Parts WHERE (PartID=$ChildID)";
    if (!$Q2=mysql_query($Query2))
    {
      return QueryError("RecurseMenu",$query);
    }
    $nrows2=mysql_num_rows($Q2);
    if ($nrows2<>1)
    {
      ShowPage("<p>Duplicate Part Description or Part Description Not Found (RecurseMenu): PartID=$ChildID</p>\n");
      return "";
    }
    $row2=mysql_fetch_array($Q2, MYSQL_BOTH);
    extract($row2);
    $Result=$Result.RecurseMenu($Description,$Level+1);
  }
  if ($nrows>0)
  {
    $Result=$Result."</div>\n";
  }
  return $Result;
}

*/

#####################################################################################################

?>
