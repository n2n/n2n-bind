<?php

namespace n2n\bind\build\impl\compose\prop;

use n2n\bind\plan\BindPlan;
use n2n\bind\plan\BindableTarget;
use n2n\bind\plan\BindTask;
use n2n\util\magic\MagicTask;
use n2n\bind\plan\BindResult;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\impl\SimpleBindResult;
use n2n\bind\err\BindTargetException;
use n2n\bind\plan\BindSource;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

class PropBindTask extends PropBindComposer implements MagicTask {
	private BindTask $bindTask;

	function __construct(private BindSource $bindSource, BindableTarget $bindableTarget) {
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
		return $this->bindTask->exec($magicContext);
	}

}