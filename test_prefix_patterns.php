<?php

// Test different prefix patterns
$testPrefixes = [
    'Ryan',           // Single word -> Ryan, Ryan
    'RyanL',          // PascalCase short -> Ryan, L
    'RyanLeavesley',  // PascalCase long -> Ryan, Leavesley
    'info',           // lowercase single -> Info, Info
    'contact',        // lowercase single -> Contact, Contact
    'john.doe',       // dot separated -> John, Doe
    'sales',          // lowercase -> Sales, Sales
    'SalesTeam',      // PascalCase -> Sales, Team
    'supportTeam',    // camelCase -> Support, Team
];

echo "Testing prefix name splitting logic:\n";
echo str_repeat('=', 80) . "\n";
printf("%-20s | %-20s | %-20s\n", "Prefix", "First Name", "Last Name");
echo str_repeat('=', 80) . "\n";

foreach ($testPrefixes as $prefix) {
    $firstName = '';
    $lastName = '';
    $namesSplitFromPrefix = false;
    
    // If prefix contains a dot (e.g., "mitsu.bee"), split by dot
    if (strpos($prefix, '.') !== false) {
        $parts = explode('.', $prefix, 2);
        $firstName = ucfirst(str_replace('.', '', $parts[0]));
        $lastName = ucfirst(str_replace('.', '', $parts[1]));
        $namesSplitFromPrefix = true;
    }
    // Try to find capital letter in the middle for compound names (e.g., RyanLeavesley -> Ryan, Leavesley)
    elseif (preg_match('/^([A-Z][a-z]+)([A-Z][a-z]+.*)$/', $prefix, $matches)) {
        $firstName = $matches[1]; // Ryan
        $lastName = $matches[2];  // Leavesley
        $namesSplitFromPrefix = true;
    }
    // If prefix has PascalCase short form (e.g., RyanL -> Ryan, L)
    elseif (preg_match('/^([A-Z][a-z]+)([A-Z][a-z]*)$/', $prefix, $matches)) {
        $firstName = $matches[1]; // Ryan
        $lastName = $matches[2] ?: $matches[1]; // L or Ryan if no second part
        $namesSplitFromPrefix = true;
    }
    // If it starts with lowercase and has uppercase (e.g., ryanL)
    elseif (preg_match('/^([a-z]+)([A-Z].*)$/', $prefix, $matches)) {
        $firstName = ucfirst($matches[1]); // Ryan
        $lastName = $matches[2];           // L
        $namesSplitFromPrefix = true;
    }
    // If it's a single word (no capitals in the middle), use it for both first and last
    elseif (preg_match('/^[A-Z][a-z]+$/', $prefix) || preg_match('/^[a-z]+$/', $prefix)) {
        $firstName = ucfirst($prefix);
        $lastName = ucfirst($prefix);
        $namesSplitFromPrefix = true;
    }
    
    if (!$namesSplitFromPrefix) {
        $firstName = $prefix;
        $lastName = $prefix;
    }
    
    // Remove any remaining dots
    $firstName = str_replace('.', '', $firstName);
    $lastName = str_replace('.', '', $lastName);
    
    printf("%-20s | %-20s | %-20s\n", $prefix, $firstName, $lastName);
}

echo str_repeat('=', 80) . "\n";
