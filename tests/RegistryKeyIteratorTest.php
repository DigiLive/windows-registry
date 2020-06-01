<?php
/**
 * Copyright 2020 DigiLive <info@digilive.nl>
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

use PHPUnit\Framework\TestCase;
use Windows\Registry\Registry;
use Windows\Registry\RegistryHandle;
use Windows\Registry\RegistryKey;

/**
 * Class RegistryKeyIteratorTest
 *
 * The script will make a connection to the registry and read from it.
 *
 * @package Windows\Registry\Tests
 */
class RegistryKeyIteratorTest extends TestCase
{
    /**
     * @var RegistryHandle The WMI registry provider handle to use.
     */
    protected $handle;

    /**
     * Connect to the registry and get the handle which operates on the registry.
     */
    public function setUp(): void
    {
        $this->handle = Registry::connect()->getHandle();
    }

    /**
     * Test if the instantiated registry key has the same handle as the test class.
     */
    public function testGetHandle()
    {
        $key = $this->getSomeKey();

        $this->assertSame($this->handle, $key->getHandle());
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
     */
    protected function getSomeKey($hive = Registry::HKEY_CURRENT_USER, $name = ''): RegistryKey
    {
        return new RegistryKey($this->handle, $hive, $name);
    }

    /**
     * Test if the instantiated registry key has the same handle as the test class.
     */
    public function testGetHandleReturnsHandle()
    {
        $key = $this->getSomeKey();

        $this->assertSame($this->handle, $key->getHandle());
    }

    /**
     * Test the subKey Iterator.
     *
     * The key to apply the iterator on, must have subKeys, otherwise this test will fail.
     */
    public function testIterator()
    {
        $key      = $this->getSomeKey();
        $iterator = $key->getSubKeyIterator();

        $iterator->rewind();
        $this->assertTrue($iterator->hasChildren());
        $current = $iterator->current();
        $this->assertNotNull($current);
        $this->assertIsString($iterator->key());
        $iterator->next();
        $this->assertNotEquals($current, $iterator->current());
    }

    /**
     * Test the subKey Iterator of a subKey.
     *
     * The subKey to apply the iterator on, must have subKeys, otherwise this test will fail.
     */
    public function testGetChildren()
    {
        $key      = $this->getSomeKey();
        $iterator = $key->getSubKeyIterator();

        $iterator->rewind();
        $this->assertTrue($iterator->hasChildren());

        $childIterator = $iterator->getChildren();

        $childIterator->rewind();
        $current = $childIterator->current();
        $this->assertNotNull($current);
        $this->assertIsString($childIterator->key());
        $childIterator->next();
        $this->assertNotEquals($current, $childIterator->current());
    }
}
