<?php

# gpl2
# by crutchy
# 23-april-2014

$words=array("back lawn","strange men","testes","homosexual","rubber hose","bird poop","smuggling live humans");
for ($i=0;$i<count($words);$i++)
{
  if (strpos(strtolower($argv[1]),$words[$i])!==False)
  {
    echo "IRC_MSG !grab ".$argv[2]."\n";
  }
}

?>
