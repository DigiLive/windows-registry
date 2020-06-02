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

use RecursiveIterator;
use VARIANT;

/**
 * Iterates over the subKeys of a registry key.
 */
class RegistryKeyIterator implements RecursiveIterator
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
     * @var int The number of subKeys we are iterating over.
     */
    protected $count = 0;

    /**
     * @var VARIANT A (hopefully) enumerable variant containing the names of subKeys.
     */
    protected $subKeyNames;

    /**
     * Create a new registry key iterator.
     *
     * @param RegistryHandle $handle The WMI registry provider handle to use.
     * @param RegistryKey    $key    The registry key whose subKeys to iterate over.
     */
    public function __construct(RegistryHandle $handle, RegistryKey $key)
    {
        $this->handle      = $handle;
        $this->registryKey = $key;
    }

    /**
     * Check for a key having subKeys.
     *
     * If a subKey iterator can be created for the current key the method returns true.
     *
     * @return bool True when a key has subKeys, False otherwise.
     */
    public function hasChildren(): bool
    {
        $iterator = $this->getChildren();
        $iterator->rewind();

        return $iterator->valid();
    }

    /**
     * Get an subKey iterator of the current registry key.
     *
     * @return RegistryKeyIterator SubKey Iterator.
     */
    public function getChildren(): RegistryKeyIterator
    {
        return new static($this->handle, $this->current());
    }

    /**
     * Get the registry key at the current iteration position.
     *
     * @return RegistryKey Registry key at current position.
     */
    public function current(): RegistryKey
    {
        return $this->registryKey->getSubKey($this->key());
    }

    /**
     * Get the name of the registry key at the current iteration position.
     *
     * @return string Name of the registry key at the current position.
     */
    public function key(): string
    {
        return (string)$this->subKeyNames[$this->pointer];
    }

    /**
     * Rewind the iterator to the first registry key.
     */
    public function rewind()
    {
        // Reset pointer and count.
        $this->pointer = 0;
        $this->count   = 0;

        // Create an empty variant to store subKey names.
        $this->subKeyNames = new VARIANT();

        // Enumerate subKeys.
        $errorCode = $this->handle->enumKey(
            $this->registryKey->getHive(false),
            $this->registryKey->getQualifiedName(),
            $this->subKeyNames
        );

        // Make sure the enum isn't empty.
        if ($errorCode === 0 && (variant_get_type($this->subKeyNames) & VT_ARRAY)) {
            // Store the amount of subKeys.
            /** @noinspection PhpParamsInspection */
            $this->count = count($this->subKeyNames); // VARIANT is countable.
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
     * Advances the iterator to the next registry key.
     */
    public function next()
    {
        $this->pointer++;
    }
}
