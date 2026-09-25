<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents a mutable collection of chess pieces.
 *
 * Each piece is indexed by its unique identifier.
 *
 * Unlike {@see Board}, which is a fixed immutable collection of
 * squares, this collection is mutable. Pieces can be added to and
 * removed from the collection.
 *
 * The pieces themselves are immutable. Only the collection changes
 * when pieces are added or removed.
 *
 * @package   Ephpicman\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final class Pieces
{
    /**
     * The pieces indexed by their unique identifiers.
     *
     * @var array<string, Piece>
     */
    private array $pieces = [];

    /**
     * Adds a piece to the collection.
     *
     * A piece identifier must be unique within the collection.
     *
     * @param Piece $piece The piece to add.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If another piece with the
     *                                   same identifier already exists.
     */
    public function add(Piece $piece): void
    {
        if (isset($this->pieces[$piece->id])) {
            throw new \InvalidArgumentException(
                "A piece with ID '{$piece->id}' already exists."
            );
        }

        $this->pieces[$piece->id] = $piece;
    }

    /**
     * Removes a piece from the collection by its identifier.
     *
     * If no piece with the supplied identifier exists, the collection
     * remains unchanged.
     *
     * @param string $id The identifier of the piece to remove.
     *
     * @return void
     */
    public function remove(string $id): void
    {
        unset($this->pieces[$id]);
    }

    /**
     * Returns a piece by its identifier.
     *
     * @param string $id The identifier of the requested piece.
     *
     * @return Piece The corresponding piece.
     *
     * @throws \OutOfBoundsException If no piece with the supplied
     *                               identifier exists.
     */
    public function get(string $id): Piece
    {
        if (!isset($this->pieces[$id])) {
            throw new \OutOfBoundsException(
                "Piece with ID '{$id}' does not exist."
            );
        }

        return $this->pieces[$id];
    }

    /**
     * Determines whether a piece with the supplied identifier exists.
     *
     * @param string $id The identifier to search for.
     *
     * @return bool True if the piece exists; otherwise, false.
     */
    public function contains(string $id): bool
    {
        return isset($this->pieces[$id]);
    }

    /**
     * Returns the number of pieces in the collection.
     *
     * @return int The number of pieces currently contained.
     */
    public function count(): int
    {
        return count($this->pieces);
    }

    /**
     * Returns all pieces in the collection.
     *
     * A new array is returned, so modifying the returned collection
     * does not modify this collection.
     *
     * The Piece objects themselves remain immutable.
     *
     * @return array<string, Piece>
     */
    public function all(): array
    {
        return $this->pieces;
    }
}
