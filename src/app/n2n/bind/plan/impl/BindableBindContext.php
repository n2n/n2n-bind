<?php

namespace n2n\bind\plan\impl;

use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\l10n\Message;
use n2n\util\type\attrs\AttributePath;

class BindableBindContext implements BindContext {

	function __construct(private Bindable $bindable) {
	}

	function addGeneralError(Message $message): void {
		$this->bindable->addError($message);
	}

	function getPath(): AttributePath {
		return $this->bindable->getPath();
	}

}