<?php
namespace n2n\bind\type;

class UnbindableMock {
	private $atusch;
	
	public function getAtusch() {
		return $this->atusch;
	}
	
	public function setAtusch($atusch) {
		$this->atusch = $atusch;
	}
}