<?php

namespace Energy;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail
{

    public static function send($params = array())
    {

        $mail = new PHPMailer(true);

        try {

            $defParams = array(
                'content' => '',
                'title' => '',
                'email' => '',
                'document' => 'email/document',
                'props' => array()
            );

            $params = array_merge($defParams, $params);

            if (Hooks::is('Mail::send.pre'))
                Hooks::apply('Mail::send.pre', $params);

            $mail->CharSet = (Kernel::config('config', 'mail_charset') == '') ? 'UTF-8' : Kernel::config('config', 'mail_charset');

            if (isset($params['content'])) {

                if (Hooks::is('Mail::send.content.pre'))
                    Hooks::apply('Mail::send.content.pre', $params);

                $content = '';

                if (is_array($params['content'])) {
                    if ($params['content']) {
                        foreach ($params['content'] as $file) {
                            $content .= View::load($file, $params['props']);
                        }

                        if ($content) {

                            $content = View::load($params['document'], [
                                'title' => $params['title'],
                                'content' => $content
                            ]);
                        }
                    }
                } else
                    $content = $params['content'];

                if (Hooks::is('Mail::send.content.post'))
                    Hooks::apply('Mail::send.content.post', $params);
            }

            if ($content) {
                if (Kernel::config('config', 'mail_method') == 1) {
                    $mail->isSMTP();
                    $mail->SMTPDebug = 0;
                    $mail->Host = Kernel::config('config', 'smtp_host');
                    $mail->Port = Kernel::config('config', 'smtp_port');
                    $mail->SMTPAuth = true;
                    $mail->Username = Kernel::config('config', 'smtp_username');
                    $mail->Password = Kernel::config('config', 'smtp_password');

                    if (Kernel::config('config', 'smtp_secure_type') == 1)
                        $mail->SMTPSecure = 'ssl';
                    elseif (Kernel::config('config', 'smtp_secure_type') == 2)
                        $mail->SMTPSecure = 'tls';
                    else {
                        $mail->SMTPSecure = false;
                        $mail->SMTPAutoTLS = false;
                    }
                    $mail->setFrom(Kernel::config('config', 'smtp_username'), View::getSiteName());
                } else
                    $mail->setFrom(Kernel::config('config', 'email_from'), View::getSiteName());

                $mail->addAddress($params['email'], $params['email']);
                $mail->isHTML(true);
                $mail->Subject = $params['title'];
                $mail->Body = $content;
                $mail->AltBody = '';

                if (Hooks::is('Mail::send.post'))
                    Hooks::apply('Mail::send.post', $params);

                return $mail->Send();
            } else
                return false;
        } catch (Exception $e) {
            if (env('APP_DEBUG'))
                print_r("Message could not be sent. Mailer Error: {$mail->ErrorInfo} " . PHP_EOL . $e);
        }
    }
}
