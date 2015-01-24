<?php

#####################################################################################################

/*
  RewriteEngine On
  RewriteRule ^/(.*)$ /index.php?i=$1 [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.php [L]
*/

echo "<html><head><title>z.cr.rs</title></head><body><p>Welcome to <b>z.cr.rs</b>.</p><p>-- crutchy</p>";

if (isset($_GET["i"])==True)
{
  echo "<p>query: ".$_GET["i"]."</p>";
}

echo "</body></html>";

#####################################################################################################

?>
