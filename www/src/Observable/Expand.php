<?php

namespace Peter\Observable;

use Rx\Operator\OperatorInterface;
use Rx\DisposableInterface;
use Rx\ObservableInterface;
use Rx\ObserverInterface;
use Rx\Observable;

function tryCatchResult($a, $b, $c = false) {
	print_r($a);
	print_r($b);
	print_r($c);
}


function projectToObservable($project, $v, $i): Observable {
	$symbolError = "";
	$result = gettype($project) === "function" ? tryCatchResult($project, $v, $i) : $v;
   
	if ($result === $symbolError) {
		return Observable::error((new \Exception('Oops!')));
	}

	$obs = tryCatchResult(Observable::fromArray, $result);
	if ($obs === $symbolError) {
    	return Observable::error((new \Exception('Oops!')));
	}
	return $obs;
}



class Expand implements OperatorInterface {

	public $destination;
	public $project;
	public int $concurrent;
	public $innerSubs;
	public $buffer;
	public int $index;
	public int $active;
	public bool $hasCompleted;
	public bool $closed;
	public $subscription;


	 public function __construct(
		ISubscriptionObserver $destination,
		$project,
		int $concurrent
	)
	{
		$this->destination = $destination;
    	$this->project = $project;
    	$this->concurrent = $concurrent;
    	//$this->innerSubs = new Set();
    	$this->buffer = [];
    	$this->index = 0;
    	$this->active = 0;
    	$this->hasCompleted = false;
    	$this->closed = false;
    	$this->subscription = null;
	}


	public function start(ISubscription $subscription ): void {
		$this->subscription = $subscription;
	}

	public function next($value): void {
		if ($this->active < $this->concurrent) {
			$this->projectTo($value);
		}
		else {
			$this->buffer[] = $value;
		}
	}

	public function error($e): void
	{
		$this->destination->error($e);
		foreach($this->innerSubs as $s) {
			 $s->unsubscribe();
		}
		
		$this->innerSubs = null;
		$this->closed = true;
	}

	public function complete(): void 
	{
		$this->hasCompleted = true;
		if ($this->active === 0) {
      		$this->destination->complete();
			$this->closed = true;
		}
	}

	public function projectTo($value): void 
	{
		$this->destination->next($value);
		$i = $this->index++;
    	$obs = projectToObservable($this->project, $value, $i);
    	$innerSub = new InnerSubscriber($this, $value, $i);
		$this->innerSubs.add($innerSub);
		$this->active++;
		$obs->subscribe($innerSub);
	}

	public function notifyNext(
		$outerValue,
		$innerValue,
    	int $outerIndex,
    	int $innerIndex,
    	InnerSubscriber $innerSub
	): void 
	{
    	$this->next($innerValue);
	}

	public function notifyError($e, InnerSubscriber $innerSub): void {
    	$this->destination->error($e);
    	$this->innerSubs->delete($innerSub);
	}

	public function notifyComplete(InnerSubscriber $innerSub): void {
		$this->active--;
		$this->innerSubs->delete($innerSub);
		if (count($this->buffer) !== 0) {
			$this->projectTo($this->buffer->shift());
		}
		else if ($this->hasCompleted && $this->active === 0) {
			$this->destination->complete();
		}
	}

	public function unsubscribe(): void 
	{
		if ($this->closed) {
			return;
		}

		if ($this->subscription !== null) {
			$this->subscription->unsubscribe();
		}

		foreach($this->innerSubs as $s) {
			$s->unsubscribe();
		}
    	
		$this->innerSubs->clear();
		$this->closed = true;
	}


	public function __invoke(ObservableInterface $observable, ObserverInterface $observer): DisposableInterface
    {

	}


}