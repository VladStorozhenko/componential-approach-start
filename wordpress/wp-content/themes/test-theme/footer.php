<?php
wp_footer();

// Подключение скриптов
use builderScripts\inc\AdderBundles;

$scripts = new AdderBundles();
$scripts->print_source('js', get_the_ID() );
?>

</body>
</html>