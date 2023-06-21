<?php

//include("pathSyntax/path-syntax.php");

use PHPUnit\Framework\TestCase;
use Peter\Parser;

final class FromPathTest extends TestCase
{
    public function testShouldConvertAStringToPath(): void {
        $input = 'videos[1234].summary';
        $output = array('videos', 1234, 'summary');

        $out = Parser::fromPath($input);
        $this->assertSame($out, $output);
        //expect(parser.fromPath(input)).to.deep.equal(output);
    }

    public function testShouldReturnAProvidedArray(): void {
        $input = array('videos', 1234, 'summary');
        $output = array('videos', 1234, 'summary');
        $out = Parser::fromPath($input);
        $this->assertSame($out, $output);
        //expect(parser.fromPath(input)).to.deep.equal(output);
    }

    public function testShouldConvertAnUndefined(): void {
        $input = null;
        $output = array();
        $out = Parser::fromPath($input);
        $this->assertSame($out, $output);
        //expect(parser.fromPath(input)).to.deep.equal(output);
    }


}