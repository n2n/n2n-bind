<?php

namespace n2n\bind\plan\impl;

use n2n\bind\plan\BindContext;
use n2n\util\type\attrs\AttributePath;
use n2n\l10n\Message;
use n2n\bind\plan\BindSource;
use n2n\bind\plan\BindInstance;

class RootBindContext implements BindContext {

	function __construct(private BindInstance $bindInstance) {
	}

	function addGeneralError(Message $message): void {
		$this->bindInstance->addGeneralError($message);
	}

	function getPath(): AttributePath {
		return new AttributePath([]);
	}
}