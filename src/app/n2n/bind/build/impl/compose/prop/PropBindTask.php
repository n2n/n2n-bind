<?php

namespace n2n\bind\build\impl\compose\prop;

use n2n\bind\plan\BindPlan;
use n2n\bind\plan\BindTarget;
use n2n\bind\plan\BindTask;
use n2n\util\magic\MagicTask;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\plan\BindSource;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\util\type\attrs\AttributeWriter;
use n2n\bind\build\impl\target\AttrsBindTarget;
use n2n\bind\build\impl\target\RefBindTarget;
use n2n\bind\build\impl\target\ObjectBindTarget;
use n2n\util\magic\TaskResult;
use n2n\util\magic\impl\MagicContexts;
use n2n\bind\plan\BindResult;

class PropBindTask extends PropBindComposer implements MagicTask {
	private BindTask $bindTask;

	/**
	 * @var \Closure[]
	 */
	private array $onSuccessCallbacks = [];

	function __construct(private BindSource $bindSource) {
		parent::__construct(new BindPlan());

		$this->bindTask = new BindTask($bindSource);
		$this->bindTask->addBindPlan($this->bindPlan);
	}

	function ifValid(): static {
		$this->bindPlan = new BindPlan();
		$this->bindTask->addBindPlan($this->bindPlan);
		return $this;
	}

	function toAttrs(AttributeWriter|\Closure $attributeWriter, bool $writeTargetOnFailure = false): static {
		return $this->to(new AttrsBindTarget($attributeWriter), $writeTargetOnFailure);
	}

	/**
	 * @param array $array
	 * @return PropBindTask
	 */
	function toArray(array &$array = [], bool $writeTargetOnFailure = false): static {
		return $this->to(new RefBindTarget($array, true), $writeTargetOnFailure);
	}

	/**
	 * @param $value
	 * @return PropBindTask
	 */
	function toValue(&$value, bool $writeTargetOnFailure = false): static {
		return $this->to(new RefBindTarget($value, false), $writeTargetOnFailure);
	}

	/**
	 * @param object $objOrCallback can also be a Closure
	 * @return PropBindTask
	 */
	function toObj(object $objOrCallback, bool $writeTargetOnFailure = false): static {
		return $this->to(new ObjectBindTarget($objOrCallback), $writeTargetOnFailure);
	}

	/**
	 * @param BindTarget $target
	 * @param bool $writeTargetOnFailure
	 * @return PropBindTask
	 */
	function to(BindTarget $target, bool $writeTargetOnFailure = false): static {
		$this->bindTask->setBindTarget($target);
		$this->bindTask->setWriteTargetOnFailure($writeTargetOnFailure);
		return $this;
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function exec(?MagicContext $magicContext = null, mixed $input = null): BindResult {
		$magicContext ??= MagicContexts::simple([]);

		$bindResult = $this->bindTask->exec($magicContext, $input);

		if ($bindResult->isValid()) {
			$this->triggerOnSuccessCallbacks($magicContext, $bindResult);
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

	private function triggerOnSuccessCallbacks(MagicContext $magicContext, TaskResult $taskResult): void {
		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClassParamObject(TaskResult::class, $taskResult);

		foreach ($this->onSuccessCallbacks as $onSuccessCallback) {
			$invoker->invoke(null, $onSuccessCallback, firstArgs: [$taskResult->get()]);
		}
	}




}