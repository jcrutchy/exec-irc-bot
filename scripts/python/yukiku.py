"""
exec:~yukiku|10|0|1|1|*||||python scripts/python/yukiku.py %%trailing%% 2>&1
"""

import sys
 
def function_name(arg):
    if arg == 0 and arg.isdigit:
        return True
    else:
        return False
 
def function_call(arg):
    arg1 = function_name(sys.argv)
    if arg1 == True:
        print "I'm true!"
    else:
        print arg1
 
function_call(sys.arg)
