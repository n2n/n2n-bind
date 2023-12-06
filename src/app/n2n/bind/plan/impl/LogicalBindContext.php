<?php

namespace n2n\bind\plan\impl;

use n2n\bind\plan\BindContext;
use n2n\l10n\Message;
use n2n\util\type\attrs\AttributePath;

class LogicalBindContext implements BindContext {

	function __construct(private AttributePath $contextPath, private BindContext $parentBindContext) {

	}
	function getPath(): AttributePath {
		return $this->contextPath;
	}

	function addGeneralError(Message $message): void {
		$this->parentBindContext->addGeneralError($message);
	}
}