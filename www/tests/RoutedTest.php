<?php

use PHPUnit\Framework\TestCase;
use Peter\Parser;

final class RoutedTest extends TestCase
{
   public function testShouldCreateARoutedTokenForThePath(): void {
        $out = Parser::pathSyntax('one[{ranges}].oneMore', true);
        $output = array('one', (object) [ 'type' => 'ranges', 'named'=> false, 'name'=> '' ], 'oneMore');
     
        $this->assertEquals($out, $output);
        //expect(out).to.deep.equal(['one', {type: 'ranges', named: false, name: ''}, 'oneMore']);
    }

    public function testShouldCreateANamedRoutedTokenForThePath(): void {
        $out = Parser::pathSyntax('one[{ranges:foo}].oneMore', true);
        $output = array('one', (object) [ 'type' => 'ranges', 'named'=> true, 'name'=> 'foo' ], 'oneMore');
     
        $this->assertEquals($out, $output);
        //expect(out).to.deep.equal(['one', {type: 'ranges', named: true, name: 'foo'}, 'oneMore']);
    }

    /*public function testShouldCreateANamedRoutedTokenForThePathAndAllowWhiteSpaceBeforeTheDefinition(): void {
        $out = Parser::pathSyntax('one[{ranges: \t\n\rfoo}].oneMore', true);
        $output = array('one', (object) [ 'type' => 'ranges', 'named'=> true, 'name'=> 'foo' ], 'oneMore');
     
        $this->assertEquals($out, $output);
        //expect(out).to.deep.equal(['one', {type: 'ranges', named: true, name: 'foo'}, 'oneMore']);
    }

    public function testShouldCreateANamedRoutedTokenForThePathAndAllowWhiteSpaceAfterTheDefinition(): void {
        $out = Parser::pathSyntax('one[{ranges:foo \t\n\r}].oneMore', true);
        $output = array('one', (object) [ 'type' => 'ranges', 'named'=> true, 'name'=> 'foo' ], 'oneMore');
     
        $this->assertEquals($out, $output);
        //expect(out).to.deep.equal(['one', {type: 'ranges', named: true, name: 'foo'}, 'oneMore']);
    }
    
    public function testShouldCreateANamedRoutedTokenForThePathAndAllowWhiteSpaceBeforeAndAfterTheDefinition(): void {
        $out = Parser::pathSyntax('one[{ranges: \t\n\rfoo \t\n\r}].oneMore', true);
        $output = array('one', (object) [ 'type' => 'ranges', 'named'=> true, 'name'=> 'foo' ], 'oneMore');
     
        $this->assertEquals($out, $output);
        //expect(out).to.deep.equal(['one', {type: 'ranges', named: true, name: 'foo'}, 'oneMore']);
    }*/
    
}