<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents the current placement of pieces on a chessboard.
 *
 * A position describes which piece, if any, occupies each square
 * of a {@see Board}.
 *
 * The position combines:
 *
 * - A {@see Board}, which provides the fixed set of squares.
 * - A {@see Pieces} collection, which contains the pieces that exist.
 * - An association between squares and pieces, which describes the
 *   current placement of those pieces.
 *
 * The board itself is immutable, while the position is mutable.
 * Moving a piece therefore changes the position, not the board,
 * square, or piece.
 *
 * A square can contain at most one piece, and a piece can occupy
 * at most one square at a time.
 *
 * The position provides bidirectional lookup:
 *
 * - Determine which piece occupies a square.
 * - Determine which square contains a piece.
 *
 * An empty square is represented by null.
 *
 * The position does not determine whether a move is legal. It only
 * represents the current relationship between pieces and squares.
 * Move validation belongs to a higher-level domain object.
 *
 * @package   Ephpicman\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final class Position
{
    /**
     * The board on which this position exists.
     */
    private Board $board;

    /**
     * The collection of pieces belonging to this position.
     */
    private Pieces $pieces;

    /**
     * The current placement of pieces on the board.
     *
     * Each key is a square's zero-based index and each value is
     * the piece occupying that square.
     *
     * A square that is not present in this array is empty.
     *
     * @var array<int, Piece>
     */
    private array $placements = [];

    /**
     * Creates an empty position on the supplied board.
     *
     * The position initially contains no pieces and therefore
     * every square is empty.
     *
     * @param Board  $board  The board on which the position exists.
     * @param Pieces $pieces The collection of pieces available to
     *                       the position.
     */
    public function __construct(
        Board $board,
        Pieces $pieces
    ) {
        $this->board = $board;
        $this->pieces = $pieces;
    }

    /**
     * Returns the board associated with this position.
     *
     * @return Board The board.
     */
    public function getBoard(): Board
    {
        return $this->board;
    }

    /**
     * Returns the piece collection associated with this position.
     *
     * @return Pieces The piece collection.
     */
    public function getPieces(): Pieces
    {
        return $this->pieces;
    }

    /**
     * Places a piece on a square.
     *
     * The piece must already exist in the position's piece collection.
     * A square can contain only one piece.
     *
     * A piece that is already placed on another square cannot be
     * placed again without first being removed from its current
     * square.
     *
     * @param Square $square The destination square.
     * @param Piece  $piece  The piece to place.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the piece does not belong
     *                                   to the position's collection.
     * @throws \LogicException If the square is already occupied or
     *                         the piece is already placed elsewhere.
     */
    public function place(Square $square, Piece $piece): void
    {
        if (!$this->pieces->contains($piece->id)) {
            throw new \InvalidArgumentException(
                "Piece with ID '{$piece->id}' does not belong to this position."
            );
        }

        if (isset($this->placements[$square->getIndex()])) {
            throw new \LogicException(
                "Square {$square->notation()} is already occupied."
            );
        }

        if ($this->hasPiece($piece)) {
            throw new \LogicException(
                "Piece with ID '{$piece->id}' is already placed."
            );
        }

        $this->placements[$square->getIndex()] = $piece;
    }

    /**
     * Removes the piece occupying a square.
     *
     * If the square is empty, nothing is removed.
     *
     * @param Square $square The square from which the piece is removed.
     *
     * @return Piece|null The removed piece, or null if the square
     *                    was empty.
     */
    public function remove(Square $square): ?Piece
    {
        $index = $square->getIndex();

        if (!isset($this->placements[$index])) {
            return null;
        }

        $piece = $this->placements[$index];

        unset($this->placements[$index]);

        return $piece;
    }

    /**
     * Returns the piece occupying a square.
     *
     * An empty square returns null.
     *
     * @param Square $square The square to inspect.
     *
     * @return Piece|null The occupying piece, or null if the square
     *                    is empty.
     *
     * @example
     * $piece = $position->getPieceAt(
     *     $board->getSquareByNotation('e4')
     * );
     */
    public function getPieceAt(Square $square): ?Piece
    {
        return $this->placements[$square->getIndex()] ?? null;
    }

    /**
     * Determines whether a square is occupied.
     *
     * @param Square $square The square to inspect.
     *
     * @return bool True if the square contains a piece; otherwise, false.
     */
    public function isOccupied(Square $square): bool
    {
        return isset($this->placements[$square->getIndex()]);
    }

    /**
     * Determines whether a square is empty.
     *
     * @param Square $square The square to inspect.
     *
     * @return bool True if the square contains no piece; otherwise, false.
     */
    public function isEmpty(Square $square): bool
    {
        return !$this->isOccupied($square);
    }

    /**
     * Returns the square occupied by a piece.
     *
     * The piece is identified by its object identity.
     *
     * @param Piece $piece The piece whose square is requested.
     *
     * @return Square|null The square containing the piece, or null
     *                     if the piece is not currently placed.
     */
    public function getSquareOf(Piece $piece): ?Square
    {
        foreach ($this->placements as $index => $placedPiece) {
            if ($placedPiece === $piece) {
                return $this->board->getSquare($index);
            }
        }

        return null;
    }

    /**
     * Determines whether a piece is currently placed on the board.
     *
     * The comparison is based on object identity.
     *
     * @param Piece $piece The piece to inspect.
     *
     * @return bool True if the piece occupies a square; otherwise, false.
     */
    public function hasPiece(Piece $piece): bool
    {
        foreach ($this->placements as $placedPiece) {
            if ($placedPiece === $piece) {
                return true;
            }
        }

        return false;
    }
}
