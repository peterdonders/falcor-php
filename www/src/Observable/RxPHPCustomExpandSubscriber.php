<?php 

namespace Peter\Observable;

use Rx\ObserverInterface;
use Rx\DisposableInterface;
use Rx\Observable;
use Rx\SchedulerInterface;
use Rx\Scheduler;
use Rx\Disposable\CallbackDisposable;

/**
 * Custom ExpandSubscriber for RxPHP.
 * This directly mirrors the JavaScript logic.
 *
 * @template T
 */
class RxPHPCustomExpandSubscriber implements ObserverInterface, DisposableInterface
{
    private ObserverInterface $actualObserver;
    /** @var callable(mixed, int): Observable */
    private $project;
    private int $concurrent;
    /** @var array<string, DisposableInterface> */ // Using array to simulate a Set for inner disposables
    private array $innerDisposables;
    /** @var array<T> */
    private array $buffer;
    private int $index;
    private int $active;
    private bool $hasCompleted;
    private bool $isStopped; // Equivalent to 'closed' in JS
    private ?DisposableInterface $parentDisposable = null; // To hold the disposable for the source subscription

    private SchedulerInterface $scheduler;

    /**
     * @param ObserverInterface $actualObserver The downstream observer.
     * @param callable(mixed, int): Observable $project The projection function.
     * @param int $concurrent The maximum concurrency.
     * @param SchedulerInterface|null $scheduler The scheduler to use for inner subscriptions.
     */
    public function __construct(
        ObserverInterface $actualObserver,
        callable $project,
        int $concurrent,
        ?SchedulerInterface $scheduler = null
    ) {
        $this->actualObserver = $actualObserver;
        $this->project = $project;
        $this->concurrent = $concurrent;
        $this->innerDisposables = [];
        $this->buffer = [];
        $this->index = 0;
        $this->active = 0;
        $this->hasCompleted = false;
        $this->isStopped = false;
        $this->scheduler = $scheduler ?? Scheduler::getDefault();
    }

    /**
     * Called when the source observable starts.
     * In RxPHP, the DisposableInterface returned by Observable::subscribe() is handled.
     * We'll capture it here.
     * @param DisposableInterface $disposable
     */
    public function setParentDisposable(DisposableInterface $disposable): void
    {
        $this->parentDisposable = $disposable;
    }

    public function onNext($value): void
    {
        if ($this->isStopped) {
            return;
        }

        if ($this->active < $this->concurrent) {
            $this->projectAndSubscribe($value);
        } else {
            $this->buffer[] = $value;
        }
    }

    public function onError(\Throwable $error): void
    {
        if ($this->isStopped) {
            return;
        }

        $this->actualObserver->onError($error);
        $this->disposeAllInnerSubscriptions();
        $this->isStopped = true;
        $this->parentDisposable?->dispose();
    }

    public function onCompleted(): void
    {
        if ($this->isStopped) {
            return;
        }

        $this->hasCompleted = true;
        if ($this->active === 0 && empty($this->buffer)) {
            $this->actualObserver->onCompleted();
            $this->isStopped = true;
            $this->parentDisposable?->dispose();
        }
    }

    /**
     * Projects a value to an observable and subscribes to it.
     * @param mixed $value
     */
    private function projectAndSubscribe($value): void
    {
        $this->actualObserver->onNext($value); // Re-emit the source value

        $i = $this->index++;
        $projectFn = $this->project;

        try {
            $innerObservable = $projectFn($value, $i);
        } catch (\Throwable $e) {
            $this->onError($e);
            return;
        }

        $this->active++;

        // Each inner subscription needs its own disposable management.
        // We use RefCountDisposable to allow multiple inner subscriptions to be tracked.
        $innerDisposable = new CallbackDisposable(function () use (&$innerSubDisposableHash) {
            unset($this->innerDisposables[$innerSubDisposableHash]);
        });

        // The actual inner subscriber
        $innerSub = new class($this, $innerDisposable) implements ObserverInterface, DisposableInterface {
            private RxPHPCustomExpandSubscriber $parent;
            private DisposableInterface $selfDisposable; // Disposable for this inner subscription entry in parent's map
            private ?DisposableInterface $actualInnerSubscription = null; // The disposable returned by innerObservable->subscribe()
            private bool $isInnerStopped = false;

            public function __construct(RxPHPCustomExpandSubscriber $parent, DisposableInterface $selfDisposable)
            {
                $this->parent = $parent;
                $this->selfDisposable = $selfDisposable;
            }

            public function setActualInnerSubscription(DisposableInterface $disposable): void
            {
                $this->actualInnerSubscription = $disposable;
            }

            public function onNext($value): void
            {
                if ($this->isInnerStopped) return;
                $this->parent->onNext($value); // Emit the inner value to the main stream (recursive expansion)
            }

            public function onError(\Throwable $error): void
            {
                if ($this->isInnerStopped) return;
                $this->isInnerStopped = true;
                $this->parent->notifyInnerError($error, $this);
                $this->dispose(); // Dispose self on error
            }

            public function onCompleted(): void
            {
                if ($this->isInnerStopped) return;
                $this->isInnerStopped = true;
                $this->parent->notifyInnerComplete($this);
                $this->dispose(); // Dispose self on complete
            }

            public function dispose(): void
            {
                if ($this->isInnerStopped) { // Already disposed
                    return;
                }
                $this->actualInnerSubscription?->dispose();
                $this->selfDisposable->dispose(); // Notify parent to remove from its list
                $this->isInnerStopped = true;
            }
        };

        $innerSubDisposableHash = spl_object_hash($innerSub);
        $this->innerDisposables[$innerSubDisposableHash] = $innerSub;

        // Subscribe to the inner observable
        $innerSub->setActualInnerSubscription(
            $innerObservable->subscribe($innerSub, $this->scheduler)
        );
    }

    /**
     * Called by inner subscribers on error.
     * @param \Throwable $error
     * @param DisposableInterface $innerSub
     */
    public function notifyInnerError(\Throwable $error, DisposableInterface $innerSub): void
    {
        // Propagate the error to the main observer.
        $this->onError($error);
    }

    /**
     * Called by inner subscribers on completion.
     * @param DisposableInterface $innerSub
     */
    public function notifyInnerComplete(DisposableInterface $innerSub): void
    {
        $this->active--;
        // The innerSub handles its own removal from innerDisposables via its dispose method
        // (which is called onComplete/onError). So we just check the buffer/completion state.

        if (!empty($this->buffer)) {
            $nextValue = array_shift($this->buffer);
            $this->projectAndSubscribe($nextValue);
        } elseif ($this->hasCompleted && $this->active === 0 && empty($this->buffer)) {
            $this->actualObserver->onCompleted();
            $this->isStopped = true;
            $this->parentDisposable?->dispose();
        }
    }

    public function dispose(): void
    {
        if ($this->isStopped) {
            return;
        }

        $this->parentDisposable?->dispose();
        $this->disposeAllInnerSubscriptions();
        $this->isStopped = true;
    }

    private function disposeAllInnerSubscriptions(): void
    {
        foreach ($this->innerDisposables as $disposable) {
            $disposable->dispose();
        }
        $this->innerDisposables = [];
    }
}
