<?php
$content = file_get_contents('tests/Unit/Domains/Security/Services/SuspiciousActivityDetectorTest.php');

// Fix the first problematic array (around line 68)
$content = preg_replace(
    '/(\s*\$activities = \[\s*\n\s*\[\'action_type\' => \'auth\.login\.failed\'[^\]]*\]),\s*(\[\'action_type\'[^\]]*\]),\s*(\[\'action_type\'[^\]]*\]),\s*(\[\'action_type\'[^\]]*\]),\s*(\[\'action_type\'[^\]]*\]),\s*(\[\'action_type\'[^\]]*\]),\s*(\[\'action_type\'[^\]]*\]),/',
    '$1,' . "\n            " . '$2,' . "\n            " . '$3,' . "\n            " . '$4,' . "\n            " . '$5,' . "\n            " . '$6,' . "\n            " . '$7,',
    $content
);

file_put_contents('tests/Unit/Domains/Security/Services/SuspiciousActivityDetectorTest.php', $content);
echo "Fixed array formatting\n";
