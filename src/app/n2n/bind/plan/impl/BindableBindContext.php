<?php

namespace n2n\bind\plan\impl;

use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\l10n\Message;
use n2n\util\type\attrs\AttributePath;
use n2n\bind\plan\BindInstance;
use n2n\bind\plan\BindContextAdapter;

class BindableBindContext extends BindContextAdapter {

	function __construct(private Bindable $bindable, BindInstance $bindInstance) {
		parent::__construct($bindInstance);
	}

	function addGeneralError(Message $message): void {
		$this->bindable->addError($message);
	}

	function getPath(): AttributePath {
		return $this->bindable->getPath();
	}

}