<?php

/**
 * Test Stock Status Detection Logic
 *
 * This standalone test verifies the stock status detection logic
 * without requiring the full Laravel environment.
 */

function determineStockStatus(?array $stockSummary): ?string
{
    if (!$stockSummary || !isset($stockSummary['status'])) {
        return null;
    }

    $stockStatus = strtolower(trim($stockSummary['status']));

    // Check for "out of stock" variations first (more specific)
    if (str_contains($stockStatus, 'out of stock') ||
        str_contains($stockStatus, 'out-of-stock') ||
        str_contains($stockStatus, 'outofstock') ||
        str_contains($stockStatus, 'out of  stock') ||
        str_contains($stockStatus, 'unavailable') ||
        str_contains($stockStatus, 'discontinued') ||
        str_contains($stockStatus, 'not available') ||
        str_contains($stockStatus, 'sold out') ||
        preg_match('/\bout\b.*\bstock\b/', $stockStatus)) {
        return 'out_of_stock';
    }
    // Check for "in stock" variations
    elseif (str_contains($stockStatus, 'in stock') ||
            str_contains($stockStatus, 'in-stock') ||
            str_contains($stockStatus, 'instock') ||
            str_contains($stockStatus, 'available') ||
            str_contains($stockStatus, 'ships') ||
            preg_match('/\bin\b.*\bstock\b/', $stockStatus)) {
        return 'in_stock';
    }

    // Default to in_stock for unknown statuses
    return 'in_stock';
}

// Test cases
$testCases = [
    // Out of stock variations
    [['status' => 'Out of Stock'], 'out_of_stock'],
    [['status' => 'OUT OF STOCK'], 'out_of_stock'],
    [['status' => 'out of stock'], 'out_of_stock'],
    [['status' => 'out-of-stock'], 'out_of_stock'],
    [['status' => 'outofstock'], 'out_of_stock'],
    [['status' => 'out of  stock'], 'out_of_stock'],
    [['status' => 'Unavailable'], 'out_of_stock'],
    [['status' => 'unavailable'], 'out_of_stock'],
    [['status' => 'Discontinued'], 'out_of_stock'],
    [['status' => 'Not Available'], 'out_of_stock'],
    [['status' => 'not available'], 'out_of_stock'],
    [['status' => 'Sold Out'], 'out_of_stock'],
    [['status' => 'sold out'], 'out_of_stock'],
    [['status' => 'Currently out stock'], 'out_of_stock'],

    // In stock variations
    [['status' => 'In Stock'], 'in_stock'],
    [['status' => 'IN STOCK'], 'in_stock'],
    [['status' => 'in stock'], 'in_stock'],
    [['status' => 'in-stock'], 'in_stock'],
    [['status' => 'instock'], 'in_stock'],
    [['status' => 'Available'], 'in_stock'],
    [['status' => 'available'], 'in_stock'],
    [['status' => 'Ships in 24 hours'], 'in_stock'],
    [['status' => 'ships'], 'in_stock'],
    [['status' => 'Currently in stock'], 'in_stock'],

    // Edge cases
    [['status' => ''], 'in_stock'],
    [['status' => '   '], 'in_stock'],
    [['status' => 'Unknown Status'], 'in_stock'],
];

$nullCases = [
    null,
    [],
    ['other_field' => 'value'],
];

echo "Testing Stock Status Detection Logic\n";
echo "=====================================\n\n";

$passed = 0;
$failed = 0;

// Test regular cases
foreach ($testCases as $test) {
    [$input, $expected] = $test;
    $result = determineStockStatus($input);
    $status = $result === $expected ? '✅ PASS' : '❌ FAIL';

    if ($result === $expected) {
        $passed++;
    } else {
        $failed++;
        echo "$status | Input: '{$input['status']}' | Expected: $expected | Got: $result\n";
    }
}

// Test null cases
foreach ($nullCases as $input) {
    $result = determineStockStatus($input);
    $status = $result === null ? '✅ PASS' : '❌ FAIL';

    if ($result === null) {
        $passed++;
    } else {
        $failed++;
        $inputStr = json_encode($input);
        echo "$status | Input: $inputStr | Expected: NULL | Got: $result\n";
    }
}

echo "\n";
echo "=====================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: $passed ✅\n";
echo "Failed: $failed " . ($failed > 0 ? '❌' : '✅') . "\n";
echo "=====================================\n";

if ($failed === 0) {
    echo "\n🎉 All tests passed! Stock status detection is working correctly.\n";
} else {
    echo "\n⚠️ Some tests failed. Please review the logic.\n";
    exit(1);
}

