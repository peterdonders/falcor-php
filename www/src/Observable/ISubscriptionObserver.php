<?php

namespace Peter\Observable;

/**
 * Interface ISubscriptionObserver
 * The observer interface for receiving notifications from an Observable.
 * T is the type of values, E is the type of errors.
 */
interface ISubscriptionObserver
{
    /**
     * Called when the observable emits a new value.
     * @param mixed $value
     */
    public function next($value): void;

    /**
     * Called when the observable encounters an error.
     * @param mixed $error
     */
    public function error($error): void;

    /**
     * Called when the observable has no more values to emit.
     */
    public function complete(): void;
}