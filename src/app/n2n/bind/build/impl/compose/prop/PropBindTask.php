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

class PropBindTask extends PropBindComposer implements MagicTask {

	function __construct(private PropBindSource $bindableSource, BindableTarget $bindableTarget) {
		$bindPlan = new BindPlan();
		parent::__construct($bindPlan);
		$this->bindTask = new BindTask($bindableSource, $bindableTarget, $bindPlan);
	}

	/**
	 * @throws BindTargetException
	 */
	function exec(MagicContext $magicContext): BindResult {
		return $this->bindTask->exec($magicContext);
	}

}