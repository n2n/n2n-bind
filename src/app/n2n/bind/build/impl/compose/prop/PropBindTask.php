<?php

namespace n2n\bind\build\impl\compose\prop;

use n2n\bind\plan\BindPlan;
use n2n\bind\plan\BindTarget;
use n2n\bind\plan\BindTask;
use n2n\util\magic\MagicTask;
use n2n\bind\plan\BindResult;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\impl\SimpleBindResult;
use n2n\bind\err\BindTargetException;
use n2n\bind\plan\BindSource;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\reflection\magic\MagicMethodInvoker;

class PropBindTask extends PropBindComposer implements MagicTask {
	private BindTask $bindTask;

	/**
	 * @var \Closure[]
	 */
	private array $onSuccessCallbacks = [];

	function __construct(private BindSource $bindSource, BindTarget $bindableTarget) {
		$bindPlan = new BindPlan();
		parent::__construct($bindPlan);
		$this->bindTask = new BindTask($bindSource, $bindableTarget, $bindPlan);
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function exec(MagicContext $magicContext): BindResult {
		$bindResult = $this->bindTask->exec($magicContext);

		if (!$bindResult->hasErrors()) {
			$this->triggerOnSuccessCallbacks($magicContext);
		}

		return $bindResult;
	}

	function onSuccess(\Closure $onSuccessCallback): static {
		$this->onSuccessCallbacks[spl_object_hash($onSuccessCallback)] = $onSuccessCallback;
		return $this;
	}

	function offSuccess(\Closure $onSuccessCallback): static {
		unset($this->onSuccessCallbacks[spl_object_hash($onSuccessCallback)]);
		return $this;
	}

	private function triggerOnSuccessCallbacks(MagicContext $magicContext): void {
		foreach ($this->onSuccessCallbacks as $onSuccessCallback) {
			$invoker = new MagicMethodInvoker($magicContext);
			$invoker->invoke(null, $onSuccessCallback);
		}
	}




}