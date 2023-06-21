<?php



use PHPUnit\Framework\TestCase;
use Peter\Parser;

final class PathSyntaxTest extends TestCase
{
    public function testShouldParseASimpleKey(): void
    {
        $out = Parser::pathSyntax("one.two.three");
        $this->assertSame($out, array('one', 'two', 'three'));
    }
    
    public function testShouldParseAStringWithIndexers(): void
    {
        $out = Parser::pathSyntax('one[0]');
        $this->assertSame($out, array('one', 0));
        
    }

    public function testShouldParseAStringWithIndexersFollowedByDotSeparators(): void
    {
        $out = Parser::pathSyntax('one[0].oneMore');
        $this->assertSame($out, array('one', 0, 'oneMore'));
    }

    public function testShouldParseAStringWithARange(): void {
        $out = Parser::pathSyntax('one[0..5].oneMore');
        $this->assertEquals($out, array('one', (object) [ 'from' => 0, 'to' => 5 ], 'oneMore'));
        //expect(out).to.deep.equal(['one', {from: 0, to: 5}, 'oneMore']);
    }

    public function testShouldParseAStringWithASetOfTokens(): void {
        $out = Parser::pathSyntax('one["test", \'test2\'].oneMore');
        $this->assertSame($out, array('one', array('test', 'test2'), 'oneMore'));
        //expect(out).to.deep.equal(['one', ['test', 'test2'], 'oneMore']);
    }

    public function testShouldTreat07As7(): void {
        
        $out = Parser::pathSyntax('one[07, 0001].oneMore');
        $this->assertSame($out, array('one', array(7, 1), 'oneMore'));
        //expect(out).to.deep.equal(['one', [7, 1], 'oneMore']);
    }
    
    public function testShouldParseOutARange(): void {
        $out = Parser::pathSyntax('one[0..1].oneMore');
        $this->assertEquals($out, array('one', (object) [ 'from' => 0, 'to' => 1 ], 'oneMore'));
        //expect(out).to.deep.equal(['one', {from: 0, to: 1}, 'oneMore']);
    }
    
    public function testShouldParseOutMultipleRanges(): void {
        $out = Parser::pathSyntax('one[0..1,3..4].oneMore');
        $this->assertEquals($out, array('one', array((object) [ 'from' => 0, 'to' => 1 ],(object) [ 'from' => 3, 'to' => 4 ]), 'oneMore'));
        //expect(out).to.deep.equal(['one', [{from: 0, to: 1}, {from: 3, to: 4}], 'oneMore']);
    }

    /*public function testShouldParsePathsWithNewlinesAndWhitespaceBetweenIndexerKeys(): void {

        $input = 'one[\n\
        0, 1, 2, 3, 4, \n\
        5, 6, 7, 8, 9].oneMore';
        $output = array('one', array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9), 'oneMore');

        $out = Parser::pathSyntax($input);
        $this->assertSame($out, $output);
        //expect(out).to.deep.equal(['one', [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 'oneMore']);
    }*/
}