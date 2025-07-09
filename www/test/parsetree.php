<?php

// Define a constant for the match key, similar to Keys.match in the original JavaScript
define('MATCH_KEY', '__match');

/**
 * Placeholder for the actionWrapper function.
 * In a real Falcor-like system, this would likely bind the route context to the action.
 * It returns a callable (closure) that wraps the original action, passing the route path.
 *
 * @param array $route The current route path.
 * @param callable $action The original get/set/call action.
 * @return callable A wrapped action (closure).
 */
function actionWrapper(array $route, callable $action): callable {
    return function(...$args) use ($route, $action) {
        // Call the original action with the route and any other arguments
        return call_user_func_array($action, array_merge([$route], $args));
    };
}

/**
 * Placeholder for decendTreeByRoutedToken.
 * This function's exact logic is crucial but not provided in the original JS.
 * It appears to handle special route tokens (like ranges or named parameters).
 *
 * This function is designed to return the 'next' node (an array) if the token
 * represents a special type (like `{integers}`) or a recognized "ranged key"
 * (like '$id'). If it's a regular key, it returns `null`, indicating that
 * `buildParseTree` should create the node.
 *
 * @param array $node The current node in the parse tree (passed by reference).
 * @param mixed $token The route token (can be a string, number, or array/object for special types).
 * @param mixed $fullTokenObject Optional: The full token object if $token is a type property.
 * This argument is present when the original JS `value` was an object.
 * @return array|null Returns the next node (an array) if handled by this function, otherwise null.
 */
function decendTreeByRoutedToken(array &$node, $token, $fullTokenObject = null) {
    // Case 1: Original JS `value` was an object (e.g., {type: 'integers'}, {from: 0, to: 10})
    // This handles route segments like ['products', ['type' => 'integers'], 'description']
    if ($fullTokenObject !== null && is_array($fullTokenObject) && isset($fullTokenObject['type'])) {
        $routeType = $fullTokenObject['type'];
        // Ensure the node for this type exists and return it.
        if (!isset($node[$routeType])) {
            $node[$routeType] = [];
        }
        error_log("decendTreeByRoutedToken: Handled object token. Route Type: " . $routeType);
        return $node[$routeType];
    }

    // Case 2: Original JS `value` was a simple key (string or number).
    // This is where Falcor-like "ranged keys" (e.g., '$id', 'length') are handled.
    // If it's a special ranged key, we create a "virtual" node for it and return that node.
    if (is_string($token)) {
        // IMPORTANT: Customize this logic based on your actual Falcor-like "ranged key" patterns.
        // If these tokens represent special dynamic segments that need a dedicated node in the tree.
        if ($token === '$id' || $token === 'length') {
            // Create a node for this "virtual" or "ranged" key.
            // This node will then be the target for the next recursion step.
            if (!isset($node[$token])) {
                $node[$token] = [];
            }
            error_log("decendTreeByRoutedToken: Handled ranged key. Token: " . $token);
            return $node[$token]; // Return the created/existing node for this ranged key.
        }
    }

    // If it's not a special object token and not a recognized ranged string token,
    // then this function does not create the 'next' node.
    // `buildParseTree` will then handle creating a regular key node.
    error_log("decendTreeByRoutedToken: Not a special token, returning null. Token: " . (is_array($token) ? json_encode($token) : $token));
    return null;
}

/**
 * Builds a parse tree for routing based on a given route object.
 * This function is a PHP port of a JavaScript function, likely part of a Falcor-like routing system.
 * It recursively descends the route path, creating nodes in a tree structure
 * and attaching get/set/call handlers at the leaf nodes.
 *
 * @param array $node The current node in the parse tree (passed by reference, will be modified).
 * @param array $routeObject An associative array containing route details:
 * - 'route': array of route segments (tokens).
 * - 'get': callable for GET requests (optional).
 * - 'set': callable for SET requests (optional).
 * - 'call': callable for CALL requests (optional).
 * - 'prettyRoute': string representation of the route.
 * - 'getId', 'setId', 'callId': optional IDs for actions.
 * @param int $depth The current depth in the route path.
 * @return void
 */
function buildParseTree(array &$node, array $routeObject, int $depth): void {
    $route = $routeObject['route'];
    $get = $routeObject['get'] ?? null;
    $set = $routeObject['set'] ?? null;
    $call = $routeObject['call'] ?? null;

    // Check if the current depth is out of bounds for the route array
    if (!isset($route[$depth])) {
        error_log("buildParseTree: Depth " . $depth . " out of bounds for route. Route: " . json_encode($route));
        return; // Exit recursion if depth is invalid
    }

    $el = $route[$depth];

    // Coerce numeric strings to numbers, similar to JS `+el || el`
    // Use float for general numbers, int if only integers are strictly expected.
    $el = is_numeric($el) ? (float)$el : $el;

    $isArray = is_array($el);
    $i = 0;

    do {
        $value = $el;
        $next = null; // Initialize next node reference
        if ($isArray) {
            $value = $value[$i];
        }
        error_log("buildParseTree: Processing depth " . $depth . ", value: " . (is_array($value) ? json_encode($value) : $value));

        // Check if the current route segment is a special "ranged token" (an object in JS, array in PHP).
        // This typically happens with parsed path-syntax paths like `{integers}` or `{keys}`.
        if (is_array($value) && isset($value['type'])) {
            // Call decendTreeByRoutedToken with the full object to get the next node.
            // This function is expected to return an array (the actual node).
            $next = decendTreeByRoutedToken($node, $value['type'], $value);
        }
        // This is a simple key (string or number). Could potentially be a "ranged key"
        // that's represented as a simple string (e.g., '$id' or 'length').
        else {
            // decendTreeByRoutedToken will return the node if it's a special ranged key, or null otherwise.
            $next = decendTreeByRoutedToken($node, $value);

            if ($next !== null) {
                // If $next is not null, it means decendTreeByRoutedToken handled it (it's a ranged key).
                // The original JS also modified `route[depth]` in this case.
                $route[$depth] = ['type' => $value, 'named' => false];
                // $next already holds the correct node to recurse into.
            } else {
                // If decendTreeByRoutedToken returned null, it's a regular simple key.
                // Create the node for this simple key if it doesn't exist.
                if (!isset($node[$value])) {
                    $node[$value] = [];
                }
                $next = $node[$value]; // Assign the newly created/existing node to $next.
            }
        }

        // Determine if this is the last segment of the route (leaf node) or if recursion is needed.
        if ($depth + 1 === count($route)) {
            error_log("buildParseTree: Reached leaf node at depth " . $depth . ". Attaching handlers.");
            // This is a leaf node; attach the get/set/call handlers.
            // Retrieve or initialize the match object for this node.
            $matchObject = $next[MATCH_KEY] ?? [];
            if (!isset($next[MATCH_KEY])) {
                $next[MATCH_KEY] = $matchObject;
            }

            $matchObject['prettyRoute'] = $routeObject['prettyRoute'];

            // Attach actions if provided, wrapped by actionWrapper
            if ($get) {
                $matchObject['get'] = actionWrapper($route, $get);
                $matchObject['getId'] = $routeObject['getId'] ?? null;
            }
            if ($set) {
                $matchObject['set'] = actionWrapper($route, $set);
                $matchObject['setId'] = $routeObject['setId'] ?? null;
            }
            if ($call) {
                $matchObject['call'] = actionWrapper($route, $call);
                $matchObject['callId'] = $routeObject['callId'] ?? null;
            }
            // Update the node with the modified matchObject
            $next[MATCH_KEY] = $matchObject;

        } else {
            // Not a leaf node; recurse to the next depth.
            error_log("buildParseTree: Recursing from depth " . $depth . " to " . ($depth + 1) . ". Next node type: " . gettype($next));

            // Ensure $next is an array before passing by reference to the recursive call.
            if (!is_array($next)) {
                // This indicates an unexpected state where $next is not an array.
                // This error log should help pinpoint if $next is not what's expected.
                error_log("Error: \$next is not an array before recursive call at depth " . $depth . ". Value: " . print_r($next, true));
                // Fallback to prevent fatal error, though this indicates a logic problem.
                $next = [];
            }
            // Recursively call buildParseTree for the next segment.
            // $next is passed by reference, so modifications in the recursive call affect it.
            buildParseTree($next, $routeObject, $depth + 1);
        }

    } while ($isArray && ++$i < count($el)); // Continue loop if 'el' was an array (e.g., ['id1', 'id2'])
}

// --- Example Usage and Demonstration ---

// Define a dummy get action for testing purposes
$myGetAction = function($routePath) {
    return "Data for route: " . implode('.', $routePath);
};

// Define a dummy set action for testing purposes
$mySetAction = function($routePath, $value) {
    return "Set data for route: " . implode('.', $routePath) . " to '" . $value . "'";
};

// Define a dummy call action for testing purposes
$myCallAction = function($routePath, $args) {
    return "Called route: " . implode('.', $routePath) . " with args: " . json_encode($args);
};

// Initialize the root of the parse tree
$parseTree = [];

echo "--- Building Parse Tree Examples ---\n\n";

// Example 1: Simple route with a numeric key and a GET handler
//$routeObject1 = [
//    'route' => ['users', 123, 'name'],
    //'get' => $myGetAction,
    //'prettyRoute' => 'users[123].name',
    //'getId' => 'getUsersName'
//];
//echo "Building parse tree for route: " . $routeObject1['prettyRoute'] . "\n";
//buildParseTree($parseTree, $routeObject1, 0);
//echo "Current Parse Tree:\n" . json_encode($parseTree, JSON_PRETTY_PRINT) . "\n\n";

// Example 2: Another simple route with a SET handler
$routeObject2 = [
    'route' => ['videos', 'latest', 'title'],
    'set' => $mySetAction,
    'prettyRoute' => 'videos.latest.title',
    'setId' => 'setVideoTitle'
];
echo "Building parse tree for route: " . $routeObject2['prettyRoute'] . "\n";
buildParseTree($parseTree, $routeObject2, 0);
echo "Current Parse Tree:\n" . json_encode($parseTree, JSON_PRETTY_PRINT) . "\n\n";

// Example 3: Route with an array token (mimics Falcor's `{keys}` or specific list of keys)
// The 'do...while' loop handles each element in the array token.
$routeObject3 = [
    'route' => ['items', ['id1', 'id2'], 'price'],
    'get' => $myGetAction,
    'prettyRoute' => 'items[{keys}].price'
];
echo "Building parse tree for route: " . $routeObject3['prettyRoute'] . "\n";
buildParseTree($parseTree, $routeObject3, 0);
echo "Current Parse Tree:\n" . json_encode($parseTree, JSON_PRETTY_PRINT) . "\n\n";

// Example 4: Route with an object token (mimics Falcor's `{integers}` or `{ranges}`)
// This triggers the `is_array($value) && isset($value['type'])` branch.
$routeObject4 = [
    'route' => ['products', ['type' => 'integers'], 'description'],
    'get' => $myGetAction,
    'prettyRoute' => 'products[{integers}].description'
];
echo "Building parse tree for route: " . $routeObject4['prettyRoute'] . "\n";
buildParseTree($parseTree, $routeObject4, 0);
echo "Final Parse Tree:\n" . json_encode($parseTree, JSON_PRETTY_PRINT) . "\n\n";


echo "--- Accessing Handlers (Simulated Routing) ---\n\n";

// Simulate accessing a GET handler for 'users[123].name'
if (isset($parseTree['users'][123]['name'][MATCH_KEY]['get'])) {
    echo "Accessing handler for 'users[123].name':\n";
    $handler = $parseTree['users'][123]['name'][MATCH_KEY]['get'];
    echo "Result: " . $handler(['users', 123, 'name']) . "\n\n";
}

// Simulate accessing a SET handler for 'videos.latest.title'
if (isset($parseTree['videos']['latest']['title'][MATCH_KEY]['set'])) {
    echo "Accessing handler for 'videos.latest.title':\n";
    $handler = $parseTree['videos']['latest']['title'][MATCH_KEY]['set'];
    echo "Result: " . $handler(['videos', 'latest', 'title'], 'New Awesome Video Title') . "\n\n";
}

// Simulate accessing GET handlers for 'items[id1].price' and 'items[id2].price'
if (isset($parseTree['items']['id1']['price'][MATCH_KEY]['get'])) {
    echo "Accessing handler for 'items[id1].price':\n";
    $handler = $parseTree['items']['id1']['price'][MATCH_KEY]['get'];
    echo "Result: " . $handler(['items', 'id1', 'price']) . "\n\n";
}
if (isset($parseTree['items']['id2']['price'][MATCH_KEY]['get'])) {
    echo "Accessing handler for 'items[id2].price':\n";
    $handler = $parseTree['items']['id2']['price'][MATCH_KEY]['get'];
    echo "Result: " . $handler(['items', 'id2', 'price']) . "\n\n";
}

// Simulate accessing a GET handler for 'products[{integers}].description'
// The actual integer value would be determined by a router, but the handler receives the full path.
if (isset($parseTree['products']['integers']['description'][MATCH_KEY]['get'])) {
    echo "Accessing handler for 'products[{integers}].description':\n";
    $handler = $parseTree['products']['integers']['description'][MATCH_KEY]['get'];
    // In a real Falcor router, 'some_integer' would be the matched integer from the path.
    echo "Result: " . $handler(['products', 42, 'description']) . "\n\n";
}

?>
