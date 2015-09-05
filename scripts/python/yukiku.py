"""
exec:~yukiku|10|0|1|1|*||||python scripts/python/yukiku.py %%trailing%% 2>&1
script by Alex Forehand
https://github.com/Dhs92
"""

import sys
 
arg = sys.argv[1]
 
 
def function_name(arg):
    if arg == 0:
        return True
    else:
        return arg
 
 
def function_call(arg):
    if function_name(arg) == True:
        print "I'm true!"
    else:
        print function_name(arg)
 
function_call(arg)
