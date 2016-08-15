<?php

#####################################################################################################

/*

http://ai-depot.com/FiniteStateMachines/FSM-Background.html

# from_state,condition,to_state
# items begining with % are user-defined function names
# | means or
# state names also correspond to user defined function names that define action to be taken in that state, state action function names must begin with 'state_'
# checking of state transitions occurs on every cycle and action is taken based on current state
# * means any state
# state names must be registered using the 'state_name:' multi-line comment directive
# state transitions must be registered using the 'state_transition:' multi-line comment directive

# eventually add weapon/object pickups and ai team tactics/roles, personalities etc
# look into stack-based fsm, http://gamedevelopment.tutsplus.com/tutorials/finite-state-machines-theory-and-implementation--gamedev-11867

state_def:idle %get_health<=%health_threshold & %enemy_scan=0
state_def:attack (%get_health>%health_threshold & %enemy_scan>0) | %is_surrounded=yes
state_def:patrol %get_health>%health_threshold & %enemy_scan=0
state_def:hide %get_health<=%health_threshold & %enemy_scan>0 & %is_surrounded=no
state_def:dead %get_health=0

*/

#####################################################################################################

function ai_init($hostname,&$map_data,&$player,&$players,&$ai_states)
{
  # initialize new ai player data structure based on state_init directives
  # add ai player to database
  # call ai_cycle
}

#####################################################################################################

function ai_cycle($hostname,&$map_data,&$player,&$players,&$ai_states)
{

}

#####################################################################################################

function state_idle($hostname,&$map_data,&$player,&$players,&$ai_states)
{

}

#####################################################################################################

function state_attack($hostname,&$map_data,&$player,&$players,&$ai_states)
{

}

#####################################################################################################

function state_patrol($hostname,&$map_data,&$player,&$players,&$ai_states)
{

}

#####################################################################################################

function state_hide($hostname,&$map_data,&$player,&$players,&$ai_states)
{
  # inspect enemies array
  # move in direction that puts self farthest away from all enemies, if possible
}

#####################################################################################################

function state_dead($hostname,&$map_data,&$player,&$players,&$ai_states)
{
  # delete ai player from database
}

#####################################################################################################

function get_health($hostname,&$map_data,&$player,&$players,&$ai_states)
{
  # return health variable
}

#####################################################################################################

function health_threshold($hostname,&$map_data,&$player,&$players,&$ai_states)
{

}

#####################################################################################################

function is_surrounded($hostname,&$map_data,&$player,&$players,&$ai_states)
{
  # inspect enemies array. if movement in any direction puts enemy closer by same amount, then is surrounded
}

#####################################################################################################

function enemy_scan($hostname,&$map_data,&$player,&$players,&$ai_states)
{
  # search all visible coordinates for enemies
  # if enemies found, add to enemies array
}

#####################################################################################################

?>
