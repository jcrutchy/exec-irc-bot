/*
exec:add ~ctest
exec:edit ~ctest auto 1
exec:edit ~ctest cmd tcc -run scripts/c/hello.c
exec:enable ~ctest
*/

#include <stdio.h>
int main()
{
printf("Hello World!\n");
}
