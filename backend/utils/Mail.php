<?php
require_once __DIR__ . '/../config/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Mail {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // Enable debug output for troubleshooting
        $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
        $this->mailer->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug: $str");
        };

        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = SMTP_HOST;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = SMTP_USER;
        $this->mailer->Password = SMTP_PASS;
        $this->mailer->SMTPSecure = SMTP_SECURE;
        $this->mailer->Port = SMTP_PORT;

        // Common settings
        $this->mailer->setFrom(SMTP_USER, APP_NAME);
        $this->mailer->isHTML(true);
        $this->mailer->CharSet = 'UTF-8';
        
        // Clear all recipients and attachments
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
    }

    /**
     * Send verification email
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $token Verification token
     * @return bool Success status
     */
    public function sendVerificationEmail($email, $name, $token) {
        try {
            $verifyUrl = APP_URL . '/verify-email.html?token=' . $token;

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            $this->mailer->Subject = APP_NAME . ' - Verify your email address';
            
            $body = <<<HTML
            <h2>Welcome to SAI!</h2>
            <p>Hi {$name},</p>
            <p>Thank you for registering. Please click the button below to verify your email address:</p>
            <p style="text-align: center;">
                <a href="{$verifyUrl}" 
                   style="display: inline-block; padding: 10px 20px; background-color: #007bff; 
                          color: #ffffff; text-decoration: none; border-radius: 5px;">
                    Verify Email Address
                </a>
            </p>
            <p>Or copy and paste this URL into your browser:</p>
            <p>{$verifyUrl}</p>
            <p>This link will expire in 24 hours.</p>
            <p>If you did not create an account, no further action is required.</p>
            HTML;

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Mail error: " . $e->getMessage());
            error_log("Mail debug info: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send password reset email
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $token Reset token
     * @return bool Success status
     */
    public function sendPasswordResetEmail($email, $name, $token) {
        try {
            $resetUrl = APP_URL . '/reset-password.html?token=' . $token;

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            $this->mailer->Subject = APP_NAME . ' - Reset your password';
            
            $body = <<<HTML
            <h2>Password Reset Request</h2>
            <p>Hi {$name},</p>
            <p>You recently requested to reset your password. Click the button below to proceed:</p>
            <p style="text-align: center;">
                <a href="{$resetUrl}" 
                   style="display: inline-block; padding: 10px 20px; background-color: #007bff; 
                          color: #ffffff; text-decoration: none; border-radius: 5px;">
                    Reset Password
                </a>
            </p>
            <p>Or copy and paste this URL into your browser:</p>
            <p>{$resetUrl}</p>
            <p>This link will expire in 1 hour.</p>
            <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
            HTML;

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Mail error: " . $e->getMessage());
            error_log("Mail debug info: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send password change notification
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @return bool Success status
     */
    public function sendPasswordChangeNotification($email, $name) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            $this->mailer->Subject = APP_NAME . ' - Your password has been changed';
            
            $body = <<<HTML
            <h2>Password Changed</h2>
            <p>Hi {$name},</p>
            <p>Your password has been successfully changed.</p>
            <p>If you did not make this change, please contact support immediately.</p>
            HTML;

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Mail error: " . $e->getMessage());
            error_log("Mail debug info: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send device registration notification
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $deviceId Device ID
     * @param string $deviceName Device name
     * @return bool Success status
     */
    public function sendDeviceRegistrationNotification($email, $name, $deviceId, $deviceName) {
        try {
            $this->mailer->addAddress($email, $name);
            $this->mailer->Subject = 'New Device Registered';
            
            $body = <<<HTML
            <h2>New Device Registration</h2>
            <p>Hi {$name},</p>
            <p>A new device has been registered to your account:</p>
            <p><strong>Device Name:</strong> {$deviceName}<br>
            <strong>Device ID:</strong> {$deviceId}</p>
            <p>If you did not register this device, please contact support immediately.</p>
            HTML;

            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Failed to send device registration notification: " . $e->getMessage());
            return false;
        }
    }
} 