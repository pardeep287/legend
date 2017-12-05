<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('alpha_spaces', function($attribute, $value)
        {
            return preg_match('/^[\pL\s-]+$/u', $value);
        });

        Validator::extend('greater_zero', function($attribute, $value)
        {
            return ($value > 0);
        });

        Validator::extend('alpha_hyphen', function($attribute, $value)
        {
            return preg_match('/^[a-zA-Z-]+$/', $value);
        });

        Validator::extend('numeric_hyphen', function($attribute, $value)
        {
            return preg_match('/^[0-9-]+$/', $value);
        });


        Validator::replacer('alpha_spaces', function($message, $attribute, $rule, $parameters) {
            return string_manip(str_replace('_', ' ', $attribute), 'UCW').' only accept alphabets.';
        });

        Validator::replacer('greater_zero', function($message, $attribute, $rule, $parameters) {
            return string_manip(str_replace('_', ' ', $attribute), 'UCW').' must be greater than zero.';
        });

        Validator::replacer('numeric_hyphen', function($message, $attribute, $rule, $parameters) {
            return string_manip(str_replace('_', ' ', $attribute), 'UCW').' only accept numeric.';
        });

        Validator::replacer('alpha_hyphen', function($message, $attribute, $rule, $parameters) {
            return string_manip(str_replace('_', ' ', $attribute), 'UCW').' only accept alphabets.';
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
