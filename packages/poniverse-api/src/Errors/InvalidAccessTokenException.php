<?php

/**
 * Copyright 2016 Peter Deltchev
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Poniverse\Lib\Errors;

/**
 * Class InvalidAccessTokenException
 * @package Poniverse\Lib\Errors
 *
 * This exception indicates that an access token we attempted to introspect
 * through the Poniverse API is expired or otherwise unusable.
 */
class InvalidAccessTokenException extends ApiException {}
