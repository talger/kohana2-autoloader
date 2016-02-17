<?php
/**
 *
 */
namespace Talger\Kohana\Autoloader;

/**
 *
 */
class Autoloader
{
    /**
     * [$extensionPrefix description].
     *
     * @var null
     */
    protected $extensionPrefix = null;

    /**
     * [$includePaths description].
     *
     * @var null
     */
    protected $includePaths = null;

    /**
     * [__construct description].
     *
     * @param array  $includePaths    [description]
     * @param string $extensionPrefix [description]
     */
    public function __construct(array $includePaths = [], $extensionPrefix = 'MY_')
    {
        $this->includePaths = $includePaths;
        $this->extensionPrefix = $extensionPrefix;
    }

    /**
     * [register description].
     *
     * @return void
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * [loadClass description].
     *
     * @param string $class
     *
     * @return bool
     */
    public function loadClass($class)
    {
        $suffix = '';
        if (strrpos($class, '_') > 0) {
            $suffix = substr($class, strrpos($class, '_') + 1);
        }

        switch ($suffix) {
        case 'Core':
            $type = 'libraries';
            $file = substr($class, 0, -5);
            break;

        case 'Controller':
            $type = 'controllers';
            $file = strtolower(substr($class, 0, -11));
            break;

        case 'Model':
            $type = 'models';
            $file = strtolower(substr($class, 0, -6));
            break;

        case 'Driver':
            $type = 'libraries/drivers';
            $file = str_replace('_', '/', substr($class, 0, -7));
            break;

        default:
            $type = ($class[0] < 'a') ? 'libraries' : 'helpers';
            $file = $class;
            break;
        }

        $realpath = $this->findFile($type.'/'.$file.'.php');
        if (!$realpath) {
            return false;
        }

        include_once $realpath;

        if ($filename = $this->findFile($type.'/'.$this->extensionPrefix.$class.'.php')) {
            include_once $filename;
        } elseif ($suffix !== 'Core' && class_exists($class.'_Core', false)) {
            $extension = 'class '.$class.' extends '.$class.'_Core { }';

            $core = new \ReflectionClass($class.'_Core');

            if ($core->isAbstract()) {
                $extension = 'abstract '.$extension;
            }

            eval($extension);
        }

        return true;
    }

    /**
     * [findFile description].
     *
     * @param [type] $filepath [description]
     *
     * @return [type] [description]
     */
    protected function findFile($filepath)
    {
        foreach ($this->includePaths as $path) {
            $realpath = rtrim($path, '/').'/'.$filepath;

            if (file_exists($realpath)) {
                return $realpath;
            }
        }

        return false;
    }
}
