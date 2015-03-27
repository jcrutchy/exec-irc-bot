<?php

  echo "<script language=\"JavaScript\" type=\"text/javascript\">\n";
  echo "function point_it(event)\n";
  echo "{\n";
  echo "  pos_x=event.offsetX?(event.offsetX):event.pageX-document.getElementById(\"pointer_div\").offsetLeft;\n";
  echo "  pos_y=event.offsetY?(event.offsetY):event.pageY-document.getElementById(\"pointer_div\").offsetTop;\n";
  echo "  document.pointform.form_x.value=pos_x;\n";
  echo "  document.pointform.form_y.value=pos_y;\n";
  echo "  document.forms['pointform'].submit();\n";
  echo "}\n";
  echo "</script>\n";
  echo "</head>\n";
  echo "<body>\n";

  $x=-1;
  $y=-1;

  $xx=-1;
  $yy=-1;

  if ((isset($_POST["form_x"])==True) and (isset($_POST["form_y"])==True))
  {

    $xx=$_POST["form_x"]; # X PIXEL CO-ORDINATE
    $yy=$_POST["form_y"]; # Y PIXEL CO-ORDINATE

    # CALCULATE TILE CO-ORDINATE

    $msk=imagecreatefromgif($FilePath.'tile_mask.gif');

    $dx=$xx%$tile_w;
    $dy=$yy%$tile_h;

    $c=imagecolorat($msk,$dx,$dy);
    $c_nw=imagecolorexact($msk,0,0,255);
    $c_ne=imagecolorexact($msk,0,255,0);
    $c_sw=imagecolorexact($msk,255,0,0);
    $c_se=imagecolorexact($msk,255,0,255);
    $c_ctr=imagecolorexact($msk,255,255,255);

    $sx=$xx/$tile_w;
    $sx=(int)$sx;
    $sx=$sx*2;
    $sy=$yy/$tile_h;
    $sy=(int)$sy;

    if ($c==$c_nw)
    {
      $x=$sx-1;
      $y=$sy-1;
    }
    if ($c==$c_ne)
    {
      $x=$sx+1;
      $y=$sy-1;
    }
    if ($c==$c_sw)
    {
      $x=$sx-1;
      $y=$sy;
    }
    if ($c==$c_se)
    {
      $x=$sx+1;
      $y=$sy;
    }
    if ($c==$c_ctr)
    {
      $x=$sx;
      $y=$sy;
    }

    imagedestroy($msk);

  }

  if (isset($_GET["edit"])==True)
  {
    echo "<form name=\"pointform\" method=\"post\" action=\"?edit\">\n";
    if (isset($_POST["terrain_id"])==True)
    {
      $terrain_id=$_POST["terrain_id"];
      if (($x>=0) and ($y>=0) and ($x<$map_x) and ($y<$map_y))
      {
        $query="UPDATE Tiles SET TerrainID=$terrain_id WHERE ((CoordX=$x) AND (CoordY=$y))";
        if (!$Q=mysql_query($query))
        {
          echo "<p>Query Error: \"$query\"</p>\n";
        }
        echo "<p>$query</p>\n";
      }
      else
      {
        echo "<p>Range error.</p>\n";
      }
    }
    else
    {
      $terrain_id=1;
    }
    echo "<input type=\"text\" name=\"terrain_id\" value=\"$terrain_id\">\n";
  }
  else
  {
    echo "<form name=\"pointform\" method=\"post\" action=\".\">\n";
  }
  echo "<div id=\"pointer_div\" onclick=\"point_it(event)\" style=\"background-image:url('?map&x=$x&y=$y&xx=$xx&yy=$yy');width:".$map_w."px;height:".$map_h."px;\"></div>\n";
  echo "<input type=\"hidden\" name=\"form_x\" value=\"0\">\n";
  echo "<input type=\"hidden\" name=\"form_y\" value=\"0\">\n";
  echo "</form>\n";

?>
