<?php
/**
 * Albaraka MacNew Debug Script - Updated for 14-field theory
 */

// Test config values
$eposNo = '1010174801275903';
$merchantNo = '6701665447';
$terminalNo = '67264095';
$encKey = "10,10,10,10,10,10,10,10";
$testOrderId = '00KP-2026-04-27-0014';
$tutarKurus = 5000;
$testUrl = 'https://2026.kestanepazari.org.tr/bagis/albaraka/callback';

// Convert ENCKEY
$encKeyBinary = '';
foreach (explode(',', trim($encKey)) as $byte) {
    $encKeyBinary .= chr((int) trim($byte));
}

echo "=== ALBARAKA MACNEW DEBUG (14-field theory) ===\n";
echo "EPOS: $eposNo\n";
echo "Merchant: $merchantNo\n";
echo "Terminal: $terminalNo\n";
echo "OrderId: $testOrderId\n\n";

// Theory 1: 14 core fields ONLY (no optional fields)
echo "=== 14-Field Core (Current Fix) ===\n";
$payload_14 = [
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
    $testUrl,
    'TR',
    'TL',
];
$joined = implode(';', $payload_14);
echo "Payload:\n$joined\n\n";
$mac14Binary = base64_encode(hash_hmac('sha256', $joined, $encKeyBinary, true));
echo "MacNew (binary key): $mac14Binary\n";
echo "Length: " . strlen($mac14Binary) . "\n\n";

// Theory 2: 18 fields with ENCKEY as raw string (not binary)
echo "=== 18-Field with Raw ENCKEY ===\n";
$payload_18 = [
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
    $testUrl,
    'TR',
    'TL',
    '0',
    '0',
    '0',
    'INITIAL',
];
$joined18 = implode(';', $payload_18);
echo "Payload (first 100 chars):\n" . substr($joined18, 0, 100) . "...\n\n";
$mac18RawKey = base64_encode(hash_hmac('sha256', $joined18, trim($encKey), true));
echo "MacNew (raw ENCKEY): $mac18RawKey\n";
echo "Length: " . strlen($mac18RawKey) . "\n\n";

// Theory 3: Different field order - maybe ExpireDate is before CardNo?
echo "=== Different Field Order (Expiry before Card) ===\n";
$payload_alt = [
    $eposNo,
    $merchantNo,
    $terminalNo,
    $testOrderId,
    'Sale',
    '2601',      // ExpireDate FIRST
    '4111111111111111',  // CardNo SECOND
    '000',
    'TEST USER',
    (string)$tutarKurus,
    '0',
    $testUrl,
    'TR',
    'TL',
];
$joinedAlt = implode(';', $payload_alt);
$macAlt = base64_encode(hash_hmac('sha256', $joinedAlt, $encKeyBinary, true));
echo "MacNew: $macAlt\n\n";

// Theory 4: With empty card fields (UseOOS=1)
echo "=== 14-Field Core with Empty Card (UseOOS=1) ===\n";
$payload_empty = [
    $eposNo,
    $merchantNo,
    $terminalNo,
    $testOrderId,
    'Sale',
    '',      // Empty CardNo
    '',      // Empty ExpireDate
    '',      // Empty CVV
    '',      // Empty CardHolderName
    (string)$tutarKurus,
    '0',
    $testUrl,
    'TR',
    'TL',
];
$joinedEmpty = implode(';', $payload_empty);
echo "Payload:\n$joinedEmpty\n\n";
$macEmpty = base64_encode(hash_hmac('sha256', $joinedEmpty, $encKeyBinary, true));
echo "MacNew: $macEmpty\n\n";

// Theory 5: Maybe spaces/encoding issue - what if Amount comes without conversion
echo "=== Amount as Integer (not string) ===\n";
$payload_intamt = [
    $eposNo,
    $merchantNo,
    $terminalNo,
    $testOrderId,
    'Sale',
    '4111111111111111',
    '2601',
    '000',
    'TEST USER',
    $tutarKurus,  // As integer, not string
    '0',
    $testUrl,
    'TR',
    'TL',
];
$joinedIntAmt = implode(';', $payload_intamt);
$macIntAmt = base64_encode(hash_hmac('sha256', $joinedIntAmt, $encKeyBinary, true));
echo "MacNew: $macIntAmt\n\n";

echo "=== SUMMARY ===\n";
echo "14-field (binary):  $mac14Binary\n";
echo "18-field (rawkey):  $mac18RawKey\n";
echo "Alt order:          $macAlt\n";
echo "Empty card:         $macEmpty\n";
echo "Int amount:         $macIntAmt\n";
