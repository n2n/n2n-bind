<?php
namespace n2n\bind\validation;

use n2n\l10n\Message;

interface ValidatableProperty {
	
	/**
	 * @return mixed 
	 */
	function getValue();
	
	/**
	 * @return bool
	 */
	function isValid(): bool;
	
	/**
	 * @param Message $message
	 */
	function addError(Message $message);
}