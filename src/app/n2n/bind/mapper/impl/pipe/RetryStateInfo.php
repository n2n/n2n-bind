<?php

namespace n2n\bind\mapper\impl\pipe;

class RetryStateInfo {
	public bool $fallbackApplied = false;
	public bool $fillStrApplied = false;

	public bool $modifiedAtLeastOnce = false;
	public bool $validatedAtLeastOnce = false;
}