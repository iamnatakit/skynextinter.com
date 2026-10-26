<?php
namespace App\Libraries\Platform;
use RuntimeException;

/** EMV PromptPay payload; QR drawing happens locally, never through a third-party image URL. */
class PromptPay
{
    private static function tlv(string $id, string $value): string { return $id . str_pad((string)strlen($value), 2, '0', STR_PAD_LEFT) . $value; }
    public static function payload(string $target, int $satang): string
    {
        if (!preg_match('/^(0\d{9}|\d{13})$/D', $target) || $satang <= 0) { throw new RuntimeException('ระบุพร้อมเพย์เป็นเบอร์โทร 10 หลักหรือเลขประจำตัว 13 หลัก'); }
        $account = strlen($target) === 10 ? self::tlv('01', '0066'.substr($target, 1)) : self::tlv('02', $target);
        $payload = self::tlv('00','01').self::tlv('01','12').self::tlv('29', self::tlv('00','A000000677010111').$account)
            .self::tlv('58','TH').self::tlv('53','764').self::tlv('54', number_format($satang / 100, 2, '.', '')).'6304';
        $crc = 0xffff;
        foreach (str_split($payload) as $char) {
            $crc ^= ord($char) << 8;
            for ($bit=0; $bit<8; $bit++) { $crc = (($crc & 0x8000) ? ($crc << 1) ^ 0x1021 : $crc << 1) & 0xffff; }
        }
        return $payload.strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
