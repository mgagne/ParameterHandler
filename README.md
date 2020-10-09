Composer Parameter Handler
==========================

This is heavily based and derivative work from Incenteev/ParameterHandler.

Usage
=====

Add the following in your root composer.json file:

```
{
    "require": {
        "singlehopllc/composer-parameter-handler": "*"
    },
    "scripts": {
        "post-install-cmd": [
            "Inap\\ParameterHandler\\ScriptHandler::buildParameters"
        ],
        "post-update-cmd": [
            "Inap\\ParameterHandler\\ScriptHandler::buildParameters"
        ]
    },
    "extra": {
        "inap-parameters": {
            "definitions-file": "parameters.yml"
        }
    }
}
```

Create a parameter definitions file:

```
---

# Destination parameters file (required)
parameters-file: config/packages/parameters.yml

# Dist parameters file (optional)
# Defaults: <parameters-file>.dist
# parameters-dist-file: config/packages/parameters.yml.dist

# Raises an error if a definition is not found in the definitions file
# for a parameter found in <parameters-file>.dist
# If true, a undefined parameter is considered optional and of "string" type.
# Defaults: true
# ignore-unknown-parameters: false

# Parameter definitions
parameters:
  app.string:
    variable: SYMFONY_APP_NAME
    type: string
    required: true
    description: Symfony Application Name
    constraints:
      - length: {min: 10}
  app.number:
    type: number
    required: true
    constraints:
      - range: {min: 10, max: 20}
  app.boolean:
    type: boolean
    required: true
  app.list:
    type: list
    required: true
  app.one_of_allowed_values:
    type: string
    required: true
    constraints:
      - allowed_values:
          - foo
          - bar
  app.allowed_pattern:
    type: string
    required: true
    constraints:
      - allowed_pattern: '^hello$'
#  <parameter-name>:
#    variable: <ENVIRONMENT_VARIABLE_NAME>
#    type: <string, number, boolean, list>
#    required: <true, false>
#    constraints:
#      - length: {min: <int>, max: <int>}
#      - range: {min: <int>, max: <int>}
#      - allowed_values: [<value1, <value2>]
#      - allowed_pattern: '^foobar$'
```

Removed features
================

Following features from the original work have been dropped:
* Remove support for renamed parameters
* Remove support to keep outdated parameters in parameters.yml
* Remove support for multiple ignored files

Original License
================

```
Copyright (c) 2012 Christophe Coevoet

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is furnished
to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
```
