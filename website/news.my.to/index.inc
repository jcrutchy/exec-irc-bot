<?php

#####################################################################################################

/*
  RewriteEngine On
  RewriteRule ^/tfa/(.*)$ /index.php?tfa=$1 [L]
  RewriteRule ^/img/(.*)$ /index.php?img=$1 [L]
  RewriteRule ^/op/(.*)$ /index.php?op=$1 [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.php [L]
*/

#####################################################################################################

if (isset($_GET["op"])==True)
{
  die("op=".$_GET["op"]);
}

if (isset($_GET["user"])==True)
{
  die("user=".$_GET["user"]);
}

echo "<html><head><title>hi chromas!</title></head><body><p>welcome to news.my.to -- crutchy</p></body></html>";

#####################################################################################################

?>
