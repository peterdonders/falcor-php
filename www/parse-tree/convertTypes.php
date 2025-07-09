<?php



function convertTypes($virtualPath) {

    foreach ($virtualPath->route as $key) {
        if (getType($key) === 'object' && $key !== null && !is_array($key)) {
            switch ($key->type) {
                case 'keys':
                    $key->type = 'keys';
                    break;
                case 'integers':
                    $key->type = 'integers';
                    break;
                case 'ranges':
                    $key->type = 'ranges';
                    break;
                default:
                    throw new Error('Unknown route type.');
            }
        }
        
    }

    /*$virtualPath->route = virtualPath.route.map(function(key) {
        if (typeof key === 'object' && key !== null && !Array.isArray(key)) {
            switch (key.type) {
                case 'keys':
                    key.type = Keys.keys;
                    break;
                case 'integers':
                    key.type = Keys.integers;
                    break;
                case 'ranges':
                    key.type = Keys.ranges;
                    break;
                default:
                    throw new Error('Unknown route type.');
            }
        }
        return key;
    });*/
};