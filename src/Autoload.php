<?php
/**
 * Copyright 2015 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * Apache License, Version 2.0
 *    <http://www.apache.org/licenses/LICENSE-2.0>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace ACurl;

/**
 * @package ACurl
 * @object  ACurl\Autoload
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Autoload
{
    /**
     * Register.
     * @return bool
     */
    final public static function register()
    {
        return spl_autoload_register(function($className) {
            // check namespace
            if (strpos($className, __namespace__) !== 0) {
                return;
            }

            $classBase = substr($className, strlen(__namespace__) + 1);
            $classFile = sprintf('%s/%s.php', __dir__, str_replace('\\', '/', $classBase));

            require $classFile;
        });
    }
}
