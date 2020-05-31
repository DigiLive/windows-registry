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

use VARIANT;

/**
 * Represents a single key in the Windows registry.
 */
final class RegistryKey
{
    public const TYPE_SZ        = 1;
    public const TYPE_EXPAND_SZ = 2;
    public const TYPE_BINARY    = 3;
    public const TYPE_DWORD     = 4;
    public const TYPE_MULTI_SZ  = 7;
    public const TYPE_QWORD     = 11;

    /**
     * @var RegistryHandle The WMI registry provider handle to use.
     */
    protected $handle;

    /**
     * @var int The registry hive the key is located in.
     *          Note: Although a hive is represented as a hex number, which is an int, php will cast this value into a float or
     *                double because the value causes an overflow.
     */
    protected $hive;

    /**
     * @var string Fully-qualified name of the key.
     */
    protected $name;

    /**
     * Creates a new key value object.
     *
     * Note: Although a hive is represented as a hex number, which is an int, php will cast this value into a float or
     * double because the value causes an overflow.
     *
     * @param RegistryHandle $handle The WMI registry provider handle to use.
     * @param float          $hive   The registry hive the key is located in.
     * @param string         $name   The fully-qualified name of the key.
     */
    public function __construct(RegistryHandle $handle, float $hive, string $name)
    {
        $this->handle = $handle;
        $this->hive   = $hive;
        $this->name   = $name;
    }

    /**
     * Gets the local (unqualified) name of the key.
     *
     * @return string Name of the key.
     */
    public function getName(): string
    {
        if (strpos($this->name, '\\') !== false) {
            return substr($this->name, strpos($this->name, '\\') + 1);
        }

        return $this->name;
    }

    /**
     * Gets the fully-qualified name of the key.
     *
     * @return string Name of the key.
     */
    public function getQualifiedName(): string
    {
        return $this->name;
    }

    /**
     * Gets the registry hive the key is located in.
     *
     * Note: Although a hive is represented as a hex number, which is an int, php will cast this value into a float or
     * double because the value causes an overflow.
     *
     * @return float Hive of the key.
     */
    public function getHive(): float
    {
        return $this->hive;
    }

    /**
     * Gets the underlying handle object used to access the registry.
     *
     * @return RegistryHandle The WMI registry provider handle to use..
     */
    public function getHandle(): RegistryHandle
    {
        return $this->handle;
    }

    /**
     * Gets a registry subKey with the specified name.
     *
     * @param string $name The name or path of the subKey.
     *
     * @return RegistryKey The requested key.
     * @throws KeyNotFoundException When the key doesn't exist.
     */
    public function getSubKey(string $name): RegistryKey
    {
        $subKeyName = empty($this->name) ? $name : $this->name . '\\' . $name;

        // Call EnumKeys on the subKey to check if it exists.
        $sNames = new VARIANT();
        if ($this->handle->enumKey($this->hive, $subKeyName, $sNames) === 0) {
            return new static($this->handle, $this->hive, $subKeyName);
        }

        throw new KeyNotFoundException("The key \"{$subKeyName}\" does not exist.");
    }

    /**
     * Gets the parent registry key of the current subKey, or null if the key is a root key.
     *
     * @return RegistryKey|null Parent of the key or null.
     */
    public function getParentKey()
    {
        $parentKeyName = dirname($this->name);
        if ($parentKeyName !== '.') {
            return new static($this->handle, $this->hive, $parentKeyName);
        }

        // The parent key is a hive.
        return new static($this->handle, $this->hive, '');
    }

    /**
     * Creates a new registry subKey.
     *
     * @param string $name The name or path of the key relative to the current key.
     *
     * @return RegistryKey The new key.
     * @throws OperationFailedException When creating the key failed.
     */
    public function createSubKey(string $name): RegistryKey
    {
        $subKeyName = empty($this->name) ? $name : $this->name . '\\' . $name;

        if ($this->handle->createKey($this->hive, $subKeyName) !== 0) {
            throw new OperationFailedException("Failed to create key \"{$subKeyName}\".");
        }

        return new static($this->handle, $this->hive, $name);
    }

    /**
     * Deletes a registry subKey.
     *
     * @param string $name The name or path of the subKey to delete.
     *
     * @throws OperationFailedException When deleting the subKey failed.
     */
    public function deleteSubKey(string $name)
    {
        $subKeyName = empty($this->name) ? $name : $this->name . '\\' . $name;

        if ($this->handle->deleteKey($this->hive, $subKeyName) !== 0) {
            throw new OperationFailedException("Failed to delete key '{$subKeyName}'.");
        }
    }

    /**
     * Gets an iterator for iterating over subKeys of this key.
     *
     * @return RegistryKeyIterator subKey Iterator.
     */
    public function getSubKeyIterator(): RegistryKeyIterator
    {
        return new RegistryKeyIterator($this->handle, $this);
    }

    /**
     * Gets the value data of a named key value.
     *
     * @param string $name The name of the value.
     * @param int    $type The value type of the value.
     *
     * @return mixed The value data of the value.
     */
    public function getValue(string $name, int $type = null)
    {
        // Create a variant to store the key value data.
        $valueData = new VARIANT();

        // Auto detect type. Not recommended - see getValueType() for details.
        if (!$type) {
            $type = $this->getValueType($name);
        }

        $normalizedValue = null;
        $errorCode       = 0;

        // Get the value's data type.
        switch ($type) {
            // String Type.
            case self::TYPE_SZ:
                $errorCode       = $this->handle->getStringValue($this->hive, $this->name, $name, $valueData);
                $normalizedValue = (string)$valueData;
                break;

            // Expanded String Type.
            case self::TYPE_EXPAND_SZ:
                $errorCode       = $this->handle->getExpandedStringValue($this->hive, $this->name, $name, $valueData);
                $normalizedValue = (string)$valueData;
                break;

            // Binary Type.
            case self::TYPE_BINARY:
                $errorCode    = $this->handle->getBinaryValue($this->hive, $this->name, $name, $valueData);
                $binaryString = '';

                // Enumerate over each byte.
                if (variant_get_type($valueData) & VT_ARRAY) {
                    foreach ($valueData as $byte) {
                        // Add the byte code to the byte string.
                        $binaryString .= chr((int)$byte);
                    }
                }

                $normalizedValue = $binaryString;
                break;

            // Integer Type.
            case self::TYPE_DWORD:
                $errorCode       = $this->handle->getDWORDValue($this->hive, $this->name, $name, $valueData);
                $normalizedValue = (int)$valueData;
                break;

            // Big-Integer Type.
            case self::TYPE_QWORD:
                $errorCode       = $this->handle->getQWORDValue($this->hive, $this->name, $name, $valueData);
                $normalizedValue = (string)$valueData;
                break;

            // String Array Type.
            case self::TYPE_MULTI_SZ:
                $errorCode = $this->handle->getMultiStringValue($this->hive, $this->name, $name, $valueData);

                $stringArray = [];
                // Enumerate over each sub string.
                if (variant_get_type($valueData) & VT_ARRAY) {
                    foreach ($valueData as $subValueData) {
                        $stringArray[] = (string)$subValueData;
                    }
                }

                $normalizedValue = $stringArray;
                break;
        }

        if ($errorCode !== 0) {
            // Reading the value from the registry failed.
            throw new OperationFailedException("Failed to read value \"{$name}\".");
        }

        return $normalizedValue;
    }

    /**
     * Gets the data type of a given value.
     *
     * Note: This is a relatively expensive operation. Especially for keys with lots of values.
     *
     * @param string $name The name of the value.
     *
     * @return int The type of the value.
     * @throws ValueNotFoundException When the value doesn't exist.
     */
    public function getValueType(string $name): int
    {
        // Iterate over all values in the key.
        $iterator = $this->getValueIterator();
        $iterator->rewind();
        while ($iterator->valid()) {
            if ($iterator->key() === $name) {
                return $iterator->currentType();
            }
            $iterator->next();
        }

        throw new ValueNotFoundException("The value '{$name}' does not exist.");
    }

    /**
     * Gets an iterator for iterating over key values.
     *
     * @return RegistryValueIterator Value Iterator.
     */
    public function getValueIterator(): RegistryValueIterator
    {
        return new RegistryValueIterator($this->handle, $this);
    }

    /**
     * Sets the value data of a named key value.
     *
     * @param string $name  The name of the value.
     * @param mixed  $value The value data of the value.
     * @param int    $type  The value type of the value.
     *
     * @throws InvalidTypeException     When the defined type isn't a valid registry type.
     *                                  When setting a non-array type as a MultiString.
     * @throws OperationFailedException When setting the value failed.
     */
    public function setValue(string $name, $value, int $type)
    {
        // Set differently depending on type.
        switch ($type) {
            case self::TYPE_SZ:
                $errorCode = $this->handle->setStringValue($this->hive, $this->name, $name, (string)$value);
                break;

            case self::TYPE_EXPAND_SZ:
                $errorCode = $this->handle->setExpandedStringValue($this->hive, $this->name, $name, (string)$value);
                break;

            case self::TYPE_BINARY:
                if (is_string($value)) {
                    $value = array_map('ord', str_split($value));
                }
                $errorCode = $this->handle->setBinaryValue($this->hive, $this->name, $name, $value);
                break;

            case self::TYPE_DWORD:
                $errorCode = $this->handle->setDWORDValue($this->hive, $this->name, $name, (int)$value);
                break;

            case self::TYPE_MULTI_SZ:
                if (!is_array($value)) {
                    throw new InvalidTypeException('Cannot set non-array type as MultiString.');
                }
                $errorCode = $this->handle->setMultiStringValue($this->hive, $this->name, $name, $value);
                break;

            default:
                throw new InvalidTypeException("The value {$type} is not a valid registry type.");
        }

        if ($errorCode !== 0) {
            // Setting the value failed.
            throw new OperationFailedException("Failed to write value \"{$name}\".");
        }
    }

    /**
     * Deletes a named value from the key.
     *
     * @param string $name The name of the named value to delete.
     *
     * @throws OperationFailedException When deleting the value failed.
     * @throws ValueNotFoundException When the value doesn't exist.
     */
    public function deleteValue(string $name)
    {
        if ($this->handle->deleteValue($this->hive, $this->name, $name) !== 0) {
            throw new OperationFailedException("Failed to delete value '{$name}' from key '$this->name}'.");
        }

        if (!$this->valueExists($name)) {
            throw new ValueNotFoundException("The value '{$name}' does not exist.");
        }
    }

    /**
     * Checks if a named value exists.
     *
     * @param string $name The name of the value to check.
     *
     * @return bool True if the value exists, otherwise false.
     */
    public function valueExists(string $name): bool
    {
        $value = null;

        // Validate the return value "1" (Assuming '1' means the value doesn't exist).
        return $this->handle->getStringValue(
                $this->hive,
                $this->name,
                $name,
                $value
            ) !== 1;
    }
}
