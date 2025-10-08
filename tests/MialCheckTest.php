<?php

declare(strict_types=1);

use Coffeemakr\Mial\MialCheck;
use PHPUnit\Framework\TestCase;

final class MialCheckTest extends TestCase {
    public function testInvalidTld() {
        $invalid_top_level_domains = [
            'coffeemakr',
            'comm',
            'vermögensberat',
            "CO​BM"
        ];

        foreach ($invalid_top_level_domains as $tld) {
            $email = 'admin@example.' . $tld;
            $check = new MialCheck($email);
            $this->assertFalse($check->isValid());
        }
    }

    public function testValidTld() {
        $valid_top_level_domains = [
            'ch' => 'ch',
            'com' => 'com',
            'Com' => 'com',
            'coM' => 'com',
            'COM' => 'com',
            'co.uk' => 'co.uk',
            'vermögensberatung' => 'xn--vermgensberatung-pwb',
            'VERMÖGENSBERATUNG' => 'xn--vermgensberatung-pwb',
            '電訊盈科' => 'xn--fzys8d69uvgm',
        ];

        foreach ($valid_top_level_domains as $tld => $punycode_tld) {
            $email = 'admin@tesT.Example.' . $tld;
            $check = new MialCheck($email);
            $this->assertNull($check->getError(), "valid tld $tld not accepted: ". $check->getError()?->getMessage());
            $this->assertTrue($check->isValid(), "valid tld $tld not accepted");

            $corrected_mail = 'admin@test.example.' . $punycode_tld;

            $this->assertEquals($corrected_mail, $check->getAddress());

        }
    }
}