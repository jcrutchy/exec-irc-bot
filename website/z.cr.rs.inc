<?php

#####################################################################################################

/*
  RewriteEngine On
  RewriteRule ^/(.*)$ /index.php?i=$1 [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.php [L]
*/

$title="z.cr.rs";
if (isset($_GET["i"])==True)
{
  $title=$title." - ".$_GET["i"];
}

echo "<html><head><title>$title</title></head><body><p>Welcome to <b>z.cr.rs</b>.</p><p>-- crutchy</p>";

if (isset($_GET["i"])==True)
{
  echo "<p>query: ".$_GET["i"]."</p>";
}

echo "</body></html>";

#####################################################################################################

?>
