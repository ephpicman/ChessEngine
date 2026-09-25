<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\Pieces;
use PHPUnit\Framework\TestCase;

final class PiecesTest extends TestCase
{
    public function testNewCollectionIsEmpty(): void
    {
        $pieces = new Pieces();

        self::assertSame(0, $pieces->count());
        self::assertSame([], $pieces->all());
    }

    public function testAddStoresPiece(): void
    {
        $pieces = new Pieces();

        $piece = new Piece(
            'white-king',
            Color::WHITE,
            PieceType::KING
        );

        $pieces->add($piece);

        self::assertSame(1, $pieces->count());
        self::assertTrue($pieces->contains('white-king'));
        self::assertSame($piece, $pieces->get('white-king'));
    }

    public function testAddMultiplePieces(): void
    {
        $pieces = new Pieces();

        $whiteKing = new Piece(
            'white-king',
            Color::WHITE,
            PieceType::KING
        );

        $blackKing = new Piece(
            'black-king',
            Color::BLACK,
            PieceType::KING
        );

        $whiteQueen = new Piece(
            'white-queen',
            Color::WHITE,
            PieceType::QUEEN
        );

        $pieces->add($whiteKing);
        $pieces->add($blackKing);
        $pieces->add($whiteQueen);

        self::assertSame(3, $pieces->count());

        self::assertSame(
            $whiteKing,
            $pieces->get('white-king')
        );

        self::assertSame(
            $blackKing,
            $pieces->get('black-king')
        );

        self::assertSame(
            $whiteQueen,
            $pieces->get('white-queen')
        );
    }

    public function testAddThrowsExceptionForDuplicateIdentifier(): void
    {
        $pieces = new Pieces();

        $first = new Piece(
            'white-pawn-1',
            Color::WHITE,
            PieceType::PAWN
        );

        $second = new Piece(
            'white-pawn-1',
            Color::WHITE,
            PieceType::PAWN
        );

        $pieces->add($first);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "A piece with ID 'white-pawn-1' already exists."
        );

        $pieces->add($second);
    }

    public function testDuplicateAddDoesNotReplaceExistingPiece(): void
    {
        $pieces = new Pieces();

        $first = new Piece(
            'white-pawn-1',
            Color::WHITE,
            PieceType::PAWN
        );

        $second = new Piece(
            'white-pawn-1',
            Color::WHITE,
            PieceType::QUEEN
        );

        $pieces->add($first);

        try {
            $pieces->add($second);
        } catch (\InvalidArgumentException) {
            // Expected.
        }

        self::assertSame(1, $pieces->count());
        self::assertSame($first, $pieces->get('white-pawn-1'));
    }

    public function testGetThrowsExceptionForUnknownIdentifier(): void
    {
        $pieces = new Pieces();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage(
            "Piece with ID 'unknown' does not exist."
        );

        $pieces->get('unknown');
    }

    public function testContainsReturnsTrueForExistingPiece(): void
    {
        $pieces = new Pieces();

        $pieces->add(
            new Piece(
                'white-rook-a',
                Color::WHITE,
                PieceType::ROOK
            )
        );

        self::assertTrue($pieces->contains('white-rook-a'));
    }

    public function testContainsReturnsFalseForUnknownPiece(): void
    {
        $pieces = new Pieces();

        self::assertFalse($pieces->contains('white-rook-a'));
    }

    public function testRemoveDeletesExistingPiece(): void
    {
        $pieces = new Pieces();

        $piece = new Piece(
            'black-knight-b',
            Color::BLACK,
            PieceType::KNIGHT
        );

        $pieces->add($piece);

        self::assertTrue($pieces->contains('black-knight-b'));
        self::assertSame(1, $pieces->count());

        $pieces->remove('black-knight-b');

        self::assertFalse($pieces->contains('black-knight-b'));
        self::assertSame(0, $pieces->count());
    }

    public function testRemoveUnknownIdentifierDoesNothing(): void
    {
        $pieces = new Pieces();

        $piece = new Piece(
            'white-bishop-c',
            Color::WHITE,
            PieceType::BISHOP
        );

        $pieces->add($piece);

        $pieces->remove('unknown');

        self::assertSame(1, $pieces->count());
        self::assertSame($piece, $pieces->get('white-bishop-c'));
    }

    public function testAllReturnsAllPiecesIndexedByIdentifier(): void
    {
        $pieces = new Pieces();

        $whiteKing = new Piece(
            'white-king',
            Color::WHITE,
            PieceType::KING
        );

        $blackQueen = new Piece(
            'black-queen',
            Color::BLACK,
            PieceType::QUEEN
        );

        $pieces->add($whiteKing);
        $pieces->add($blackQueen);

        self::assertSame(
            [
                'white-king' => $whiteKing,
                'black-queen' => $blackQueen,
            ],
            $pieces->all()
        );
    }

    public function testAllReturnsIndependentArray(): void
    {
        $pieces = new Pieces();

        $piece = new Piece(
            'white-rook-a',
            Color::WHITE,
            PieceType::ROOK
        );

        $pieces->add($piece);

        $all = $pieces->all();

        unset($all['white-rook-a']);

        self::assertSame(1, $pieces->count());
        self::assertTrue($pieces->contains('white-rook-a'));
        self::assertSame($piece, $pieces->get('white-rook-a'));
    }

    public function testRemoveAllowsIdentifierToBeAddedAgain(): void
    {
        $pieces = new Pieces();

        $first = new Piece(
            'white-pawn-1',
            Color::WHITE,
            PieceType::PAWN
        );

        $second = new Piece(
            'white-pawn-1',
            Color::WHITE,
            PieceType::QUEEN
        );

        $pieces->add($first);
        $pieces->remove('white-pawn-1');
        $pieces->add($second);

        self::assertSame(1, $pieces->count());
        self::assertSame($second, $pieces->get('white-pawn-1'));
    }
}
