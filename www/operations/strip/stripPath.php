<?php


/**
 * Takes in the matched path and virtual path and creates the
 * set of paths that represent the virtualPath being stripped
 * from the matchedPath.
 *
 * @example
 * Terms:
 * * Relative Complement: Of sets A and B the relative complement of A in B is
 * the parts of B that A do not contain.  In our example its virtualPath (A) in
 * matchedPath (B).
 *
 * Example:
 * matchedInput = [[A, D], [B, E], [C, F]]
 * virtualIntput = [A, Keys, C]
 *
 * This will produce 2 arrays from the matched operation.
 * [
 *   [D, [B, E], [C, F]],
 *   [A, [B, E], [F]]
 * ]
 *
 *
 * All the complexity of this function is hidden away in strip and its inner
 * stripping functions.
 * @param {PathSet} matchedPath
 * @param {PathSet} virtualPath
 */
function stripPath($matchedPath, $virtualPath) {
    $relativeComplement = [];
    $exactMatch = [];
    $current = [];

    // Always use virtual path because it can be shorter.
    for ($i = 0, $len = count($virtualPath); $i < $len; ++$i) {
        $matchedAtom = $matchedPath[$i];
        $virtualAtom = $virtualPath[$i];
        $stripResults = strip($matchedAtom, $virtualAtom);
        $innerMatch = $stripResults[0];
        $innerComplement = $stripResults[1];
        $hasComplement = count($innerComplement) > 0;

        // using the algorithm partially described above we need to split and
        // combine output depending on what comes out of the split function.
        // 1.  If there is no relative complement do no copying / slicing.
        // 2.  If there is both the catAndslice.

        if ($hasComplement) {
            $flattendIC = count($innerComplement) === 1 ?
                $innerComplement[0] : $innerComplement;
            $current[$i] = $flattendIC;
            $relativeComplement[count($relativeComplement)] =
                catAndSlice($current, $matchedPath, $i + 1);
        }

        // The exact match needs to be produced for calling function.
        $exactMatch[$i] = $innerMatch;
        $current[$i] = $innerMatch;
    }

    return [$exactMatch, $relativeComplement];
};

/**
 * Takes a virtual atom and the matched atom and returns an
 * array of results that is relative complement with matchedAtom
 * as the rhs. I believe the proper set syntax is virtualAtom \ matchedAtom.
 *
 * 1) An assumption made is that the matched atom and virtual atom have
 * an intersection. This makes the algorithm easier since if the matched
 * atom is a primitive and the virtual atom is an object
 * then there is no relative complement to create. This also means if
 * the direct equality test fails and matchedAtom is not an object
 * then virtualAtom is an object. The inverse applies.
 *
 *
 * @param mixed $matchedAtom
 * @param mixed $virtualAtom
 * @return array The tuple of what was matched and the relative complement.
 */
function strip($matchedAtom, $virtualAtom): array {
    $relativeComplement = [];
    $matchedResults = null;
    $typeOfMatched = gettype($matchedAtom);
    $isArrayMatched = is_array($matchedAtom);
    $isObjectMatched = $typeOfMatched === 'object' || $typeOfMatched === 'array'; // PHP treats arrays as 'array' type, objects as 'object'

    // Lets assume they are not objects. This covers the
    // string / number cases.
    if ($matchedAtom === $virtualAtom ||
        (string)$matchedAtom === (string)$virtualAtom) { // Explicitly cast to string for comparison

        $matchedResults = [$matchedAtom];
    }

    // See function comment 1)
    // If not an object (primitive type)
    elseif (!$isObjectMatched) {
        $matchedResults = [$matchedAtom];
    }

    // Its a complex object set potentially. Let the
    // subroutines handle the cases.
    else {
        $results = [];

        // The matchedAtom needs to be reduced to everything that is not in
        // the virtualAtom.
        if ($isArrayMatched) {
            $results = stripFromArray($virtualAtom, $matchedAtom);
            $matchedResults = $results[0];
            $relativeComplement = $results[1];
        } else {
            // Assuming objects represent ranges or other complex structures
            // that stripFromRange can handle.
            $results = stripFromRange($virtualAtom, $matchedAtom);
            $matchedResults = $results[0];
            $relativeComplement = $results[1];
        }
    }

    if (is_array($matchedResults) && count($matchedResults) === 1) {
        $matchedResults = $matchedResults[0];
    }

    return [$matchedResults, $relativeComplement];
}

/**
 * Concatenates two arrays and optionally slices the second one.
 * Equivalent to JavaScript's `[...a, ...b.slice(slice)]` or manual concatenation.
 *
 * @param array $a
 * @param array $b
 * @param int $slice Optional starting index for slicing $b.
 * @return array
 */
function catAndSlice(array $a, array $b, int $slice = 0): array {
    $next = $a; // Start with elements from $a
    // Add elements from $b, starting from $slice index
    for ($j = $slice; $j < count($b); ++$j) {
        $next[] = $b[$j];
    }
    return $next;
}

/**
 * Takes a string, number, or RoutedToken and removes it from
 * the array. The results are the relative complement of what
 * remains in the array.
 *
 * Don't forget: There was an intersection test performed but
 * since we recurse over arrays, we will get elements that do
 * not intersect.
 *
 * Another one is if its a routed token and a ranged array then
 * no work needs to be done as integers, ranges, and keys match
 * that token set.
 *
 * One more note. When toStrip is an array, we simply recurse
 * over each key. Else it requires a lot more logic.
 *
 * @param mixed $toStrip Can be an array, string, number, or object (RoutedToken).
 * @param array $array
 * @return array The relative complement.
 */
function stripFromArray($toStrip, array $array): array {
    $complement = [];
    $matches = [];
    $typeToStrip = gettype($toStrip);
    // Determine if the array contains objects (like ranges)
    $isRangedArray = !empty($array) && (is_object($array[0]) || is_array($array[0]));
    $isNumber = $typeToStrip === 'integer' || $typeToStrip === 'double';
    $isString = $typeToStrip === 'string';
    // A "RoutedToken" is anything that's not a number or a string.
    // In JS, this often means an object. In PHP, this would be an object or a complex array.
    $isRoutedToken = !$isNumber && !$isString;
    // Assuming 'type' property exists on RoutedToken objects
    $routeType = $isRoutedToken && (is_object($toStrip) && property_exists($toStrip, 'type') ? $toStrip->type : (is_array($toStrip) && isset($toStrip['type']) ? $toStrip['type'] : false));
    $isKeys = $routeType === Keys::keys;
    $toStripIsArray = is_array($toStrip);

    // The early break case. If it's a key, then there is never a
    // relative complement.
    if ($isKeys) {
        $complement = [];
        $matches = $array;
    }

    // Recurse over all the keys of the array.
    elseif ($toStripIsArray) {
        $currentArray = $array;
        foreach ($toStrip as $atom) {
            $results = stripFromArray($atom, $currentArray);
            if ($results[0] !== null) { // Check for non-null instead of undefined
                $matches = array_merge($matches, (array)$results[0]); // Ensure $results[0] is an array for concat
            }
            $currentArray = $results[1];
        }
        $complement = $currentArray;
    }

    // The simple case, remove only the matching element from array.
    elseif (!$isRangedArray && !$isRoutedToken) {
        $matches = [$toStrip];
        $complement = array_filter($array, function($x) use ($toStrip) {
            return $toStrip !== $x;
        });
        $complement = array_values($complement); // Re-index numeric array
    }

    // 1: from comments above (rangedArray with a non-routed token)
    elseif ($isRangedArray && !$isRoutedToken) {
        $complement = [];
        foreach ($array as $range) {
            $results = stripFromRange($toStrip, $range);
            if ($results[0] !== null) { // Check for non-null instead of undefined
                $matches = array_merge($matches, (array)$results[0]);
            }
            $complement = array_merge($complement, (array)$results[1]);
        }
    }

    // Strips elements based on routed token type.
    // We already matched keys above, so we only need to strip numbers.
    elseif (!$isRangedArray && $isRoutedToken) {
        $matches = []; // Initialize matches here for this block
        $complement = array_filter($array, function($el) use (&$matches) { // Pass matches by reference
            $type = gettype($el);
            if ($type === 'integer' || $type === 'double') {
                $matches[] = $el; // Add to matches array
                return false; // Filter out numbers
            }
            return true;
        });
        $complement = array_values($complement); // Re-index numeric array
    }

    // The final complement is rangedArray with a routedToken,
    // relative complement is always empty.
    else {
        $complement = [];
        $matches = $array;
    }

    return [$matches, $complement];
}

/**
 * Takes the first argument, toStrip, and strips it from
 * the range. The output is an array of ranges that represents
 * the remaining ranges (relative complement)
 *
 * One note. When toStrip is an array, we simply recurse
 * over each key. Else it requires a lot more logic.
 *
 * Since we recurse array keys we are not guaranteed that each strip
 * item coming in is a string integer. That is why we are doing an isNaN
 * check. consider: {from: 0, to: 1} and [0, 'one'] intersect at 0, but will
 * get 'one' fed into stripFromRange.
 *
 * @param mixed $argToStrip Can be a string, number, or an object (routed token).
 * Cannot be a range itself.
 * @param object|array $range An object or associative array with 'from' and 'to' properties/keys.
 * @return array The relative complement, containing an array of matched values and an array of remaining ranges.
 */
function stripFromRange($argToStrip, $range): array {
    $ranges = [];
    $matches = [];
    $toStrip = $argToStrip;
    // TODO: More than likely a bug around numbers and stripping
    $toStripIsNumber = isNumber($toStrip);

    if ($toStripIsNumber) {
        $toStrip = (int)$toStrip; // Cast to integer for strict comparison if needed
    }

    // Strip out non-numeric strings if toStrip is a string.
    // The original JS had `typeof toStrip === 'string'` but also `!toStripIsNumber`.
    // This condition means it's a string that *isn't* a number (e.g., 'foo').
    if (!$toStripIsNumber && is_string($toStrip)) {
        $ranges = [$range];
    }

    // If toStrip is an array, recurse over its elements.
    elseif (is_array($toStrip)) {
        $currentRanges = [$range]; // Start with the single range
        foreach ($toStrip as $atom) {
            $nextRanges = [];
            foreach ($currentRanges as $currentRangeItem) {
                $matchAndComplement = stripFromRange($atom, $currentRangeItem);
                if ($matchAndComplement[0] !== null) {
                    $matches = array_merge($matches, (array)$matchAndComplement[0]);
                }
                $nextRanges = array_merge($nextRanges, (array)$matchAndComplement[1]);
            }
            $currentRanges = $nextRanges;
        }
        $ranges = $currentRanges;
    }

    // The simple case, it's just a number.
    elseif ($toStripIsNumber) {
        $rangeFrom = is_object($range) ? $range->from : $range['from'];
        $rangeTo = is_object($range) ? $range->to : $range['to'];

        if ($rangeFrom < $toStrip && $toStrip < $rangeTo) {
            $ranges[0] = (object)['from' => $rangeFrom, 'to' => $toStrip - 1]; // Use object for consistency
            $ranges[1] = (object)['from' => $toStrip + 1, 'to' => $rangeTo];
            $matches = [$toStrip];
        }

        // In case it's a 0 length array (single element range).
        // Even though this assignment is redundant, its point is
        // to capture the intention.
        elseif ($rangeFrom === $toStrip && $rangeTo === $toStrip) {
            $ranges = [];
            $matches = [$toStrip];
        }

        elseif ($rangeFrom === $toStrip) {
            $ranges[0] = (object)['from' => $toStrip + 1, 'to' => $rangeTo];
            $matches = [$toStrip];
        }

        elseif ($rangeTo === $toStrip) {
            $ranges[0] = (object)['from' => $rangeFrom, 'to' => $toStrip - 1];
            $matches = [$toStrip];
        }

        // return the range if no intersection.
        else {
            $ranges = [$range];
        }
    }

    // It's a routed token (object). Everything is matched.
    // This handles cases where `argToStrip` is an object that represents a token
    // (e.g., `Keys::INTEGERS`, `Keys::RANGES`) that matches everything within a range.
    else {
        $matches = rangeToArray($range);
    }

    // If this is a routedToken (Object) then it will match the entire
    // range since it's integers, keys, and ranges.
    return [$matches, $ranges];
}

/**
 * Will determine if the argument is a number.
 *
 * '1' returns true
 * 1 returns true
 * [1] returns false
 * null returns false
 * @param mixed $x
 * @return bool
 */
function isNumber($x): bool {
    // is_numeric handles strings that contain valid numbers ('1', '1.2')
    // and actual numbers (1, 1.2).
    // The typeof x !== 'object' check in JS means it should not be an array or object.
    return is_numeric($x) && !is_array($x) && !is_object($x);
}

/**
 * Converts a range object to an array of numbers.
 *
 * @param object|array $range An object or associative array with 'from' and 'to' properties/keys.
 * @return array
 */
function rangeToArray($range): array {
    $out = [];
    $from = is_object($range) ? $range->from : $range['from'];
    $to = is_object($range) ? $range->to : $range['to'];

    for ($i = $from; $i <= $to; ++$i) {
        $out[] = $i;
    }
    return $out;
}