<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;

Artisan::command('test:email {email}', function ($email) {
    $this->info("Testing email to: {$email}");
    $this->info("Mail driver: " . config('mail.default'));
    $this->info("Mail host: " . config('mail.mailers.smtp.host'));
    $this->info("Mail port: " . config('mail.mailers.smtp.port'));
    
    try {
        Mail::raw('Test email from Laravel', function($message) use ($email) {
            $message->to($email)
                    ->subject('Test Email - Temporary Password System');
        });
        $this->info("✅ Email sent successfully! Check MailHog.");
    } catch (\Exception $e) {
        $this->error("❌ Failed to send email: " . $e->getMessage());
        Log::error('Email test failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    }
})->purpose('Test email sending to MailHog');

Artisan::command('reset:login-attempts {email}', function ($email) {
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        $this->error("❌ User not found: {$email}");
        return;
    }
    
    $oldAttempts = $user->failed_login_attempts;
    $user->failed_login_attempts = 0;
    $user->save();
    
    $this->info("✅ Reset failed login attempts for {$email}");
    $this->info("   Previous attempts: {$oldAttempts}");
    $this->info("   New attempts: 0");
})->purpose('Reset failed login attempts for a user (for testing)');
