<?php
$installer = $this;
$installer->startSetup();

$installer->addAttribute("order", "jad_log_embarcador_response_or_status", array("type"=>"varchar"));
$installer->endSetup();
