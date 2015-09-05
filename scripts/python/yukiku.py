"""
exec:~yukiku|10|0|1|1|*||||python scripts/python/yukiku.py %%trailing%% 2>&1
script by Alex
https://github.com/Dhs92
"""

import sys

arg = sys.argv

def function_name(arg):
    if arg == 0:
        return True
    else:
        return arg

def function_call(arg):
    arg1 = function_name(arg)
    if arg1:
        print "I'm true!"
    else:
        print arg1

function_call(arg)
