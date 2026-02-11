<?php
$id = '202102734';
$salt = 'ICPM2026_Secure_Salt';
$target = 'b920703554b7bd313b76244c4452bc5f';

$variations = [
    'standard' => $id,
    'leading_space' => " " . $id,
    'trailing_space' => $id . " ",
    'leading_zero' => "0" . $id,
    'int_cast' => (int)$id,
];

echo "Target Hash: $target\n";
echo "Standard Salt: '$salt'\n\n";

foreach ($variations as $name => $val) {
    $hash = md5($val . $salt);
    echo "Variation: $name\n";
    echo "Value: '$val'\n";
    echo "Hash: $hash\n";
    if ($hash === $target) echo "MATCH FOUND!\n";
    echo "----------------\n";
}

// Check Salt variations
$salt_variations = [
    'space_end' => 'ICPM2026_Secure_Salt ',
    'space_start' => ' ICPM2026_Secure_Salt',
    'no_underscore_1' => 'ICPM2026Secure_Salt',
    'no_underscore_2' => 'ICPM2026_SecureSalt',
    'lower_s' => 'ICPM2026_secure_salt',
];

foreach ($salt_variations as $name => $s) {
    $hash = md5($id . $s);
    echo "Salt Variation: $name\n";
    echo "Salt: '$s'\n";
    echo "Hash: $hash\n";
    if ($hash === $target) echo "MATCH FOUND!\n";
    echo "----------------\n";
}
?>
