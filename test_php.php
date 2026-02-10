<?php
echo "PHP is working.\n";
if (function_exists('mysqli_connect')) {
    echo "mysqli is enabled.\n";
} else {
    echo "mysqli is NOT enabled.\n";
}
?>
