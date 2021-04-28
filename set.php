<?php

namespace App;

require __DIR__ . '/boot.php';

$child = (int) $argv[1];
$enabled = (bool) (int) $argv[2];
$up = $enabled ? ((int) $_ENV['CHILD_' . $child . '_BANDWIDTH_UP']) : 2;
$down = $enabled ? ((int) $_ENV['CHILD_' . $child . '_BANDWIDTH_DOWN']) : 2;
$groupId = $_ENV['CHILD_' . $child . '_BANDWIDTH_PROFILE'];
$groupName = $_ENV['CHILD_' . $child . '_NAME'];
$banned = strtolower($_ENV['CHILD_' . $child . '_BANNED']) === true;

if (!$enabled || !$banned) {
	setServerGroupBandwidth($groupId, $groupName, $down, $up);
	setGroupStatus(getServerGroupStatus());
}
