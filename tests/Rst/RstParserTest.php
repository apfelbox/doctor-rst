<?php

declare(strict_types=1);

/*
 * This file is part of DOCtor-RST.
 *
 * (c) Oskar Stark <oskarstark@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Rst;

use App\Rst\RstParser;
use App\Value\Line;
use PHPUnit\Framework\TestCase;

final class RstParserTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider isLineNumberAnnotationProvider
     */
    public function isLineNumberAnnotation(bool $expected, string $string)
    {
        static::assertSame($expected, RstParser::isLineNumberAnnotation(new Line($string)));
    }

    public function isLineNumberAnnotationProvider(): \Generator
    {
        yield [true, 'Line 15'];
        yield [true, 'Line 16 - 18'];
        yield [true, 'Line 16-18'];

        yield [false, ''];
        yield [false, '.. code-block:: php'];
        yield [false, '.. index::'];
    }

    /**
     * @test
     *
     * @dataProvider isCommentProvider
     */
    public function isComment(bool $expected, string $string)
    {
        static::assertSame($expected, RstParser::isComment(new Line($string)));
    }

    public function isCommentProvider(): \Generator
    {
        yield [true, '.. I am a comment'];

        yield [false, ''];
        yield [false, '.. [1]'];
        yield [false, '.. [1] '];
        yield [false, ' .. [1] '];
    }

    /**
     * @test
     *
     * @dataProvider isFootnoteProvider
     */
    public function isFootnote(bool $expected, string $string)
    {
        static::assertSame($expected, RstParser::isFootnote(new Line($string)));
    }

    public function isFootnoteProvider(): \Generator
    {
        yield [false, ''];
        yield [true, '.. [1]'];
        yield [true, '.. [1] '];
        yield [true, ' .. [1] '];
    }

    /**
     * @test
     *
     * @dataProvider isListItemProvider
     */
    public function isListItem(bool $expected, string $string)
    {
        static::assertSame($expected, RstParser::isListItem(new Line($string)));
    }

    public function isListItemProvider(): \Generator
    {
        yield [false, ''];
        yield [true, '* Bullet point 1'];
        yield [true, '  * Bullet point 1'];
        yield [true, '#. list item 1'];
        yield [true, '  #. list item 1'];
    }

    /**
     * @test
     *
     * @dataProvider isLinkDefinitionProvider
     */
    public function isLinkDefinition(bool $expected, string $string)
    {
        static::assertSame($expected, RstParser::isLinkDefinition(new Line($string)));
    }

    public function isLinkDefinitionProvider()
    {
        yield [true, '.. _`Symfony`: https://symfony.com'];
        yield [true, '.. _`APCu`: https://github.com/krakjoe/apcu'];
        yield [true, '.. _APCu: https://github.com/krakjoe/apcu'];

        yield [false, '.. _APCu`: https://github.com/krakjoe/apcu'];
        yield [false, '.. _`APCu: https://github.com/krakjoe/apcu'];
        yield [false, ''];
        yield [false, 'I am text::'];
    }

    /**
     * @test
     *
     * @dataProvider isLinkUsageProvider
     */
    public function isLinkUsage(bool $expected, string $string)
    {
        static::assertSame($expected, RstParser::isLinkUsage($string));
    }

    public function isLinkUsageProvider()
    {
        yield [true, '`Symfony`_'];
        yield [true, '`APCu`_'];

        yield [false, ''];
        yield [false, 'I am text::'];
    }

    /**
     * @test
     *
     * @dataProvider isTableProvider
     */
    public function isTable(bool $expected, string $string)
    {
        static::assertSame($expected, RstParser::isTable(new Line($string)));
    }

    public function isTableProvider()
    {
        yield [true, '==='];
        yield [true, '=== ==='];
        yield [true, '=== === ==='];
        yield [true, '------------------ -------- -------- ------ ----------------------------------------------'];

        yield [false, '~~~'];
        yield [false, '***'];
        yield [false, '...'];
        yield [false, '^^^'];
        yield [false, ''];
        yield [false, 'I am text::'];
    }

    /**
     * @test
     *
     * @dataProvider directiveIsProvider
     */
    public function directiveIs(bool $expected, string $string, string $directive)
    {
        static::assertSame($expected, RstParser::directiveIs(new Line($string), $directive));
    }

    public function directiveIsProvider()
    {
        return [
            [false, '.. note::', RstParser::DIRECTIVE_CODE_BLOCK],
            [true, '.. note::', RstParser::DIRECTIVE_NOTE],
            [true, 'the following code is php::', RstParser::DIRECTIVE_CODE_BLOCK],
            [true, '.. code-block:: php', RstParser::DIRECTIVE_CODE_BLOCK],
            [true, '.. code-block:: text', RstParser::DIRECTIVE_CODE_BLOCK],
            [true, '.. code-block:: rst', RstParser::DIRECTIVE_CODE_BLOCK],
            [true, ' .. code-block:: php', RstParser::DIRECTIVE_CODE_BLOCK],
            [true, '.. code-block:: php-annotations', RstParser::DIRECTIVE_CODE_BLOCK],
            [false, 'foo', RstParser::DIRECTIVE_CODE_BLOCK],
        ];
    }

    /**
     * @test
     *
     * @dataProvider codeBlockDirectiveIsTypeOfProvider
     */
    public function codeBlockDirectiveIsTypeOf(bool $expected, string $string, string $type, bool $strict = false)
    {
        static::assertSame($expected, RstParser::codeBlockDirectiveIsTypeOf(new Line($string), $type, $strict));
    }

    public function codeBlockDirectiveIsTypeOfProvider()
    {
        return [
            [false, '.. note::', RstParser::CODE_BLOCK_PHP],
            [true, 'the following code is php::', RstParser::CODE_BLOCK_PHP],
            [true, '.. code-block:: php', RstParser::CODE_BLOCK_PHP],
            [true, ' .. code-block:: php', RstParser::CODE_BLOCK_PHP],
            [true, ' .. code-block:: php-annotations', RstParser::CODE_BLOCK_PHP_ANNOTATIONS],
            [true, ' .. code-block:: php-attributes', RstParser::CODE_BLOCK_PHP_ATTRIBUTES],
            [true, ' .. code-block:: text', RstParser::CODE_BLOCK_TEXT],
            [true, ' .. code-block:: rst', RstParser::CODE_BLOCK_RST],
            [false, 'foo', RstParser::CODE_BLOCK_PHP],
            [true, ' .. code-block:: php', RstParser::CODE_BLOCK_PHP, true],
            [true, ' .. code-block:: php-annotations', RstParser::CODE_BLOCK_PHP_ANNOTATIONS, false],
            [true, ' .. code-block:: php-attributes', RstParser::CODE_BLOCK_PHP_ATTRIBUTES, false],
            [true, ' .. code-block:: html+php', RstParser::CODE_BLOCK_PHP, false],
            [false, ' .. code-block:: html+php', RstParser::CODE_BLOCK_PHP, true],
        ];
    }

    /**
     * @test
     *
     * @dataProvider isOptionProvider
     */
    public function isOption(bool $expected, string $string)
    {
        static::assertSame($expected, RstParser::isOption(new Line($string)));
    }

    public function isOptionProvider()
    {
        return [
            [true, ':lineos:'],
            [true, ' :lineos: '],
            [true, ':language: text'],
            [true, ' :language: text '],
            [false, ' '],
            [false, ''],
            [false, '.. class::'],
        ];
    }
}
