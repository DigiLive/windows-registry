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
 * A wrapper around the Microsoft Windows StdRegProv WMI class.
 *
 * Watch out if you have a x64 OS because registry calls can be redirected to the Wow6432Node by the Registry Director.
 *  *
 * @see      http://msdn.microsoft.com/en-us/library/aa393664.aspx
 * @see      https://docs.microsoft.com/en-us/windows/win32/winprog64/registry-redirector?redirectedfrom=MSDN
 * @formatter:off
 * @method int  checkAccess(int $hDefKey, string $sSubKeyName, int $uRequired, bool &$bGranted)                 Verifies that the user has the specified access permissions. Not supported.
 * @method void createKey(int $hDefKey, string $sSubKeyName)                                                    Creates a subKey.
 * @method int  deleteKey(int $hDefKey, string $sSubKeyName)                                                    Deletes a subKey.
 * @method int  deleteValue(int $hDefKey, string $sSubKeyName, string $sValueName)                              Deletes a named value.
 * @method int  enumKey(int $hDefKey, string $sSubKeyName, VARIANT &$sNames)                                    Enumerates subKeys.
 * @method int  enumValues(int $hDefKey, string $sSubKeyName, VARIANT &$sNames, VARIANT &$Types)                Enumerates the named values of a key.
 * @method int  getBinaryValue(int $hDefKey, string $sSubKeyName, string $sValueName, VARIANT &$uValue)         Gets the binary data value of a named value.
 * @method int  getDWORDValue(int $hDefKey, string $sSubKeyName, string $sValueName, VARIANT &$uValue)          Gets the DWORD data value of a named value.
 * @method int  getExpandedStringValue(int $hDefKey, string $sSubKeyName, string $sValueName, string &$uValue)  Gets the expanded string data value of a named value.
 * @method int  getMultiStringValue(int $hDefKey, string $sSubKeyName, string $sValueName, VARIANT &$uValue)    Gets the multiple string data values of a named value.
 * @method int  getQWORDValue(int $hDefKey, string $sSubKeyName, string $sValueName, VARIANT &$uValue)          Gets the QWORD data values of a named value.
 * @method int  getSecurityDescriptor(int $hDefKey, string $sSubKeyName, &$Descriptor)                          Gets the security descriptor for a key. Not supported.
 * @method int  getStringValue(int $hDefKey, string $sSubKeyName, string $sValueName, string &$uValue)          Gets the string data value of a named value.
 * @method int  setBinaryValue(int $hDefKey, string $sSubKeyName, string $sValueName, int $uValue)              Sets the binary data value of a named value.
 * @method int  setDWORDValue(int $hDefKey, string $sSubKeyName, string $sValueName, int $uValue)               Sets the DWORD data value of a named value.
 * @method int  setExpandedStringValue(int $hDefKey, string $sSubKeyName, string $sValueName, string $uValue)   Sets the expanded string data value of a named value.
 * @method int  setMultiStringValue(int $hDefKey, string $sSubKeyName, string $sValueName, VARIANT $uValue)     Sets the multiple string values of a named value.
 * @method int  setQWORDValue(int $hDefKey, string $sSubKeyName, string $sValueName, int $uValue)               Sets the QWORD data values of a named value. Not supported.
 * @method int  setSecurityDescriptor(int $hDefKey, string $sSubKeyName, $Descriptor)                           Sets the security descriptor for a key. Not supported.
 * @method int  setStringValue(int $hDefKey, string $sSubKeyName, string $sValueName, string $uValue)           Sets the string value of a named value.
 * @formatter:on
 */
class RegistryHandle
{
    /**
     * @var VARIANT An StdRegProv instance.
     */
    protected $stdRegProv;

    /**
     * Creates a new wrapper for an StdRegProv instance.
     *
     * @param VARIANT $stdRegProv The StdRegProv instance to wrap.
     */
    public function __construct(VARIANT $stdRegProv)
    {
        $this->stdRegProv = $stdRegProv;
    }

    /**
     * Calls a dynamic method of the StdRegProv instance.
     *
     * @param string  $name The name of the method to call.
     * @param array  &$args An array of arguments to pass to the method.
     *
     * @return mixed The return value of the method call.
     */
    public function __call(string $name, array $args)
    {
        $argRefs = [];
        foreach ($args as $key => &$arg) {
            $argRefs[$key] = &$arg;
        }

        return call_user_func_array([$this->stdRegProv, ucfirst($name)], $argRefs);
    }
}
