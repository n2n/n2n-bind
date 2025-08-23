<?php

namespace n2n\bind\plan\impl;

use n2n\bind\plan\BindContext;
use n2n\util\type\attrs\AttributePath;
use n2n\l10n\Message;
use n2n\bind\plan\BindSource;
use n2n\bind\plan\BindableFactory;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\plan\BindContextAdapter;
use n2n\bind\build\impl\source\BindInstance;

class RootBindContext extends BindContextAdapter {

	function __construct(private readonly BindInstance $bindInstance) {
		parent::__construct($this->bindInstance);
	}

	function addGeneralError(Message $message): void {
		$this->bindInstance->addGeneralError($message);
	}

	function getPath(): AttributePath {
		return new AttributePath([]);
	}


}
