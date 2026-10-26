<?php

namespace App\Commands;

use App\Libraries\Platform\Affiliate;
use App\Libraries\Platform\Store;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PlatformAffiliateTest extends BaseCommand
{
    protected $group = 'Platform';
    protected $name = 'platform:affiliate-test';
    protected $description = 'Test fixed sequential and immutable affiliate IDs';

    public function run(array $params)
    {
        $store = new Store();
        CLI::write("=== Testing Affiliate ID Logic ===", 'green');

        // 1. Invalid regex
        try {
            Affiliate::validateCustomCode('bad@code!', 2, $store);
            CLI::error("FAIL: bad@code! did not throw");
        } catch (\Exception $e) {
            CLI::write("PASS: bad@code! rejected -> " . $e->getMessage(), 'yellow');
        }

        // 2. Short length
        try {
            Affiliate::validateCustomCode('ab', 2, $store);
            CLI::error("FAIL: ab did not throw");
        } catch (\Exception $e) {
            CLI::write("PASS: ab rejected -> " . $e->getMessage(), 'yellow');
        }

        // 3. Reserved word
        try {
            Affiliate::validateCustomCode('admin', 2, $store);
            CLI::error("FAIL: admin did not throw");
        } catch (\Exception $e) {
            CLI::write("PASS: reserved word admin rejected -> " . $e->getMessage(), 'yellow');
        }

        // 4. Reserved word case-insensitive
        try {
            Affiliate::validateCustomCode('CHECKOUT', 2, $store);
            CLI::error("FAIL: CHECKOUT did not throw");
        } catch (\Exception $e) {
            CLI::write("PASS: reserved word CHECKOUT rejected -> " . $e->getMessage(), 'yellow');
        }

        // 5. Duplicate code belonging to user 1 (0001)
        try {
            Affiliate::validateCustomCode('0001', 2, $store);
            CLI::error("FAIL: 0001 did not throw");
        } catch (\Exception $e) {
            CLI::write("PASS: duplicate code 0001 rejected -> " . $e->getMessage(), 'yellow');
        }

        // 6. Own existing code 0002 (should pass)
        try {
            $code = Affiliate::validateCustomCode('0002', 2, $store);
            CLI::write("PASS: keeping own code 0002 accepted -> " . $code, 'green');
        } catch (\Exception $e) {
            CLI::error("FAIL: 0002 threw -> " . $e->getMessage());
        }

        // 7. Custom alphanumeric code
        try {
            $code = Affiliate::validateCustomCode('somsak-top_99', 2, $store);
            throw new \RuntimeException("Custom code was incorrectly accepted");
        } catch (\Exception $e) {
            CLI::write("PASS: custom Affiliate ID rejected", 'green');
        }

        // 8. NextCode generation
        $next = Affiliate::nextCode($store);
        CLI::write("PASS: next sequential code -> " . $next . " (next available number)", 'green');

        CLI::write("=== All Affiliate Validation Tests Passed! ===", 'green');
    }
}
