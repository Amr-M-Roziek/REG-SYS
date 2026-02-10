<?php
function getPhoneCodeOptions($selected = '') {
    $codes = [
        "+971" => "UAE (+971)",
        "+966" => "Saudi Arabia (+966)",
        "+965" => "Kuwait (+965)",
        "+973" => "Bahrain (+973)",
        "+974" => "Qatar (+974)",
        "+968" => "Oman (+968)",
        "+20" => "Egypt (+20)",
        "+1" => "USA/Canada (+1)",
        "+44" => "UK (+44)",
        "+91" => "India (+91)",
        "+92" => "Pakistan (+92)",
        "+63" => "Philippines (+63)",
        "+86" => "China (+86)",
        "+33" => "France (+33)",
        "+49" => "Germany (+49)",
        "+39" => "Italy (+39)",
        "+34" => "Spain (+34)",
        "+7" => "Russia (+7)",
        "+81" => "Japan (+81)",
        "+82" => "South Korea (+82)",
        "+61" => "Australia (+61)",
        "+64" => "New Zealand (+64)",
        "+55" => "Brazil (+55)",
        "+27" => "South Africa (+27)"
    ];
    $html = '<option value="" disabled selected>Code</option>';
    foreach ($codes as $val => $label) {
        $sel = ($selected === $val) ? ' selected' : '';
        $html .= '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
    }
    $html .= '<option value="other">Other</option>';
    return $html;
}
?>
