<?php

namespace n2n\bind\plan\impl;

use n2n\bind\plan\Bindable;
use n2n\l10n\Message;
use n2n\util\type\attrs\AttributePath;
use n2n\bind\plan\BindContextAdapter;
use n2n\bind\plan\BindInstance;
use n2n\bind\plan\BindTargetInstance;

class BindableBindContext extends BindContextAdapter {

	function __construct(private Bindable $bindable, BindInstance $bindInstance, BindTargetInstance $bindTargetInstance) {
		parent::__construct($bindInstance, $bindTargetInstance);
	}

	function addGeneralError(Message $message): void {
		$this->bindable->addError($message);
	}

	function getPath(): AttributePath {
		return $this->bindable->getPath();
	}

}