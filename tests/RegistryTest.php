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
use Windows\Registry\Registry;
use Windows\Registry\RegistryHandle;

/**
 * Class RegistryTest
 *
 * The script will make a connection to the registry, but the registry handler is mocked so it can't operate on the
 * registry.
 *
 * @package Windows\Registry\Tests
 */
class RegistryTest extends TestCase
{
    /**
     * @var MockObject The mocked registry handle.
     */
    protected $stubHandle;
    /**
     * @var Registry Connection to the registry.
     */
    protected $registry;

    /**
     * Mock the registry handle class to avoid operating on the registry itself.
     */
    public function setUp(): void
    {
        $this->stubHandle = $this->getMockBuilder(RegistryHandle::class)->disableOriginalConstructor()->getMock();
        /** @noinspection PhpParamsInspection */
        $this->registry = new Registry($this->stubHandle);
    }

    /**
     * Test if the instantiated registry key has the same handle as the test class.
     */
    public function testGetHandle()
    {
        $this->assertSame($this->stubHandle, $this->registry->getHandle());
    }

    /**
     * Connect to a registry using the default values.
     */
    public function testConnectDefaultReturnsInstance()
    {
        $this->assertInstanceOf(Registry::class, $this->registry::connect());
    }

    /**
     * Test getting registry trees.
     *
     * Also known as hives.
     */
    public function testGetTrees()
    {
        $this->assertSame(Registry::HKEY_CLASSES_ROOT, $this->registry::connect()->getClassesRoot()->getHive());
        $this->assertSame(Registry::HKEY_CURRENT_USER, $this->registry::connect()->getCurrentUser()->getHive());
        $this->assertSame(Registry::HKEY_CURRENT_CONFIG, $this->registry::connect()->getCurrentConfig()->getHive());
        $this->assertSame(Registry::HKEY_LOCAL_MACHINE, $this->registry::connect()->getLocalMachine()->getHive());
        $this->assertSame(Registry::HKEY_USERS, $this->registry::connect()->getUsers()->getHive());
    }
}
