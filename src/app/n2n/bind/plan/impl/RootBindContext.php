<?php

namespace n2n\bind\plan\impl;

use n2n\bind\plan\BindContext;
use n2n\util\type\attrs\AttributePath;
use n2n\l10n\Message;
use n2n\bind\plan\BindSource;

class RootBindContext implements BindContext {

	function __construct(private BindSource $bindSource) {
	}

	function addGeneralError(Message $message): void {
		$this->bindSource->addGeneralError($message);
	}

	function getPath(): AttributePath {
		return new AttributePath([]);
	}
}