<?php
/**
 * Albaraka MacNew Debug Script
 * Test different payload variations to identify E216 root cause
 */

// Test config values (from .env)
$eposNo = '1010174801275903';
$merchantNo = '6701665447';
$terminalNo = '67264095';
$encKey = "10,10,10,10,10,10,10,10";  // Byte-list format
$testOrderId = '00KP-2026-04-27-0014';
$tutarKurus = 5000;

// Convert ENCKEY (byte list format)
$encKeyBinary = '';
foreach (explode(',', trim($encKey)) as $byte) {
    $encKeyBinary .= chr((int) trim($byte));
}

echo "=== ALBARAKA MACNEW DEBUG ===\n";
echo "EPOS: $eposNo\n";
echo "Merchant: $merchantNo\n";
echo "Terminal: $terminalNo\n";
echo "OrderId: $testOrderId\n";
echo "Tutar: $tutarKurus\n";
echo "ENCKEY (binary): " . bin2hex($encKeyBinary) . "\n\n";

// Test different payload configurations
$payloads = [
    'config_18_fields' => [
        $eposNo,
        $merchantNo,
        $terminalNo,
        $testOrderId,
        'Sale',
        '4111111111111111',  // Test card
        '2601',              // Expiry YYAA
        '000',               // CVV
        'TEST USER',         // Cardholder
        (string)$tutarKurus,
        '0',                 // InstallmentCount
        'https://2026.kestanepazari.org.tr/bagis/albaraka/callback',
        'TR',                // Language
        'TL',                // Currency
        '0',                 // UseJokerVadaa
        '0',                 // OpenNewWindow
        '0',                 // UseOOS
        'INITIAL',           // TxnState
    ],
    'core_14_fields' => [
        $eposNo,
        $merchantNo,
        $terminalNo,
        $testOrderId,
        'Sale',
        '4111111111111111',  // Test card
        '2601',              // Expiry
        '000',               // CVV
        'TEST USER',         // Cardholder
        (string)$tutarKurus,
        '0',                 // InstallmentCount
        'https://2026.kestanepazari.org.tr/bagis/albaraka/callback',
        'TR',                // Language
        'TL',                // Currency
    ],
    'without_optional' => [
        $eposNo,
        $merchantNo,
        $terminalNo,
        $testOrderId,
        'Sale',
        '4111111111111111',
        '2601',
        '000',
        'TEST USER',
        (string)$tutarKurus,
        '0',
        'https://2026.kestanepazari.org.tr/bagis/albaraka/callback',
        'TR',
        'TL',
    ],
];

foreach ($payloads as $name => $payload) {
    echo "=== Payload: $name (" . count($payload) . " fields) ===\n";
    
    $joined = implode(';', $payload);
    echo "Payload (semicolon-joined):\n";
    echo $joined . "\n\n";
    
    $mac = base64_encode(hash_hmac('sha256', $joined, $encKeyBinary, true));
    echo "MacNew: $mac\n";
    echo "Length: " . strlen($mac) . "\n";
    
    // Also test with different ENCKEY interpretation
    $macWithRawKey = base64_encode(hash_hmac('sha256', $joined, $encKey, true));
    echo "MacNew (raw key): $macWithRawKey\n\n";
}

// Test with empty card fields (UseOOS=1, so no card)
echo "=== Payload: UseOOS=1 (no card fields) ===\n";
$payload_oos = [
    $eposNo,
    $merchantNo,
    $terminalNo,
    $testOrderId,
    'Sale',
    '',  // Empty CardNo
    '',  // Empty ExpiredDate
    '',  // Empty CVV
    '',  // Empty CardHolderName
    (string)$tutarKurus,
    '0',
    'https://2026.kestanepazari.org.tr/bagis/albaraka/callback',
    'TR',
    'TL',
    '0',
    '0',
    '1',  // UseOOS
    'INITIAL',
];
$joined = implode(';', $payload_oos);
echo "Payload:\n" . $joined . "\n\n";
$mac = base64_encode(hash_hmac('sha256', $joined, $encKeyBinary, true));
echo "MacNew: $mac\n";
echo "Length: " . strlen($mac) . "\n";
