<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

/**
 * @param   string $guard
 * @return  string|null
 */
function getModelForGuard(string $guard)
{
    return collect(config('auth.guards'))
        ->map(function ($guard) {
            return config("auth.providers.{$guard['provider']}.model");
        })->get($guard);
}