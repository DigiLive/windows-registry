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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Windows\Registry\Registry;
use Windows\Registry\RegistryHandle;
use Windows\Registry\RegistryKey;

/**
 * Class RegistryHandleTest
 *
 * The script will make a connection to the registry, but the registry handler is mocked so it can't operate on the
 * registry.
 *
 * @package Windows\Registry\Tests
 */
class RegistryHandleTest extends TestCase
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
     * Test if a key is received when calling a random, but valid RegistryKey method.
     *
     * @see RegistryKey
     */
    public function test__call()
    {
        $HKLM = $this->registry::connect()->getLocalMachine();
        $this->assertInstanceOf(RegistryKey::class, $HKLM->getSubKey('SOFTWARE\Microsoft\Windows\CurrentVersion'));
    }
}
