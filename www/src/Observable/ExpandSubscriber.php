<?php

namespace Peter\Observable; // Pas dit aan naar je eigen namespace

use Rx\Observable;
use Rx\ObserverInterface;
use Rx\DisposableInterface;
use Rx\Disposable\CallbackDisposable;
use Rx\Operator\OperatorInterface;
use Rx\Observer\CallbackObserver;
use SplQueue;

/**
 * Represents a subscriber for the expand operator.
 * @template T
 * @template E
 * @implements \Rx\ObserverInterface<T>
 */
class ExpandSubscriber implements ObserverInterface, DisposableInterface
{
    private ObserverInterface $destination;
    private callable $project;
    private int $concurrent;
    private \SplObjectStorage $innerSubs; // Using SplObjectStorage for inner subscribers
    private SplQueue $buffer;
    private int $index;
    private int $active;
    private bool $hasCompleted;
    private bool $isStopped;
    private ?DisposableInterface $parentSubscription;

    /**
     * @param ObserverInterface<T> $destination
     * @param callable(T, int): Observable<T> $project
     * @param int $concurrent
     */
    public function __construct(
        ObserverInterface $destination,
        callable $project,
        int $concurrent
    ) {
        $this->destination = $destination;
        $this->project = $project;
        $this->concurrent = $concurrent;
        $this->innerSubs = new \SplObjectStorage();
        $this->buffer = new SplQueue();
        $this->index = 0;
        $this->active = 0;
        $this->hasCompleted = false;
        $this->isStopped = false;
        $this->parentSubscription = null;
    }

    public function onNext($value): void
    {
        if ($this->isStopped) {
            return;
        }

        if ($this->active < $this->concurrent) {
            $this->projectTo($value);
        } else {
            $this->buffer->enqueue($value);
        }
    }

    public function onError(\Throwable $error): void
    {
        if ($this->isStopped) {
            return;
        }

        $this->destination->onError($error);
        $this->disposeAll();
        $this->isStopped = true;
    }

    public function onCompleted(): void
    {
        if ($this->isStopped) {
            return;
        }

        $this->hasCompleted = true;
        if ($this->active === 0) {
            $this->destination->onCompleted();
            $this->disposeAll();
            $this->isStopped = true;
        }
    }

    /**
     * @param T $value
     */
    private function projectTo($value): void
    {
        $this->destination->onNext($value); // Emit the original value
        
        $i = $this->index++;
        try {
            $observable = ($this->project)($value, $i);
        } catch (\Throwable $e) {
            $this->destination->onError($e);
            $this->disposeAll();
            $this->isStopped = true;
            return;
        }

        $innerObserver = new CallbackObserver(
            function ($innerValue) {
                $this->onNext($innerValue); // Recurse: project the inner value
            },
            function (\Throwable $error) {
                $this->destination->onError($error);
                $this->disposeAll();
                $this->isStopped = true;
            },
            function () use ($observable) {
                $this->active--;
                $this->innerSubs->detach($observable); // Detach the observable used for this inner subscription

                if (!$this->buffer->isEmpty()) {
                    $this->projectTo($this->buffer->dequeue());
                } elseif ($this->hasCompleted && $this->active === 0) {
                    $this->destination->onCompleted();
                    $this->disposeAll();
                    $this->isStopped = true;
                }
            }
        );

        $disposable = $observable->subscribe($innerObserver);
        $this->innerSubs->attach($observable, $disposable); // Store the disposable associated with the observable
        $this->active++;
    }

    public function dispose(): void
    {
        $this->disposeAll();
    }

    private function disposeAll(): void
    {
        if ($this->parentSubscription) {
            $this->parentSubscription->dispose();
            $this->parentSubscription = null;
        }

        foreach ($this->innerSubs as $observable) {
            /** @var DisposableInterface $disposable */
            $disposable = $this->innerSubs->offsetGet($observable);
            $disposable->dispose();
        }
        $this->innerSubs->removeAll();
        $this->isStopped = true;
    }

    /**
     * Set the parent subscription.
     * @param DisposableInterface $subscription
     */
    public function setParentSubscription(DisposableInterface $subscription): void
    {
        $this->parentSubscription = $subscription;
    }
}
