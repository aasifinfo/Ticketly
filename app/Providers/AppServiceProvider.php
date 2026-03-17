<?php

namespace App\Providers;

use App\Console\Commands\DispatchEventReminders;
use App\Console\Commands\ExpireReservations;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    public function boot(): void
    {
        if (Schema::hasTable('system_settings')) {
            $settings = SystemSetting::allCached();

            if (array_key_exists('service_fee_percentage', $settings)) {
                config(['ticketly.service_fee_percentage' => $settings['service_fee_percentage']]);
            }
            if (array_key_exists('portal_fee_percentage', $settings)) {
                config(['ticketly.portal_fee_percentage' => $settings['portal_fee_percentage']]);
            }
            if (array_key_exists('settlement_days', $settings)) {
                config(['ticketly.settlement_days' => $settings['settlement_days']]);
            }
            if (array_key_exists('currency', $settings)) {
                config(['ticketly.currency' => $settings['currency']]);
            }
            if (array_key_exists('currency_symbol', $settings)) {
                config(['ticketly.currency_symbol' => $settings['currency_symbol']]);
            }
            if (array_key_exists('support_email', $settings)) {
                config(['ticketly.support_email' => $settings['support_email']]);
            }
            if (array_key_exists('mail_from_address', $settings)) {
                config(['mail.from.address' => $settings['mail_from_address']]);
                config(['notifications.from_address' => $settings['mail_from_address']]);
            }
            if (array_key_exists('mail_from_name', $settings)) {
                config(['mail.from.name' => $settings['mail_from_name']]);
                config(['notifications.from_name' => $settings['mail_from_name']]);
            }
            if (array_key_exists('stripe_key', $settings)) {
                config(['services.stripe.key' => $settings['stripe_key']]);
            }
            if (array_key_exists('stripe_secret', $settings)) {
                config(['services.stripe.secret' => $settings['stripe_secret']]);
            }
            if (array_key_exists('stripe_webhook_secret', $settings)) {
                config(['services.stripe.webhook_secret' => $settings['stripe_webhook_secret']]);
            }
        }

        // Scheduled Jobs
        Schedule::command(ExpireReservations::class)
            ->everyMinute()
            ->withoutOverlapping()
            ->name('expire-reservations');

        // Reminder dispatcher - runs every 30 minutes
        // Reminder windows configured in config/notifications.php
        Schedule::command(DispatchEventReminders::class)
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->name('dispatch-event-reminders');
    }
}
