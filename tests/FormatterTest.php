<?php

declare(strict_types=1);

namespace PrettyXml\Tests;

use PHPUnit\Framework\TestCase;
use PrettyXml\Formatter;

final class FormatterTest extends TestCase
{
    private Formatter $subject;

    public function setUp(): void
    {
        $this->subject = new Formatter();
    }

    public function testItShouldIndentANestedElement(): void
    {
        $input = '<?xml version="1.0" encoding="UTF-8"?><foo><bar>Baz</bar></foo>';
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
    <bar>Baz</bar>
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testItShouldIndentAVeryNestedElement(): void
    {
        $input = '<?xml version="1.0" encoding="UTF-8"?><foo><bar><bacon><bob>Baz</bob></bacon></bar></foo>';
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
    <bar>
        <bacon>
            <bob>Baz</bob>
        </bacon>
    </bar>
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testItShouldIndentTwoNestedElements(): void
    {
        $input = '<?xml version="1.0" encoding="UTF-8"?><foo><bar>Baz</bar><egg>Bacon</egg></foo>';
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
    <bar>Baz</bar>
    <egg>Bacon</egg>
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testItShouldIndentANestedEmptyElement(): void
    {
        $input = '<?xml version="1.0" encoding="UTF-8"?><foo><bar /></foo>';
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
    <bar />
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testItShouldIndentDoubleNestedElements(): void
    {
        $input = '<?xml version="1.0" encoding="UTF-8"?><foo><bar><egg /></bar></foo>';
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
    <bar>
        <egg />
    </bar>
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testItShouldIndentANestedElementWithAnAttribute(): void
    {
        $input = '<?xml version="1.0" encoding="UTF-8"?><foo><bar a="b">Baz</bar></foo>';
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
    <bar a="b">Baz</bar>
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testItShouldIndentANestedElementWhenParentHasAttributes(): void
    {
        $input = '<?xml version="1.0" encoding="UTF-8"?><foo a="b"><bar>Baz</bar></foo>';
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo a="b">
    <bar>Baz</bar>
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testItShouldChangeTheSizeOfTheIndent(): void
    {
        $this->subject->setIndentSize(2);
        $input = '<?xml version="1.0" encoding="UTF-8"?><foo><bar>Baz</bar></foo>';
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
  <bar>Baz</bar>
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testItShouldChangeTheIndentCharacter(): void
    {
        $this->subject->setIndentCharacter('_');
        $input = '<?xml version="1.0" encoding="UTF-8"?><foo><bar>Baz</bar></foo>';
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
____<bar>Baz</bar>
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testItShouldRemoveExistingExcessWhitespace(): void
    {
        $input = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
    <bar>Baz</bar>
    <egg>
                <bacon>Yum</bacon>
                        </egg>
</foo>
XML;
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
    <bar>Baz</bar>
    <egg>
        <bacon>Yum</bacon>
    </egg>
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testItRespectsWhitespaceInCdataTags(): void
    {
        $input = <<<XML
<?xml version="1.0" encoding="UTF-8"?><foo>
  <bar><![CDATA[some
whitespaced   words
      blah]]></bar></foo>
XML;
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
    <bar>
        <![CDATA[some
whitespaced   words
      blah]]>
    </bar>
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testItShouldSupportUnderscoresInTagNames(): void
    {
        $input = '<?xml version="1.0" encoding="UTF-8"?><foo><foo_bar>Baz</foo_bar></foo>';
        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<foo>
    <foo_bar>Baz</foo_bar>
</foo>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }

    public function testSimpleMagentoConfig(): void
    {
        $input = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<config>
    <modules>
        <Behat_Spec>
            <version>0.1.0</version>
            </Behat_Spec>
            </modules>
            <global>
                <blocks>
                    <behat_spec>
                        <class>Behat_Spec_Block</class>
                        </behat_spec>
                        </blocks>
                        <helpers>
                            <behat_spec>
                                <class>Behat_Spec_Helper</class>
                                </behat_spec>
                                </helpers>
                                <models>
                                    <behat_spec>
                                        <class>Behat_Spec_Model</class>
                                        <resourceModel>behat_spec_resource</resourceModel>
                                    </behat_spec>
                                    <behat_spec_resource>
                                        <class>Behat_Spec_Model_Resource</class>
                                    </behat_spec_resource>
                                </models>
                                </global>
                                <config>
                                    <frontend>
                                        <routers>
                                            <spec>
                                                <use>standard</use>
                                                <args>
                                                    <module>Behat_Spec</module>
                                                    <frontName>spec</frontName>
                                                    </args>
                                                    </spec>
                                                    </routers>
                                                    </frontend>
                                                    </config>
                                                    </config>
XML;

        $expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<config>
    <modules>
        <Behat_Spec>
            <version>0.1.0</version>
        </Behat_Spec>
    </modules>
    <global>
        <blocks>
            <behat_spec>
                <class>Behat_Spec_Block</class>
            </behat_spec>
        </blocks>
        <helpers>
            <behat_spec>
                <class>Behat_Spec_Helper</class>
            </behat_spec>
        </helpers>
        <models>
            <behat_spec>
                <class>Behat_Spec_Model</class>
                <resourceModel>behat_spec_resource</resourceModel>
            </behat_spec>
            <behat_spec_resource>
                <class>Behat_Spec_Model_Resource</class>
            </behat_spec_resource>
        </models>
    </global>
    <config>
        <frontend>
            <routers>
                <spec>
                    <use>standard</use>
                    <args>
                        <module>Behat_Spec</module>
                        <frontName>spec</frontName>
                    </args>
                </spec>
            </routers>
        </frontend>
    </config>
</config>
XML;
        self::assertEquals($expected, $this->subject->format($input));
    }
}
