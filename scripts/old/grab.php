<?php

# gpl2
# by crutchy
# 4-may-2014

$words=array("back lawn","strange men","testes","homosexual","rubber hose","bird poop","smuggling live humans","handjob");
for ($i=0;$i<count($words);$i++)
{
  if (strpos(strtolower($argv[1]),$words[$i])!==False)
  {
    echo "IRC_MSG !grab ".$argv[2]."\n";
  }
}

?>
