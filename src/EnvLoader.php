<?php
namespace masoud4\Env; 
use Exception;

class EnvLoader
{
    /**
     * @var bool Indicates if the environment variables have been loaded.
     */
    private static bool $loaded = false;

    /**
     * Loads environment variables from a .envs file into $_ENV, $_SERVER, and putenv().
     *
     * @param string $filePath The full path to the .envs file.
     * @return bool True on success, false on failure (e.g., file not found).
     * @throws Exception If the file cannot be read.
     */
    public static function load(string $filePath): bool
    {
        if (self::$loaded) {
            return true; // Already loaded, prevent re-loading
        }

        if (!file_exists($filePath)) {
            error_log("masoud4\\Env\\EnvLoader: Environment file not found at " . $filePath);
            return false;
        }

        if (!is_readable($filePath)) {
            throw new Exception("masoud4\\Env\\EnvLoader: Environment file not readable at " . $filePath);
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new Exception("masoud4\\Env\\EnvLoader: Failed to read file " . $filePath);
        }

        foreach ($lines as $line) {
            // Trim whitespace from the line
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Find the first '=' sign to split key and value
            $separatorPos = strpos($line, '=');
            if ($separatorPos === false) {
                // Not a key=value pair, skip (or log a warning)
                error_log("masoud4\\Env\\EnvLoader: Skipping malformed line in .envs file: " . $line);
                continue;
            }

            $key = substr($line, 0, $separatorPos);
            $value = substr($line, $separatorPos + 1);

            // Trim key and value
            $key = trim($key);
            $value = trim($value);

            // Remove quotes from the value if present (single or double)
            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
                // Handle escaped double quotes inside double-quoted strings
                $value = str_replace('\"', '"', $value);
            } elseif (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                $value = substr($value, 1, -1);
                // Handle escaped single quotes inside single-quoted strings
                $value = str_replace('\'', "'", $value);
            }

            // Set the environment variable
            putenv("{$key}={$value}"); // Sets for current process
            $_ENV[$key] = $value;      // Populates $_ENV superglobal
            $_SERVER[$key] = $value;   // Populates $_SERVER superglobal (common for web servers)
        }

        self::$loaded = true;
        return true;
    }

    /**
     * Retrieves an environment variable.
     * Checks $_ENV, $_SERVER, and getenv() in that order.
     *
     * @param string $key The name of the environment variable.
     * @param mixed $default The default value to return if the variable is not found.
     * @return mixed The value of the environment variable or the default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Prioritize $_ENV for consistency, then $_SERVER, then getenv()
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        $envVar = getenv($key);
        if ($envVar !== false) {
            return $envVar;
        }
        return $default;
    }

    /**
     * Checks if environment variables have been loaded.
     * @return bool
     */
    public static function isLoaded(): bool
    {
        return self::$loaded;
    }
}
