<?php
/*
 * Copyright 2014 Stephen Coakley <me@stephencoakley.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy
 * of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace Coderstephen\Windows\Registry;

/**
 * A wrapper around the Microsoft Windows StdRegProv WMI class.
 *
 * @see http://msdn.microsoft.com/en-us/library/aa393664.aspx
 */
class RegistryHandle
{
    /**
     * An StdRegProv instance.
     * @type \VARIANT
     */
    protected $stdRegProv;

    /**
     * Creates a new wrapper for an StdRegProv instance.
     *
     * @param \VARIANT $stdRegProv The StdRegProv instance to wrap.
     */
    public function __construct(\VARIANT $stdRegProv)
    {
        $this->stdRegProv = $stdRegProv;
    }

    /**
     * Calls a dynamic method of the StdRegProv instance.
     *
     * @param  string $name  The name of the method to call.
     * @param  array  &$args An array of arguments to pass to the method.
     * @return mixed         The return value of the method call.
     */
    public function __call($name, $args)
    {
        $argRefs = array();
        foreach($args as $key => &$arg){
            $argRefs[$key] = &$arg;
        }
        return call_user_func_array(array($this->stdRegProv, ucfirst($name)), $argRefs);
    }
}
