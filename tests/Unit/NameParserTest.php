<?php

namespace Tests\Unit;

use App\Support\NameParser;
use PHPUnit\Framework\TestCase;

class NameParserTest extends TestCase
{
    protected NameParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new NameParser();
    }

    /** @test */
    public function it_splits_shared_surname_pairs()
    {
        $segments = $this->parser->splitIntoSegments('Mr and Mrs Smith');
        $this->assertSame(['Mr Smith', 'Mrs Smith'], $segments);
    }

    /** @test */
    public function it_splits_title_and_title_with_full_tail()
    {
        $segments = $this->parser->splitIntoSegments('Dr & Mrs Joe Bloggs');
        $this->assertSame(['Dr Joe Bloggs', 'Mrs Bloggs'], $segments);
    }

    /** @test */
    public function it_splits_two_full_people()
    {
        $segments = $this->parser->splitIntoSegments('Mr Tom Staff and Mr John Doe');
        $this->assertSame(['Mr Tom Staff', 'Mr John Doe'], $segments);
    }

    /** @test */
    public function it_keeps_single_person_as_is()
    {
        $segments = $this->parser->splitIntoSegments('Ms Claire Robbo');
        $this->assertSame(['Ms Claire Robbo'], $segments);
    }

    /** @test */
    public function it_parses_title_first_last()
    {
        $p = $this->parser->parseSingleSegment('Mr John Smith');

        $this->assertSame([
            'title'      => 'Mr',
            'first_name' => 'John',
            'initial'    => null,
            'last_name'  => 'Smith',
        ], $p);
    }

    /** @test */
    public function it_parses_title_initial_last()
    {
        $p = $this->parser->parseSingleSegment('Mr F. Fredrickson');

        $this->assertSame([
            'title'      => 'Mr',
            'first_name' => null,
            'initial'    => 'F',
            'last_name'  => 'Fredrickson',
        ], $p);
    }

    /** @test */
    public function it_parses_title_last_only()
    {
        $p = $this->parser->parseSingleSegment('Mrs Smith');

        $this->assertSame([
            'title'      => 'Mrs',
            'first_name' => null,
            'initial'    => null,
            'last_name'  => 'Smith',
        ], $p);
    }

    /** @test */
    public function it_normalizes_mister_to_mr()
    {
        $p = $this->parser->parseSingleSegment('Mister John Doe');

        $this->assertSame([
            'title'      => 'Mr',
            'first_name' => 'John',
            'initial'    => null,
            'last_name'  => 'Doe',
        ], $p);
    }

    /** @test */
    public function it_handles_hyphenated_surnames()
    {
        $p = $this->parser->parseSingleSegment('Mrs Faye Hughes-Eastwood');

        $this->assertSame([
            'title'      => 'Mrs',
            'first_name' => 'Faye',
            'initial'    => null,
            'last_name'  => 'Hughes-Eastwood',
        ], $p);
    }
}
