<?php

namespace App;

use PiPHP\GPIO\Pin\PinInterface;
use PiPHP\GPIO\Pin\InputPinInterface;
use PiPHP\GPIO\Pin\OutputPinInterface;

class Child {
	public $ordinalId;
	public $button;
	public $led;
	public $name;
	public $status = 'unknown';
	public $ignoreUntil = 0;
	public $up = 5120;
	public $down = 5120;
	public $group = '';
	public $banned = false;

	public function __construct(int $ordinalId, string $name, InputPinInterface $button, OutputPinInterface $led, string $group, int $down, int $up, bool $banned) {
		$this->ordinalId = $ordinalId;
		$this->name = $name;
		$this->button = $button;
		$this->led = $led;
		$this->group = $group;
		$this->down = $down;
		$this->up = $up;
		$this->banned = $banned;
	}

	public function ledOn() {
		$this->led->setValue(PinInterface::VALUE_HIGH);
	}

	public function ledOff() {
		$this->led->setValue(PinInterface::VALUE_LOW);
	}

	public function handlePress($pin, $value) {
		$oldStatus = $this->status;

		// Ignore liftoffs and rapid-presses.
		if ($this->ignored() || $value) {
			return true;
		} else {
			$this->ignoreForSeconds(1);
		}

		switch($this->status) {
			case 'disabled':
				if (!$this->banned) {
					$this->setStatus('enabling');
					$this->enable();
				}
				break;
			case 'enabled':
				$this->setStatus('disabling');
				$this->disable();
				break;
			case 'enabling':
				$this->setStatus('disabling');
				$this->disable();
				break;
			case 'disabling':
				if (!$this->banned) {
					$this->setStatus('enabling');
					$this->enable();
				}
				break;
		}
		echo $this->name . ' changed from ' . $oldStatus . ' to ' . $this->status . PHP_EOL;
	}

	public function ignored() {
		return $this->ignoreUntil > microtime(true);
	}

	public function ignoreForSeconds(float $ignoreTime) {
		$this->ignoreUntil = microtime(true) + $ignoreTime;
	}

	public function setStatus(string $status) {
		$this->status = $status;
		if ('enabled' === $status) {
			$this->ledOn();
		} elseif ('disabled' === $status) {
			$this->ledOff();
		}
	}

	public function maybeUpdateLed() {
		if (in_array($this->status, ['enabling', 'disabling'])) {
			if (fmod(microtime(true), 1) < 0.5) {
				$this->ledOn();
			} else {
				$this->ledOff();
			}
		}
	}

	public function enable() {
		exec('php ' . dirname(__DIR__) . '/set.php ' . $this->ordinalId . ' 1 > /dev/null &');
	}

	public function disable() {
		exec('php ' . dirname(__DIR__) . '/set.php ' . $this->ordinalId . ' 0 > /dev/null &');
	}
}