<?php

	$vmailer = new vMailer(array(
		'subject' => 'subject',
		'message' => 'html messages',
		'plain_message' => 'plain message'
	));

	$res = $vmailer->send('vcrazy@abv.bg'); // to

	var_dump($res);
