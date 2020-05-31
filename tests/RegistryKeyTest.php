<?php
/**
 * Copyright 2014 Stephen Coakley <me@stephencoakley.com>
 * Copyright 2020 DigiLive <info@digilive.nl>
 *
 * This file has been modified by DigiLive.
 * Changes can be tracked on our GitHub website at
 *
 *     https://github.com/DigiLive/windows-registry
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Windows\Registry\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Windows\Registry\InvalidTypeException;
use Windows\Registry\KeyNotFoundException;
use Windows\Registry\OperationFailedException;
use Windows\Registry\Registry;
use Windows\Registry\RegistryHandle;
use Windows\Registry\RegistryKey;
use Windows\Registry\RegistryKeyIterator;
use Windows\Registry\ValueNotFoundException;

/**
 * Class RegistryKeyTest
 *
 * The script will make a connection to the registry, but the registry handler is mocked so it can't operate on the
 * registry.
 *
 * @package Windows\Registry\Tests
 */
class RegistryKeyTest extends TestCase
{
    /**
     * @var int Return value of registry handle at the tests that follow the one which sets this property.
     *          Be aware of changing the order of the tests!
     */
    public static $FollowingTestStdRegProvResult = 0;
    /**
     * @var MockObject The mocked registry handle.
     */
    protected $stubHandle;

    /**
     * Mock the registry handle class to avoid operating on the registry itself.
     *
     * The returnValue of method __call of the registry handle will be mocked.
     * The returnValue is the same as RegistryKeyTest::FollowingTestStdRegProvResult at the moment setUp is called.
     *
     * Mocked method __call of the registry handle only allows the methods listed in the logicalOr method to be
     * called. Calling any other method will raise an exception.
     */
    public function setUp(): void
    {
        $returnValue      = self::$FollowingTestStdRegProvResult;
        $this->stubHandle = $this->getMockBuilder(RegistryHandle::class)->disableOriginalConstructor()->getMock();
        $this->stubHandle->method('__call')->with(
            $this->logicalOr(
                $this->equalTo('enumKey'),
                $this->equalTo('enumValues'),
                $this->equalTo('createKey'),
                $this->equalTo('deleteKey'),
                $this->equalTo('getStringValue'),
                $this->equalTo('getExpandedStringValue'),
                $this->equalTo('getBinaryValue'),
                $this->equalTo('getDWORDValue'),
                $this->equalTo('getQWORDValue'),
                $this->equalTo('getMultiStringValue'),
                $this->equalTo('setStringValue'),
                $this->equalTo('setExpandedStringValue'),
                $this->equalTo('setBinaryValue'),
                $this->equalTo('setDWORDValue'),
                $this->equalTo('setMultiStringValue'),
                $this->equalTo('deleteValue')
            )
        )->willReturnCallback(
            function () use ($returnValue) {
                return $returnValue;
            }
        );
    }

    /**
     * Test if the instantiated registry key has the same handle as the test class.
     */
    public function testGetHandle()
    {
        $key = $this->getSomeKey();

        $this->assertSame($this->stubHandle, $key->getHandle());
    }

    /**
     * Get a registry key from a hive.
     *
     * When an empty string value is passed to parameter $name, the key returned is the root of the hive.
     *
     * @param int    $hive Hive to get key from.
     * @param string $name Name of the key to get.
     *
     * @return RegistryKey Key which belongs to hive.
     * @noinspection PhpParamsInspection
     */
    protected function getSomeKey($hive = Registry::HKEY_LOCAL_MACHINE, $name = 'Software'): RegistryKey
    {
        return new RegistryKey($this->stubHandle, $hive, $name);
    }

    /**
     * Test getting the hive of a key.
     */
    public function testGetHive()
    {
        $key = $this->getSomeKey(Registry::HKEY_LOCAL_MACHINE);

        $this->assertSame(Registry::HKEY_LOCAL_MACHINE, $key->getHive());
    }

    /**
     * Test getting the name of a key.
     *
     * A key might be a subKey and therefor getting the name is tested twice.
     * Once for a rootKey and once for a subKey.
     */
    public function testGetName()
    {
        $key    = $this->getSomeKey();
        $subKey = $this->getSomeKey(Registry::HKEY_LOCAL_MACHINE, 'Software\Microsoft');

        $this->assertSame('Software', $key->getName());
        $this->assertSame('Microsoft', $subKey->getName());
    }

    /**
     * Test getting a subKey.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     */
    public function testGetSubKey()
    {
        self::$FollowingTestStdRegProvResult = 1;
        $key                                 = $this->getSomeKey();

        $this->assertInstanceOf(RegistryKey::class, $key->getSubKey('Software'));
    }

    /**
     * Test getting a non existing subKey.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     */
    public function testGetSubKeyThrowsException()
    {
        self::$FollowingTestStdRegProvResult = 0;
        $hive                                = $this->getSomeKey();

        $this->expectException(KeyNotFoundException::class);
        $hive->getSubKey('nonExistingKey');
    }

    /**
     * Test creating a subKey.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     */
    public function testCreateSubKey()
    {
        self::$FollowingTestStdRegProvResult = 1;
        $key                                 = $this->getSomeKey();

        $this->assertInstanceOf(RegistryKey::class, $key->createSubKey('newKey'));
    }

    /**
     * Test failure of creating a subKey when the registry handle returns a failure value.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     */
    public function testCreateSubKeyThrowsException()
    {
        self::$FollowingTestStdRegProvResult = 0;
        $key                                 = $this->getSomeKey();

        $this->expectException(OperationFailedException::class);
        $key->createSubKey('newKey');
    }

    /**
     * Test deleting a subKey.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     *
     * @noinspection PhpVoidFunctionResultUsedInspection
     */
    public function testDeleteSubKey()
    {
        self::$FollowingTestStdRegProvResult = 1;

        $key = $this->getSomeKey();
        $this->assertNull($key->deleteSubKey('someKey'));
    }

    /**
     * Test failure of creating a subKey when the registry handle returns a failure value.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     */
    public function testDeleteSubKeyThrowsException()
    {
        self::$FollowingTestStdRegProvResult = 0;
        $key                                 = $this->getSomeKey();
        $this->expectException(OperationFailedException::class);
        $key->deleteSubKey('someKey');
    }

    /**
     * Test deleting a subKey including contents.
     *
     * Note: Since the registry handle is mocked, the iterators of the subKey contain no elements. Therefor the content
     *       of the outer foreach loop of RegistryKey::deleteSubKeyRecursive isn't covered.
     * @noinspection PhpVoidFunctionResultUsedInspection
     */
    public function testDeleteSubKeyRecursive()
    {
        $key = $this->getSomeKey();
        $this->assertNull($key->deleteSubKeyRecursive('someKey'));
    }

    /**
     * Test getting a subKey iterator.
     */
    public function testGetSubKeyIterator()
    {
        $key = $this->getSomeKey();
        $this->assertInstanceOf(RegistryKeyIterator::class, $key->getSubKeyIterator());
    }

    /**
     * Test getting the type of a value.
     *
     * Note: This test doesn't use the mocked registry handle, but instantiate a real one!
     *       It will actually read from the registry.
     */
    public function testGetValueType()
    {
        $key = Registry::connect()->getLocalMachine();
        $key = $key->getSubKey('Software\Microsoft\Windows\CurrentVersion');
        $this->assertSame(RegistryKey::TYPE_SZ, $key->getValueType('ProgramFilesDir'));
    }

    /**
     * Test getting values of a key.
     *
     * Getting values of all types which are currently supported by the registry handle is tested.
     * This test doesn't cover 100% of the getValue method because we can't get variant types when the handle is mocked.
     */
    public function testGetValue()
    {
        $key = $this->getSomeKey();

        $this->assertSame('', $key->getValue('someName', RegistryKey::TYPE_SZ));
        $this->assertSame('', $key->getValue('someName', RegistryKey::TYPE_EXPAND_SZ));
        $this->assertSame('', $key->getValue('someName', RegistryKey::TYPE_BINARY));
        $this->assertSame(0, $key->getValue('someName', RegistryKey::TYPE_DWORD));
        $this->assertSame('', $key->getValue('someName', RegistryKey::TYPE_QWORD));
        $this->assertSame([], $key->getValue('someName', RegistryKey::TYPE_MULTI_SZ));
    }

    /**
     * Test failure of getting a value when the value doesn't exist.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     */
    public function testGetValueThrowsException1()
    {
        self::$FollowingTestStdRegProvResult = 1;
        $key                                 = $this->getSomeKey();

        $this->expectException(ValueNotFoundException::class);
        $this->assertSame('', $key->getValue('nonExistingValue'));
    }

    /**
     * Test failure of getting a value when the registry handle returns a failure value.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     */
    public function testGetValueThrowsException2()
    {
        self::$FollowingTestStdRegProvResult = 0;
        $key                                 = $this->getSomeKey();

        $this->expectException(OperationFailedException::class);
        $this->assertSame('', $key->getValue('someName', RegistryKey::TYPE_SZ));
    }

    /**
     * Test setting a value.
     *
     * Getting values of all types which are currently supported by the registry handle is tested.
     *
     * @noinspection PhpVoidFunctionResultUsedInspection
     */
    public function testSetValue()
    {
        $key = $this->getSomeKey();

        $this->assertNull($key->setValue('someKey', 'someValue', RegistryKey::TYPE_SZ));
        $this->assertNull($key->setValue('someKey', 'someValue', RegistryKey::TYPE_EXPAND_SZ));
        $this->assertNull($key->setValue('someKey', 'someValue', RegistryKey::TYPE_BINARY));
        $this->assertNull($key->setValue('someKey', 'someValue', RegistryKey::TYPE_DWORD));
        $this->assertNull($key->setValue('someKey', [], RegistryKey::TYPE_MULTI_SZ));
    }

    /**
     * Test failure of setting a multiString value which isn't an array.
     *
     * @noinspection PhpVoidFunctionResultUsedInspection
     */
    public function testSetValueTrowsException1()
    {
        $this->expectException(InvalidTypeException::class);
        $key = $this->getSomeKey();

        $this->assertNull($key->setValue('someKey', 'nonArray', RegistryKey::TYPE_MULTI_SZ));
        $this->assertNull($key->setValue('someKey', 'someValue', 'type'));
    }

    /**
     * Test failure of setting a value which has an non existing type.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     *
     * @noinspection PhpVoidFunctionResultUsedInspection
     */
    public function testSetValueTrowsException2()
    {
        self::$FollowingTestStdRegProvResult = 1;
        $this->expectException(InvalidTypeException::class);
        $key = $this->getSomeKey();

        $this->assertNull($key->setValue('someKey', 'someValue', 0));
    }

    /**
     * Test failure of setting a value when the registry handle returns a failure value.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     *
     * @noinspection PhpVoidFunctionResultUsedInspection
     */
    public function testSetValueTrowsException3()
    {
        self::$FollowingTestStdRegProvResult = 0;
        $this->expectException(OperationFailedException::class);
        $key = $this->getSomeKey();

        $this->assertNull($key->setValue('someKey', 'value', RegistryKey::TYPE_SZ));
    }

    /**
     * Test deleting a value.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     */
    public function testDeleteValue()
    {
        self::$FollowingTestStdRegProvResult = 1;
        $key                                 = $this->getSomeKey();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull($key->deleteValue('someKey'));
    }

    /**
     * Test failure of deleting a value when the registry handle returns a failure value.
     *
     * The tested method will check if the value exists after the operation failed.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     *
     * @noinspection PhpVoidFunctionResultUsedInspection
     */
    public function testDeleteValueTrowsException1()
    {
        self::$FollowingTestStdRegProvResult = 2;
        $this->expectException(ValueNotFoundException::class);
        $key = $this->getSomeKey();

        $this->assertNull($key->deleteValue('someKey'));
    }

    /**
     * Test failure of deleting a value when the registry handle returns a failure value.
     *
     * The tested method will NOT check if the value exists after the operation failed.
     *
     * Note: This test changes the returnValue of mocked method __call of the registry handle for the following
     *       tests!
     *
     * @noinspection PhpVoidFunctionResultUsedInspection
     */
    public function testDeleteValueTrowsException2()
    {
        self::$FollowingTestStdRegProvResult = 0;
        $this->expectException(OperationFailedException::class);
        $key = $this->getSomeKey();

        $this->assertNull($key->deleteValue('someKey'));
    }

    /**
     * Test getting the qualified name of a key.
     */
    public function testGetQualifiedName()
    {
        $key = $this->getSomeKey(Registry::HKEY_LOCAL_MACHINE, 'Software\Microsoft');
        $this->assertSame('Software\Microsoft', $key->getQualifiedName());
    }

    /**
     * Test getting the parentKey of a key.
     *
     * To make sure the returned key is the parentKey, the qualified name of the parentKey is validated.
     */
    public function testGetParentKey()
    {
        $key = $this->getSomeKey(Registry::HKEY_LOCAL_MACHINE, 'Software\Microsoft');
        $this->assertSame('Software', $key->getParentKey()->getQualifiedName());
    }

    /**
     * Test getting a hive when it's a parent of a key.
     *
     * To make sure the returned hive is the parentKey, the qualified name of the parentKey is validated.
     */
    public function testGetParentKeyGetsHiveKey()
    {
        $key = $this->getSomeKey();
        $this->assertSame('', $key->getParentKey()->getQualifiedName());
    }
}
