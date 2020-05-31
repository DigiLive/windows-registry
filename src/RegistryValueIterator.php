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

use Iterator;
use VARIANT;

/**
 * Iterates over values in a registry key.
 */
class RegistryValueIterator implements Iterator
{
    /**
     * @var RegistryHandle The WMI registry provider handle to use.
     */
    protected $handle;

    /**
     * @var RegistryKey The registry key whose values are being iterated over.
     */
    protected $registryKey;

    /**
     * @var int The current iterator position.
     */
    protected $pointer = 0;

    /**
     * @var int The number of values we are iterating over.
     */
    protected $count = 0;

    /**
     * @var VARIANT A (hopefully) enumerable variant containing the value names.
     */
    protected $valueNames;

    /**
     * @var VARIANT A (hopefully) enumerable variant containing the data types of values.
     */
    protected $valueTypes;

    /**
     * Creates a new registry value iterator.
     *
     * @param RegistryHandle $handle The WMI registry provider handle to use.
     * @param RegistryKey    $key    The registry key whose values to iterate over.
     */
    public function __construct(RegistryHandle $handle, RegistryKey $key)
    {
        $this->handle      = $handle;
        $this->registryKey = $key;
    }

    /**
     * Rewind the iterator to the first value.
     */
    public function rewind()
    {
        // Reset pointer and count.
        $this->pointer = 0;
        $this->count   = 0;

        // Create empty variants to store values and their type.
        $this->valueNames = new VARIANT();
        $this->valueTypes = new VARIANT();

        // Attempt to enumerate values.
        $errorCode = $this->handle->enumValues(
            $this->registryKey->getHive(),
            $this->registryKey->getQualifiedName(),
            $this->valueNames,
            $this->valueTypes
        );

        // Make sure the enum isn't empty.
        if ($errorCode === 0
            && (variant_get_type($this->valueNames) & VT_ARRAY)
            && (variant_get_type($this->valueTypes) & VT_ARRAY)) {
            // Store the amount of values.
            /** @noinspection PhpParamsInspection */
            $this->count = count($this->valueNames); // VARIANT is countable.
        }
    }

    /**
     * Check if the current iteration position is within range.
     *
     * @return bool True if the position is within range, False otherwise.
     */
    public function valid(): bool
    {
        return $this->pointer < $this->count;
    }

    /**
     * Get the value at the current iteration position.
     *
     * @return mixed The value at the current iterator position.
     */
    public function current()
    {
        return $this->registryKey->getValue($this->key(), $this->currentType());
    }

    /**
     * Get the name of the registry value at the current iteration position.
     *
     * @return string Name of the value at the current iterator position.
     */
    public function key(): string
    {
        return (string)$this->valueNames[$this->pointer];
    }

    /**
     * Gets the value type of the registry value at the current iteration position.
     *
     * The following data value types are defined in WinNT.h:
     * REG_SZ (1)
     * REG_EXPAND_SZ (2)
     * REG_BINARY (3)
     * REG_DWORD (4)
     * REG_MULTI_SZ (7)
     * REG_QWORD (11)
     *
     * @return int Type of the registry value at the current iterator position.
     */
    public function currentType(): int
    {
        return (int)$this->valueTypes[$this->pointer];
    }

    /**
     * Advances the iterator to the next registry value.
     */
    public function next()
    {
        $this->pointer++;
    }
}
