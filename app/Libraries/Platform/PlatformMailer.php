<?php
namespace App\Libraries\Platform;

use Throwable;

class PlatformMailer
{
    public function __construct(private Store $s) {}
    private function value(string $key, string $emailKey, string $default=''): string
    { return $this->s->setting('platform_smtp_'.$key, '') ?: (string)env('email.'.$emailKey, $default); }
    public function ready(): bool
    { return $this->value('host','SMTPHost') !== '' && filter_var($this->value('from','fromEmail'), FILTER_VALIDATE_EMAIL); }
    public function work(): int
    {
        if (!$this->ready()) { return 0; }
        // Unknown delivery outcomes are kept for admin review instead of repeatedly emailing a member.
        $this->s->exec("UPDATE platform_emails SET status='review',error='การส่งขาดการเชื่อมต่อ กรุณาตรวจสอบก่อนส่งซ้ำ' WHERE status='sending' AND next_attempt_at<?", [time()-300]);
        $sent=0;
        foreach ($this->s->all("SELECT * FROM platform_emails WHERE status='queued' AND next_attempt_at<=? ORDER BY id LIMIT 10", [time()]) as $row) {
            if (!$this->s->exec("UPDATE platform_emails SET status='sending',attempts=attempts+1,next_attempt_at=? WHERE id=? AND status='queued'",[time(),$row['id']])) { continue; }
            try {
                $mailer=service('email', null, false);
                $mailer->initialize(['protocol'=>'smtp','SMTPHost'=>$this->value('host','SMTPHost'),
                    'SMTPUser'=>$this->value('user','SMTPUser'),'SMTPPass'=>$this->value('pass','SMTPPass'),
                    'SMTPPort'=>(int)$this->value('port','SMTPPort','587'),'SMTPCrypto'=>$this->value('crypto','SMTPCrypto','tls'),
                    'SMTPTimeout'=>15,'charset'=>'UTF-8','mailType'=>'text','CRLF'=>"\r\n",'newline'=>"\r\n"]);
                $mailer->setFrom($this->value('from','fromEmail'), 'SKYNEXT INTER');
                $mailer->setTo($row['recipient']);$mailer->setSubject($row['subject']);$mailer->setMessage($row['body']);
                if (!$mailer->send(false)) { throw new \RuntimeException('ส่งอีเมลไม่สำเร็จ ตรวจสอบการตั้งค่า SMTP'); }
                $this->s->exec("UPDATE platform_emails SET status='sent',sent_at=?,error='' WHERE id=?",[Store::now(),$row['id']]);
                $sent++;
            } catch (Throwable $e) {
                $this->s->exec("UPDATE platform_emails SET status=?,next_attempt_at=?,error='ส่งอีเมลไม่สำเร็จ ตรวจสอบ SMTP' WHERE id=?",[(int)$row['attempts']>=4?'review':'queued',time()+900,$row['id']]);
            }
        }
        return $sent;
    }
}
