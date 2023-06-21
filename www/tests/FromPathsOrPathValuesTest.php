<?php

use PHPUnit\Framework\TestCase;
use Peter\Parser;

final class FromPathsOrPathValuesTest extends TestCase
{
   public function testShouldConvertAStringToPath(): void {
        $input = array('videos[1234].summary');
        $output = array(array('videos', 1234, 'summary'));

        $out = Parser::fromPathsOrPathValues($input);
        $this->assertSame($out, $output);
        //expect(parser.fromPathsOrPathValues(input)).to.deep.equal(output);
    }

    public function testShouldConvertAnUndefinedToPath(): void {
        $input = null;
        $output = array();

        $out = Parser::fromPathsOrPathValues($input);
        $this->assertSame($out, $output);
        //expect(parser.fromPathsOrPathValues(input)).to.deep.equal(output);
    }

    /*public function testShouldReturnAProvidedArray(): void {
        $input = array(array('videos', 1234, 'summary'));
        $output = array(array('videos', 1234, 'summary'));

        $out = Parser::fromPathsOrPathValues($input);
       
        $this->assertSame($out, $output);
        //expect(parser.fromPathsOrPathValues(input)).to.deep.equal(output);
    }

    public function testShouldConvertWithABunchOfValues(): void {
        $input = array(
            array('videos', 1234, 'summary'),
            'videos[555].summary',
            (object) [ 'path' => 'videos[444].summary', 'value' => 5 ]
        );
        $output = array(
            array('videos', 1234, 'summary'),
            array('videos', 555, 'summary'),
            (object) [ 'path' => array('videos', 444, 'summary'), 'value' => 5 ]
        );

        $out = Parser::fromPathsOrPathValues($input);
        $this->assertEquals($out, $output);
        //expect(parser.fromPathsOrPathValues(input)).to.deep.equal(output);
    }*/



}