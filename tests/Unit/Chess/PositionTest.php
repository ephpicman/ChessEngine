<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Board;
use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\File;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\Pieces;
use Ephpicman\ChessEngine\Chess\Position;
use Ephpicman\ChessEngine\Chess\Rank;
use Ephpicman\ChessEngine\Chess\Square;
use PHPUnit\Framework\TestCase;

final class PositionTest extends TestCase
{
    private Board $board;

    private Pieces $pieces;

    private Position $position;

    protected function setUp(): void
    {
        $this->board = new Board();
        $this->pieces = new Pieces();
        $this->position = new Position(
            $this->board,
            $this->pieces
        );
    }

    public function testConstructorStoresBoardAndPieces(): void
    {
        self::assertSame($this->board, $this->position->getBoard());
        self::assertSame($this->pieces, $this->position->getPieces());
    }

    public function testNewPositionHasNoPlacedPieces(): void
    {
        $square = $this->board->getSquareByNotation('e4');

        self::assertNull($this->position->getPieceAt($square));
        self::assertFalse($this->position->isOccupied($square));
        self::assertTrue($this->position->isEmpty($square));
    }

    public function testPlaceAddsPieceToSquare(): void
    {
        $piece = $this->createPiece(
            'white-king',
            Color::WHITE,
            PieceType::KING
        );

        $square = $this->board->getSquareByNotation('e1');

        $this->pieces->add($piece);

        $this->position->place($square, $piece);

        self::assertSame(
            $piece,
            $this->position->getPieceAt($square)
        );

        self::assertTrue($this->position->isOccupied($square));
        self::assertFalse($this->position->isEmpty($square));
    }

    public function testPlaceThrowsExceptionWhenPieceDoesNotBelongToPosition(): void
    {
        $piece = $this->createPiece(
            'white-king',
            Color::WHITE,
            PieceType::KING
        );

        $square = $this->board->getSquareByNotation('e1');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Piece with ID 'white-king' does not belong to this position."
        );

        $this->position->place($square, $piece);
    }

    public function testPlaceThrowsExceptionWhenSquareIsAlreadyOccupied(): void
    {
        $first = $this->createPiece(
            'white-king',
            Color::WHITE,
            PieceType::KING
        );

        $second = $this->createPiece(
            'white-queen',
            Color::WHITE,
            PieceType::QUEEN
        );

        $square = $this->board->getSquareByNotation('e1');

        $this->pieces->add($first);
        $this->pieces->add($second);

        $this->position->place($square, $first);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Square e1 is already occupied.'
        );

        $this->position->place($square, $second);
    }

    public function testPlaceThrowsExceptionWhenPieceIsAlreadyPlaced(): void
    {
        $piece = $this->createPiece(
            'white-king',
            Color::WHITE,
            PieceType::KING
        );

        $firstSquare = $this->board->getSquareByNotation('e1');
        $secondSquare = $this->board->getSquareByNotation('e2');

        $this->pieces->add($piece);

        $this->position->place($firstSquare, $piece);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            "Piece with ID 'white-king' is already placed."
        );

        $this->position->place($secondSquare, $piece);
    }

    public function testRemoveReturnsPlacedPieceAndClearsSquare(): void
    {
        $piece = $this->createPiece(
            'black-queen',
            Color::BLACK,
            PieceType::QUEEN
        );

        $square = $this->board->getSquareByNotation('d8');

        $this->pieces->add($piece);
        $this->position->place($square, $piece);

        $removed = $this->position->remove($square);

        self::assertSame($piece, $removed);
        self::assertNull($this->position->getPieceAt($square));
        self::assertFalse($this->position->isOccupied($square));
        self::assertTrue($this->position->isEmpty($square));
    }

    public function testRemoveReturnsNullForEmptySquare(): void
    {
        $square = $this->board->getSquareByNotation('e4');

        self::assertNull(
            $this->position->remove($square)
        );
    }

    public function testGetPieceAtReturnsNullForEmptySquare(): void
    {
        $square = $this->board->getSquareByNotation('a1');

        self::assertNull(
            $this->position->getPieceAt($square)
        );
    }

    public function testIsOccupiedReturnsTrueForOccupiedSquare(): void
    {
        $piece = $this->createPiece(
            'white-rook',
            Color::WHITE,
            PieceType::ROOK
        );

        $square = $this->board->getSquareByNotation('a1');

        $this->pieces->add($piece);
        $this->position->place($square, $piece);

        self::assertTrue(
            $this->position->isOccupied($square)
        );
    }

    public function testIsEmptyReturnsTrueForEmptySquare(): void
    {
        $square = $this->board->getSquareByNotation('a1');

        self::assertTrue(
            $this->position->isEmpty($square)
        );
    }

    public function testGetSquareOfReturnsSquareContainingPiece(): void
    {
        $piece = $this->createPiece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT
        );

        $square = $this->board->getSquareByNotation('f3');

        $this->pieces->add($piece);
        $this->position->place($square, $piece);

        self::assertSame(
            $square,
            $this->position->getSquareOf($piece)
        );
    }

    public function testGetSquareOfReturnsNullForUnplacedPiece(): void
    {
        $piece = $this->createPiece(
            'white-bishop',
            Color::WHITE,
            PieceType::BISHOP
        );

        $this->pieces->add($piece);

        self::assertNull(
            $this->position->getSquareOf($piece)
        );
    }

    public function testGetSquareOfUsesPieceIdentity(): void
    {
        $placedPiece = $this->createPiece(
            'white-pawn-1',
            Color::WHITE,
            PieceType::PAWN
        );

        $differentPiece = $this->createPiece(
            'white-pawn-1',
            Color::WHITE,
            PieceType::PAWN
        );

        $square = $this->board->getSquareByNotation('e4');

        $this->pieces->add($placedPiece);

        $this->position->place($square, $placedPiece);

        self::assertSame(
            $square,
            $this->position->getSquareOf($placedPiece)
        );

        self::assertNull(
            $this->position->getSquareOf($differentPiece)
        );
    }

    public function testHasPieceReturnsTrueForPlacedPiece(): void
    {
        $piece = $this->createPiece(
            'black-knight',
            Color::BLACK,
            PieceType::KNIGHT
        );

        $square = $this->board->getSquareByNotation('c6');

        $this->pieces->add($piece);
        $this->position->place($square, $piece);

        self::assertTrue(
            $this->position->hasPiece($piece)
        );
    }

    public function testHasPieceReturnsFalseForUnplacedPiece(): void
    {
        $piece = $this->createPiece(
            'black-bishop',
            Color::BLACK,
            PieceType::BISHOP
        );

        $this->pieces->add($piece);

        self::assertFalse(
            $this->position->hasPiece($piece)
        );
    }

    public function testHasPieceUsesPieceIdentity(): void
    {
        $placedPiece = $this->createPiece(
            'black-pawn-1',
            Color::BLACK,
            PieceType::PAWN
        );

        $differentPiece = $this->createPiece(
            'black-pawn-1',
            Color::BLACK,
            PieceType::PAWN
        );

        $square = $this->board->getSquareByNotation('e5');

        $this->pieces->add($placedPiece);

        $this->position->place($square, $placedPiece);

        self::assertTrue(
            $this->position->hasPiece($placedPiece)
        );

        self::assertFalse(
            $this->position->hasPiece($differentPiece)
        );
    }

    public function testRemoveAllowsPieceToBePlacedAgain(): void
    {
        $piece = $this->createPiece(
            'white-pawn-1',
            Color::WHITE,
            PieceType::PAWN
        );

        $firstSquare = $this->board->getSquareByNotation('e2');
        $secondSquare = $this->board->getSquareByNotation('e4');

        $this->pieces->add($piece);

        $this->position->place($firstSquare, $piece);
        $this->position->remove($firstSquare);
        $this->position->place($secondSquare, $piece);

        self::assertNull(
            $this->position->getPieceAt($firstSquare)
        );

        self::assertSame(
            $piece,
            $this->position->getPieceAt($secondSquare)
        );

        self::assertSame(
            $secondSquare,
            $this->position->getSquareOf($piece)
        );

        self::assertTrue(
            $this->position->hasPiece($piece)
        );
    }

    public function testMultiplePiecesCanOccupyDifferentSquares(): void
    {
        $whiteKing = $this->createPiece(
            'white-king',
            Color::WHITE,
            PieceType::KING
        );

        $blackKing = $this->createPiece(
            'black-king',
            Color::BLACK,
            PieceType::KING
        );

        $whiteQueen = $this->createPiece(
            'white-queen',
            Color::WHITE,
            PieceType::QUEEN
        );

        $e1 = $this->board->getSquareByNotation('e1');
        $e8 = $this->board->getSquareByNotation('e8');
        $d1 = $this->board->getSquareByNotation('d1');

        $this->pieces->add($whiteKing);
        $this->pieces->add($blackKing);
        $this->pieces->add($whiteQueen);

        $this->position->place($e1, $whiteKing);
        $this->position->place($e8, $blackKing);
        $this->position->place($d1, $whiteQueen);

        self::assertSame(
            $whiteKing,
            $this->position->getPieceAt($e1)
        );

        self::assertSame(
            $blackKing,
            $this->position->getPieceAt($e8)
        );

        self::assertSame(
            $whiteQueen,
            $this->position->getPieceAt($d1)
        );

        self::assertSame(
            $e1,
            $this->position->getSquareOf($whiteKing)
        );

        self::assertSame(
            $e8,
            $this->position->getSquareOf($blackKing)
        );

        self::assertSame(
            $d1,
            $this->position->getSquareOf($whiteQueen)
        );
    }

    private function createPiece(
        string $id,
        Color $color,
        PieceType $type,
    ): Piece {
        return new Piece($id, $color, $type);
    }
}
