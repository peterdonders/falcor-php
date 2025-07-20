<?php 

function hasIntersection($matchedPath, $virtualPath) {
    $intersection = true;

    // cycles through the atoms and ensure each one has an intersection.
    // only use the virtual path because it can be shorter than the full
    // matched path (since it includes suffix).
    for ($i = 0, $len = count($virtualPath); $i < $len && $intersection; ++$i) {
        $intersection = hasAtomIntersection($matchedPath[$i], $virtualPath[$i]);
    }

    return $intersection;
}

function hasAtomIntersection($matchedAtom, $virtualAtom) {
    $virtualIsRoutedToken = isRoutedToken($virtualAtom);
    $isKeys = $virtualIsRoutedToken && $virtualAtom->type === Keys::keys;
    $matched = false;

    // To simplify the algorithm we do not allow matched atom to be an
    // array.  This makes the intersection test very simple.
    if (is_array($matchedAtom)) {
        for ($i = 0, $len = count($matchedAtom); $i < $len && !$matched; ++$i) {
            $matched = hasAtomIntersection($matchedAtom[$i], $virtualAtom);
        }
    }

    // the == is very intentional here with all the use cases review.
    else if (doubleEquals($matchedAtom, $virtualAtom)) {
        $matched = true;
    }

    // Keys match everything.
    else if ($isKeys) {
        $matched = true;
    }

    // The routed token is for integers at this point.
    else if ($virtualIsRoutedToken) {
        $matched = is_numeric($matchedAtom) || isRange($matchedAtom);
    }

    // is virtual an array (last option)
    // Go through each of the array elements and compare against matched item.
    else if (is_array($virtualAtom)) {
        for ($i = 0, $len = count($virtualAtom); $i < $len && !$matched; ++$i) {
            $matched = hasAtomIntersection($matchedAtom, $virtualAtom[$i]);
        }
    }

    return $matched;
}


/**
 * This was very intentional ==.  The reason is that '1' must equal 1.
 * {} of anysort are always false and array ['one'] == 'one' but that is
 * fine because i would have to go through the array anyways at the
 * last elseif check.
 */
function doubleEquals($a, $b) {
    return $a == $b;
}

function isRange($range) {
	print_r($range);
	die();
    //return $range.hasOwnProperty('to') && $range.hasOwnProperty('from');
};