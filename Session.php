<?php

namespace app\core;

class Session
{
    protected const FLASH_KEY = 'flash_message';
    public function __construct()
    {
        session_start();
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? []; // Here I cannot use & with $_SESSION because I am taking a subarray only
        foreach ($flashMessages as $key => &$flashMessage){ // Here I used & with $flashMessage so that I can change the value of 'remove' in the original array not to the copy made by default
            //Mark to be removed
            $flashMessage['remove'] = true;
        }
        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }

    public function setFlash($key, $message)
    {
        $_SESSION[self::FLASH_KEY][$key] = [
            'remove' => false,
            'value' => $message
        ];
    }
    public function getFlash($key)
    {
        return $_SESSION[self::FLASH_KEY][$key]['value'] ?? false;
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key)
    {
        return $_SESSION[$key] ?? false;
    }

    public function remove($key)
    {
        unset($_SESSION[$key]);
    }

    public function __destruct()
    {
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? []; // Here I cannot use & with $_SESSION because I am taking a subarray only
        foreach ($flashMessages as $key => &$flashMessage){ // Here I used & with $flashMessage so that I can change the value of 'remove' in the original array not to the copy made by default
            if($flashMessage['remove']){
                unset($flashMessages[$key]);
            }
        }
        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }
}