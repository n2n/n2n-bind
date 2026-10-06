<?php

namespace n2n\bind\mapper\impl\pipe;
class RetryProcessResult {
	function __construct(public bool $valid, public bool $retryValueAvailable = false, public mixed $retryValue = null) {

	}
}