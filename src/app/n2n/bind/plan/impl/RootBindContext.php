<?php

namespace n2n\bind\plan\impl;

use n2n\util\type\attrs\AttributePath;
use n2n\l10n\Message;
use n2n\bind\plan\BindContextAdapter;
use n2n\bind\plan\BindInstance;
use n2n\bind\plan\BindTargetInstance;

class RootBindContext extends BindContextAdapter {

	function __construct(private readonly BindInstance $bindInstance,
			private readonly BindTargetInstance $bindTargetInstance) {
		parent::__construct($this->bindInstance, $this->bindTargetInstance);
	}

	function addGeneralError(Message $message): void {
		$this->bindInstance->addGeneralError($message);
	}

	function getPath(): AttributePath {
		return new AttributePath([]);
	}


}
