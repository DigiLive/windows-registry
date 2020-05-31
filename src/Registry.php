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

namespace Windows\Registry;

use COM;

/**
 * Creates connections to a computer's registry and provides base keys for accessing subKeys.
 */
final class Registry
{
    public const HKEY_CLASSES_ROOT   = 0x80000000;
    public const HKEY_CURRENT_USER   = 0x80000001;
    public const HKEY_LOCAL_MACHINE  = 0x80000002;
    public const HKEY_USERS          = 0x80000003;
    public const HKEY_CURRENT_CONFIG = 0x80000005;
    /**
     * @var RegistryHandle The WMI registry provider handle to use.
     */
    protected $handle;

    /**
     * Creates a new registry connection object.
     *
     * @param RegistryHandle $handle The WMI registry provider handle to use.
     */
    public function __construct(RegistryHandle $handle)
    {
        $this->handle = $handle;
    }

    /**
     * Connects to a registry and returns a registry instance.
     *
     * @param string $host     The host name or IP address of the computer whose registry to connect
     *                         to. Defaults to the local computer.
     * @param string $username The user name to use to access the registry.
     * @param string $password The password to use to access the registry.
     *
     * @return Registry Instance of the connected registry.
     */
    public static function connect(string $host = '.', string $username = null, string $password = null): Registry
    {
        // create a WMI connection
        $swbemLocator = new COM('WbemScripting.SWbemLocator', null, CP_UTF8);
        /** @noinspection PhpUndefinedMethodInspection */
        $swbemService                                = $swbemLocator->ConnectServer(
            $host,
            'root\default',
            $username,
            $password
        );
        $swbemService->Security_->ImpersonationLevel = 3;

        // initialize registry provider
        $handle = new RegistryHandle($swbemService->Get('StdRegProv'));

        return new static($handle);
    }

    /**
     * Gets the underlying handle object used to access the registry.
     *
     * @return RegistryHandle The WMI registry provider handle to use.
     */
    public function getHandle(): RegistryHandle
    {
        return $this->handle;
    }

    /**
     * Gets the base registry key for the hive CLASSES_ROOT.
     *
     * @return RegistryKey Tree of hive.
     */
    public function getClassesRoot(): RegistryKey
    {
        return new RegistryKey($this->handle, self::HKEY_CLASSES_ROOT, '');
    }

    /**
     * Gets the base registry key for the hive CURRENT_CONFIG.
     *
     * @return RegistryKey Tree of hive.
     */
    public function getCurrentConfig(): RegistryKey
    {
        return new RegistryKey($this->handle, self::HKEY_CURRENT_CONFIG, '');
    }

    /**
     * Gets the base registry key for the hive CURRENT_USER.
     *
     * @return RegistryKey Tree of hive.
     */
    public function getCurrentUser(): RegistryKey
    {
        return new RegistryKey($this->handle, self::HKEY_CURRENT_USER, '');
    }

    /**
     * Gets the base registry key for the hive LOCAL_MACHINE.
     *
     * @return RegistryKey Tree of hive.
     */
    public function getLocalMachine(): RegistryKey
    {
        return new RegistryKey($this->handle, self::HKEY_LOCAL_MACHINE, '');
    }

    /**
     * Gets the base registry key for the hive USERS.
     *
     * @return RegistryKey Tree of hive.
     */
    public function getUsers(): RegistryKey
    {
        return new RegistryKey($this->handle, self::HKEY_USERS, '');
    }
}
