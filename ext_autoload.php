<?php

$extpath = t3lib_extMgm::extPath('simplemvc');
return array(
	'tx_simplemvc_abstractcontroller' => $extpath . 'controllers/class.tx_simplemvc_abstractcontroller.php',
	'tx_simplemvc_abstractmodel' => $extpath . 'models/class.tx_simplemvc_abstractmodel.php',
	'tx_simplemvc_abstractview' => $extpath . 'views/class.tx_simplemvc_abstractview.php',
	'tx_simplemvc_ajaxcontroller' => $extpath . 'controllers/class.tx_simplemvc_ajaxcontroller.php',
	'tx_simplemvc_cache' => $extpath . 'cache/class.tx_simplemvc_cache.php',
	'tx_simplemvc_fecontroller' => $extpath . 'controllers/class.tx_simplemvc_fecontroller.php',
	'tx_simplemvc_fegroup' => $extpath . 'models/class.tx_simplemvc_fegroup.php',
	'tx_simplemvc_feuser' => $extpath . 'models/class.tx_simplemvc_feuser.php',
	'tx_simplemvc_typoscriptview' => $extpath . 'views/class.tx_simplemvc_typoscriptview.php',
);

?>
