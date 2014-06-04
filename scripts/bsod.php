<?php

# gpl2
# by crutchy
# 4-june-2014

  $delay=0.1e6;
  $inc=0.05e6;

  echo chr(3)."00,12                                                                     \n";
  usleep($delay);
  $delay=$delay+$inc;
  echo chr(3)."00,12                                  ".chr(3)."12,00 Soylent ".chr(3)."00,12                          \n";
  usleep($delay);
  $delay=$delay+$inc;
  echo chr(3)."00,12                                                                     \n";
  usleep($delay);
  $delay=$delay+$inc;
  echo chr(3)."00,12 A fatal exception 0E has occurred at 0028:C0011E36 in VXD VMM(01) + \n";
  usleep($delay);
  $delay=$delay+$inc;
  echo chr(3)."00,12 00010E36. The current application will be terminated.               \n";
  usleep($delay);
  $delay=$delay+$inc;
  echo chr(3)."00,12                                                                     \n";
  usleep($delay);
  $delay=$delay+$inc;
  echo chr(3)."00,12 * Press any key to terminate the current application.               \n";
  usleep($delay);
  echo chr(3)."00,12 * Press CTRL+ALT+DEL again to restart your computer. You will       \n";
  usleep($delay);
  $delay=$delay+$inc;
  echo chr(3)."00,12   lose any unsaved information in all applications.                 \n";
  usleep($delay);
  $delay=$delay+$inc;
  echo chr(3)."00,12                                                                     \n";
  usleep($delay);
  $delay=$delay+$inc;
  echo chr(3)."00,12                      Press any key to continue _                    \n";
  usleep($delay);
  $delay=$delay+$inc;
  echo chr(3)."00,12                                                                     \n";

?>
