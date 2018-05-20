[![Build Status](https://travis-ci.org/hollodotme/phpunit-testdox-markdown.svg?branch=master)](https://travis-ci.org/hollodotme/phpunit-testdox-markdown)
[![Latest Stable Version](https://poser.pugx.org/hollodotme/phpunit-testdox-markdown/v/stable)](https://packagist.org/packages/hollodotme/phpunit-testdox-markdown) 
[![Total Downloads](https://poser.pugx.org/hollodotme/phpunit-testdox-markdown/downloads)](https://packagist.org/packages/hollodotme/phpunit-testdox-markdown) 
[![Coverage Status](https://coveralls.io/repos/github/hollodotme/phpunit-testdox-markdown/badge.svg?branch=master)](https://coveralls.io/github/hollodotme/phpunit-testdox-markdown?branch=master)

# PHPUnit\TestListeners\TestDox

## Description

A PHPUnit test listener that creates a testdox markdown file with grouped dataset output

## Installation

```bash
composer require --dev hollodotme/phpunit-testdox-markdown
```

## Usage

If you are using PSR-4 autoloading, add the following to your `phpunit.xml`:
```xml
<phpunit ...>
    <listeners>
        <listener class="hollodotme\PHPUnit\TestListeners\TestDox\Markdown">
            <arguments>
                <string name="environment">Development</string>
                <string name="outputFile">build/logs/TestDox.md</string>
            </arguments>
        </listener>
    </listeners>
</phpunit>
```

If you're not using PSR-4 autoloading, also add the class' file path to the `<listener>` tag:
```xml
<phpunit ...>
    <listeners>
        <listener class="hollodotme\PHPUnit\TestListeners\TestDox\Markdown" 
                  file="/path/to/vendor/hollodotme/phpunit-testdox-markdown/src/Markdown.php">
            <arguments>
                <string name="environment">Development</string>
                <string name="outputFile">build/logs/TestDox.md</string>
            </arguments>
        </listener>
    </listeners>
</phpunit>
```

### Available listener arguments

| Name            | Type     | Required | Meaning                                                                                                                                                                                                                                            |
|-----------------|----------|----------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `environment`   | `string` | YES      | Is printed on top of the testdox report to identifiy on which build stage the report has been created.                                                                                                                                             |
| `outputFile`    | `string` | YES      | Relative path to the markdown testdox output file. Please note: the path must be relative to your current working directory, not to the location of the `phpunit.xml`.                                                                             |
| `baseNamespace` | `string` | NO       | A part of your test namespace to shorten the output headlines. Example: Your test namespace is `YourVendor\YourProject\Tests\Unit` and you set `baseNamespace` to `YourVendor\YourProject\Tests` all headlines will be prefixed with `Unit\` only. |
| `testStatusMap` | `array`  | NO       | A key-value array of strings that let's you manipulate the icons for each test result status. See example below.                                                                                                                                   | 

### Example with all available arguments

```xml
<phpunit ...>
    <listeners>
        <listener class="hollodotme\PHPUnit\TestListeners\TestDox\Markdown">
            <arguments>
                <string name="environment">Development</string>
                <string name="outputFile">build/logs/TestDox.md</string>
                <string name="baseNamespace">YourVendor\YourProject\Tests</string>
                <array name="testStatusMap">
                    <element key="Passed">
                        <string>💚</string>
                    </element>
                    <element key="Error">
                        <string>💔</string>
                    </element>
                    <element key="Failure">
                        <string>💔</string>
                    </element>
                    <element key="Warning">
                        <string>🧡</string>
                    </element>
                    <element key="Risky">
                        <string>💛</string>
                    </element>
                    <element key="Incomplete">
                        <string>💙</string>
                    </element>
                    <element key="Skipped">
                        <string>💜</string>
                    </element>
                </array>
            </arguments>
        </listener>
    </listeners>
</phpunit>
```

## Example output

---

---

## Contributing

Contributions are welcome and will be fully credited. Please see the [contribution guide](.github/CONTRIBUTING.md) for details.


