<?php

namespace Peter\Observable;

/**
 * Interface ISubscription
 * Represents a disposable resource, like an unsubscribe handle.
 */
interface ISubscription
{
    public function unsubscribe(): void;
    public function isClosed(): bool; // Added for convenience
}
