<?php

namespace App;

use PiPHP\GPIO\GPIO;
use PiPHP\GPIO\Pin\InputPinInterface;
use PiPHP\GPIO\Pin\PinInterface;

try {
    // Create a GPIO object
    $gpio = new GPIO();

    // Create an interrupt watcher
    $interruptWatcher = $gpio->createWatcher();
    $statusLed = $gpio->getOutputPin(17);
    $statusLed->setValue(PinInterface::VALUE_HIGH);

    $children = [];

		foreach([1, 2] as $childId) {
			$name = $_ENV['CHILD_' . $childId . '_NAME'];
			echo "Registering " . $name . PHP_EOL;
			$button = $gpio->getInputPin($_ENV['CHILD_' . $childId . '_BUTTON']);
			$button->setEdge(InputPinInterface::EDGE_FALLING);
			$led = $gpio->getOutputPin($_ENV['CHILD_' . $childId . '_LED']);
			$child = new Child(
				$childId,
				$name,
				$button,
				$led,
				$_ENV['CHILD_' . $childId . '_BANDWIDTH_PROFILE'],
				$_ENV['CHILD_' . $childId . '_BANDWIDTH_DOWN'],
				$_ENV['CHILD_' . $childId . '_BANDWIDTH_UP'],
				strtolower($_ENV['CHILD_' . $childId . '_BANNED']) === "true"
			);
			$interruptWatcher->register($button, [$child, 'handlePress']);
			$children[$name] = $child;
		}

		echo "Updating..." . PHP_EOL;
		setGroupStatus(getServerGroupStatus());

    echo "Listening..." . PHP_EOL;

    // Listen.
    while ($interruptWatcher->watch(50)) {
        foreach ($children as $child) {
            $child->maybeUpdateLed();
            $statusLed->setValue((int) (time() % 5 === 0 && fmod(microtime(true), 1) < 0.1));
						$groupStatus = getGroupStatus();
						foreach ($groupStatus as $group) {
							if ($child->group === $group->_id) {
								$disabled = $group->qos_rate_max_down === 2 || $group->qos_rate_max_up === 2;
								if (in_array($child->status, ['unknown', 'enabled', 'disabling']) && $disabled) {
									$child->setStatus('disabled');
								} elseif(in_array($child->status, ['unknown', 'disabled', 'enabling']) && !$disabled) {
									$child->setStatus('enabled');
								}
							}
						}
        }
    }
} catch(\Exception $e) {
	$statusLed->setValue(PinInterface::VALUE_LOW);
}

$statusLed->setValue(PinInterface::VALUE_LOW);
echo "Exiting..." . PHP_EOL;
