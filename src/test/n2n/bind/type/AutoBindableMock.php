<?php
namespace n2n\bind\type;

use n2n\bind\Bindable;

class AutoBindableMock implements Bindable {
	private $firstname;
	private $dateTime;
	private $bindable1Mock;
	private $unknown;
	
	function __construct(string $firstname = null, \DateTime $dt = null) {
		$this->firstname = $firstname;
		$this->dateTime = $dt;
		$this->unknown = 'huii';
	}
	
	function getFirstname() {
		return $this->firstname;
	}
	
	function setFirstname(?string $firstname) {
		$this->firstname;	
	}
	
	function getDateTime() {
		return $this->dateTime;
	}
	
	function setDateTime(\DateTime $dateTime) {
		$this->dateTime = $dateTime;
	}
	
	function setBindable1Mock(Bindable1Mock $arg) {
		$this->bindable1Mock = $arg;
	}
	
	function getBindable1Mock() {
		return $this->bindable1Mock;
	}
	
	function getUnknown() {
		return $this->unknown;
	}
}