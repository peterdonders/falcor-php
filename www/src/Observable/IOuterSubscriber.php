<?php

namespace Peter\Observable;

/**
 * Interface IOuterSubscriber
 * An observer that can receive notifications from an "outer" observable
 * and also manage "inner" subscriptions.
 * TOuter, TInner, E are generic types for outer values, inner values, and errors.
 */
interface IOuterSubscriber extends ISubscriptionObserver, ISubscription
{
    public function start(ISubscription $subscription): void;
    public function notifyNext($outerValue, $innerValue, int $outerIndex, int $innerIndex, InnerSubscriber $innerSub): void;
    public function notifyError($error, InnerSubscriber $innerSub): void;
    public function notifyComplete(InnerSubscriber $innerSub): void;
}