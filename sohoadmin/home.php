<?php
echo "hello, world.";
include('config/testinclude.inc.php');
if ( $_SERVER['REMOTE_ADDR'] == '75.144.44.25' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1' ) {echo '<p>'.basename(__FILE__).': '.__LINE__.'</p>'; exit; }
?>