<?php

namespace Talger\Kohana\Autoloader\Tests;

use Talger\Kohana\Autoloader\Autoloader;

class AutoloaderTest extends \PHPUnit_Framework_TestCase
{

	public function testConstruct()
    {
        $includePaths = [
            'valami',
            'meg valami',
        ];
        $loader = new Autoloader($includePaths, 'TEST_');

        $helper = function () {
            return $this->includePaths;
        };

        $helper = $helper->bindTo($loader, $loader);

        $this->assertEquals($includePaths, $helper());

        $helper = function () {
            return $this->extensionPrefix;
        };

        $helper = $helper->bindTo($loader, $loader);

        $this->assertEquals('TEST_', $helper());
    }

	public function testRegister()
	{
        $loader = new Autoloader();
		$loader->register();

		$this->assertContains([$loader, 'loadClass'], spl_autoload_functions());

        spl_autoload_unregister([$loader, 'loadClass']);
	}

    protected function getIncludedFiles($exceptedFiles = [])
    {
        $includedFiles = [];
        foreach (get_included_files() as $file) {
            $file = strtolower(ltrim(substr($file, strlen(getcwd())), '/'));

            if (strpos($file, 'tests') !== 0) {
                continue;
            }

            $includedFiles[] = $file;
        }

        if (count($exceptedFiles)) {
            $includedFiles = array_diff($includedFiles, $exceptedFiles);
        }

        sort($includedFiles);

        return $includedFiles;
    }

    protected function getDeclaredClasses($exceptedClasses = [])
    {
        $declaredClasses = get_declared_classes();

        $declaredClasses = array_filter($declaredClasses, function ($value) {
            return strpos($value, '\\') === false && strpos($value, 'PHPUnit') === false;
        });

        if (count($exceptedClasses)) {
            $declaredClasses = array_diff($declaredClasses, $exceptedClasses);
        }

        sort($declaredClasses);

        return $declaredClasses;
    }

    /**
     * @dataProvider providerLoadClass
     */
    public function testLoadClass($class, $expectedResult, $expectedFiles = [], $expectedClasses = [])
    {
        sort($expectedFiles);
        sort($expectedClasses);

        array_walk($expectedFiles, function (&$value) {
            $value = strtolower($value);
        });

        $originalIncludedFiles = $this->getIncludedFiles();
        $originalLoadedClasses = $this->getDeclaredClasses();

        $loader = new Autoloader([
            'tests/Resources/system',
            'tests/Resources/main',
        ]);

        $this->assertEquals($expectedResult, $loader->loadClass($class));

        $includedFiles = $this->getIncludedFiles($originalIncludedFiles);
        $declaredClasses = $this->getDeclaredClasses($originalLoadedClasses);

        $this->assertEquals($expectedFiles, $includedFiles);
        $this->assertEquals($expectedClasses, $declaredClasses);
    }

    public function providerLoadClass()
    {
        return [
            [
                'Teszt_Controller',
                true,
                [
                    'tests/Resources/system/controllers/teszt.php'
                ],
                [
                    'Teszt_Controller'
                ]
            ],
            [
                'Teszt2_Controller',
                false
            ],
            [
                'Input',
                true,
                [
                    'tests/Resources/system/libraries/Input.php'
                ],
                [
                    'Input_Core',
                    'Input'
                ]
            ],
            [
                'AbstractLib',
                true,
                [
                    'tests/Resources/system/libraries/AbstractLib.php'
                ],
                [
                    'AbstractLib',
                    'AbstractLib_Core'
                ]
            ],
            [
                'Database',
                true,
                [
                    'tests/Resources/system/libraries/Database.php',
                    'tests/Resources/main/libraries/MY_Database.php'
                ],
                [
                    'Database_Core',
                    'Database'
                ]
            ],
            [
                'Teszt_Core',
                false
            ],
            [
                'Sql_Driver',
                true,
                [
                    'tests/Resources/system/libraries/drivers/Sql.php'
                ],
                [
                    'Sql_Driver_Core',
                    'Sql_Driver'
                ]
            ],
            [
                'User_Model',
                true,
                [
                    'tests/Resources/system/models/User.php'
                ],
                [
                    'User_Model'
                ]
            ],
        ];
    }

}
