<?php
declare(strict_types=1);
/**
 * This file is a part of secure-php-login-system.
 *
 * @author Akbar Hashmi (Owner/Developer)            <me@akbarhashmi.com>.
 * @author Nicholas English (Collaborator/Developer) <nenglish0820@outlook.com>.
 *
 * @link    <https://github.com/akbarhashmi/Secure-PHP-Login-System> Github repository.
 * @license <https://github.com/akbarhashmi/Secure-PHP-Login-System/blob/master/LICENSE> MIT license.
 */
 
namespace Akbarhashmi\Engine;

use ParagonIE\Halite\Cookie as ParagonieCookie;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\Halite\HiddenString;

/**
 * Cookie.
 *
 * @codeCoverageIgnore
 */
class Cookie implements CookieInterface
{
    
    /**
     * @var array|[] $config The configuration array.
     */
    private $config = [];
    
    /**
     * @var object $cookieController The cookie controller.
     */
    private $cookieController;
    
    /**
     * Set the cookie encryption key.
     *
     * @param array $config The config array to use.
     *
     * @return void.
     */
    function __construct(array $config)
    {
        // Set the encryption key.
        $this->cookieController = new ParagonieCookie(new EncryptionKey(new HiddenString($config['cookie']['secret_key'])));
        // Set the engine configuration.
        $this->config = $config;
    }
    
    /**
     * Set a new cookie.
     *
     * @param array  $options The list of options to run.
     * @param string $name    The name of the cookie.
     * @param mixed  $value   The cookie value.
     * @param int    $expire  The cookie expiration time.
     *
     * @throws RuntimeException If the headers are already sent.
     *
     * @return void.
     */
    public function set(array $options = ['use_encrypt' => \true], string $name, string $value, $expire): void
    {
        // Check to see if the headers were sent.
        if (\headers_sent())
        {
            throw new Exception\RuntimeException('The headers are already sent.');
        }
        // Should we encrypt the cookie.
        if (isset($options['use_encrypt']) && $options['use_encrypt'] === \true)
        {
            // Set the cookie using paragonie secure encryption with sodium.
            $this->cookieController->store(
                $name,
                $value,
                $expire,
                $this->config['cookie']['path'],
                $this->config['cookie']['domain'],
                $this->config['cookie']['secure'],
                $this->config['cookie']['http_only']
            );
        } else
        {
            // Set the cookie with no encryption.
            \setcookie(
                $name,
                $value,
                $expire,
                $this->config['cookie']['path'],
                $this->config['cookie']['domain'],
                $this->config['cookie']['secure'],
                $this->config['cookie']['http_only']
            );
        }
    }
    
    /**
     * Fetch a cookie by name.
     *
     * @param array  $options The list of options to run.
     * @param string $name    The name of the cookie.
     *
     * @throws RuntimeException If the headers are already sent.
     *
     * @return mixed The cookie value or if it does not exist
     *               Return a blank string.
     */
    public function fetch(array $options = ['use_decrypt' => \true], string $name): string
    {
        // Check to see if the headers were sent.
        if (\headers_sent())
        {
            throw new Exception\RuntimeException('The headers are already sent.');
        }
        // Check to see if the cookie exists.
        if (!isset($_COOKIE[$name]))
        {
            // Return a blank string.
            return '';
        }
        // Should we decrypt the cookie.
        if (isset($options['use_decrypt']) && $options['use_decrypt'] === \true)
        {
            // Decrypt and fetch the cookie.
            return $this->cookieController->fetch($name);
        }
        // Return the cookie value without decryption.
        return $_COOKIE[$name];
    }
    
    /**
     * Delete a cookie by name.
     *
     * @param string $name The name of the cookie.
     *
     * @throws RuntimeException If the headers are already sent.
     *
     * @return void.
     */
    public function delete(string $name): void
    {
        // Check to see if the headers were sent.
        if (\headers_sent())
        {
            throw new Exception\RuntimeException('The headers are already sent.');
        }
        // Check to see if the cookie is set.
        if (isset($_COOKIE[$name]))
        {
            // Remove the cookie from the cookie global var.
            unset($_COOKIE[$name]);
            // Actually delete the cookie.
            \setcookie(
                $name,
                '',
                \time() - 42000,
                $this->config['cookie']['path'],
                $this->config['cookie']['domain'],
                $this->config['cookie']['secure'],
                $this->config['cookie']['http_only']
            );
        }
    }
    
}
