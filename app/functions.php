<?php

namespace App;

function getUniFiClient() {
	return new \UniFi_API\Client(
		$_ENV['UNIFI_USERNAME'],
		$_ENV['UNIFI_PASSWORD'],
		$_ENV['UNIFI_URL'],
		$_ENV['UNIFI_SITE_NAME'],
		$_ENV['UNIFI_VERSION'],
		strtoupper($_ENV['UNIFI_SSL']) === 'TRUE'
	);
}

function getServerGroupStatus() {
	$unifi = getUniFiClient();
	$unifi->login();
	return $unifi->list_usergroups();
}

function setGroupStatus($status) {
	file_put_contents(dirname(__DIR__) . '/groups.json', json_encode($status));
}

function getGroupStatus() {
	return json_decode(file_get_contents(dirname(__DIR__) . '/groups.json'), false);
}

function setServerGroupBandwidth($groupId, $groupName, $down, $up) {
	$unifi = getUniFiClient();
	$unifi->login();
	$unifi->edit_usergroup($groupId, $_ENV['UNIFI_SITE_NAME'], $groupName, $down, $up);
}
