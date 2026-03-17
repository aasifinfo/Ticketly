<?php

if (! function_exists('ticketly_currency')) {
    function ticketly_currency(): string
    {
        return (string) config('ticketly.currency', 'GBP');
    }
}

if (! function_exists('ticketly_currency_symbol')) {
    function ticketly_currency_symbol(): string
    {
        return (string) config('ticketly.currency_symbol', '£');
    }
}

if (! function_exists('ticketly_money')) {
    function ticketly_money(float|int|string|null $amount): string
    {
        return ticketly_currency_symbol() . number_format((float) $amount, 2);
    }
}

if (! function_exists('ticketly_money_code')) {
    function ticketly_money_code(float|int|string|null $amount): string
    {
        return ticketly_currency() . ' ' . number_format((float) $amount, 2);
    }
}

if (! function_exists('ticketly_setting')) {
    function ticketly_setting(string $key, mixed $default = null): mixed
    {
        if (class_exists(\App\Models\SystemSetting::class)
            && \Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
            return \App\Models\SystemSetting::getValue($key, $default);
        }
        return $default;
    }
}

if (! function_exists('ticketly_support_email')) {
    function ticketly_support_email(): string
    {
        return (string) config('ticketly.support_email', 'support@ticketly.com');
    }
}
