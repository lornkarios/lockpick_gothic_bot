<?php

return [
    'config_rule' => ' Enter lock configuration in format - 5:[3+ 4-,1+,2+ 4- 3-,5+,1-] Where 5 is the number of lock parts, inside the square ' .
        'brackets is the setting for each part. The setting for each part with a + sign means the part moves the part under the number ' .
        'to the left of the sign together with itself, similarly - moves in the opposite direction. I.e. 3+4- in the 1st position ' .
        'means that the 1st part moves the 3rd together with itself and the 4th moves in the opposite direction',
    'welcome' => 'Time to pick the lock in Gothic 1 remake!',
    'invalid_config' => 'Invalid configuration( ',
    'state_rule' => 'Enter the current lock state in format - 5:[1,1,3,5,6,7,2]. Each digit means the position top-to-bottom (1-top,7-bottom) for each of the parts',
    'config_valid' => 'Configuration accepted! ',
    'invalid_state' => 'Invalid state( ',
    'state_valid' => 'All settings accepted! Starting to pick the lock...',
    'unlock_success' => 'Lock successfully picked!',
    'unlock_impossible' => 'This lock cannot be picked :(',
    'unlocking_in_progress' => 'Lock is being picked, please wait',
    'already_unlocked' => 'Lock is already open!',
    'step_by_step' => 'Step by step',
    'full_instruction' => 'Full instruction',
    'instruction_step' => ':step. Lever :lever :direction',
    'direction_up' => 'up',
    'direction_down' => 'down',
    'step_message' => ":visual\nMove lever :lever :direction",
    'step_final' => ":visual\nAll steps complete! Lock is unlocked.",
    'next_step' => 'Next step',
    'step_reminder' => 'Click "Next step" to continue',
];
