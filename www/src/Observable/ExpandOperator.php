<?php

namespace Peter\Observable;

use Rx\Operator\OperatorInterface;
use Rx\Observable;
use Rx\ObserverInterface;
use Rx\DisposableInterface;
use Rx\ObservableInterface;


/**
 * @template T
 * @template E
 * @implements OperatorInterface<T, T>
 */
class ExpandOperator implements OperatorInterface
{
    private $project;
    private int $concurrent;

    /**
     * @param callable(T, int): Observable<T> $project
     * @param int $concurrent
     */
    public function __construct(callable $project, int $concurrent)
    {
        $this->project = $project;
        $this->concurrent = $concurrent;
    }

    public function __invoke(
        ObservableInterface $observable, 
        ObserverInterface $observer): DisposableInterface
    {
        $subscriber = new ExpandSubscriber($observer, $this->project, $this->concurrent);
        $parentSubscription = $observable->subscribe($subscriber);
        $subscriber->setParentSubscription($parentSubscription);
        return $subscriber;
    }

}
