<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Generates every legal chess move available to a colour in a position.
 *
 * This is shared infrastructure for human players, bots, hints, analysis,
 * and search. It does not choose a move.
 *
 * Candidate moves are generated geometrically and are then passed through
 * MoveValidator so that the final result contains only legal moves.
 *
 * @package   Ephpicman\ChessEngine
 * @author    Ephpicman <ephpicman@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final class LegalMoveGenerator
{
    /**
     * @var array<int, PieceType>
     */
    private const PROMOTION_TYPES = [
        PieceType::QUEEN,
        PieceType::ROOK,
        PieceType::BISHOP,
        PieceType::KNIGHT,
    ];

    public function __construct(
        private MoveValidator $validator = new MoveValidator(),
    ) {
    }

    /**
     * Generates all legal moves for the requested colour.
     *
     * DecisionHistory is required because legality of castling and
     * en-passant depends on information that is not represented by the
     * current piece placement alone.
     *
     * @return array<int, Move>
     */
    public function generate(
        Position $position,
        Color $color,
        DecisionHistory $history,
    ): array {
        $moves = [];

        foreach ($position->getPieces()->all() as $piece) {
            if ($piece->color !== $color) {
                continue;
            }

            $from = $position->getSquareOf($piece);

            if ($from === null) {
                continue;
            }

            foreach ($this->candidateMoves($position, $history, $piece, $from) as $move) {
                if ($this->validator->validate($position, $history, $move)) {
                    $moves[] = $move;
                }
            }
        }

        return $moves;
    }

    /**
     * @return array<int, Move>
     */
    private function candidateMoves(
        Position $position,
        DecisionHistory $history,
        Piece $piece,
        Square $from,
    ): array {
        return match ($piece->type) {
            PieceType::PAWN => $this->pawnMoves($position, $history, $piece, $from),
            PieceType::KNIGHT => $this->jumpMoves(
                $position,
                $piece,
                $from,
                [
                    [1, 2], [2, 1], [2, -1], [1, -2],
                    [-1, -2], [-2, -1], [-2, 1], [-1, 2],
                ],
            ),
            PieceType::BISHOP => $this->slidingMoves(
                $position,
                $piece,
                $from,
                [[1, 1], [1, -1], [-1, 1], [-1, -1]],
            ),
            PieceType::ROOK => $this->slidingMoves(
                $position,
                $piece,
                $from,
                [[1, 0], [-1, 0], [0, 1], [0, -1]],
            ),
            PieceType::QUEEN => $this->slidingMoves(
                $position,
                $piece,
                $from,
                [
                    [1, 1], [1, -1], [-1, 1], [-1, -1],
                    [1, 0], [-1, 0], [0, 1], [0, -1],
                ],
            ),
            PieceType::KING => $this->kingMoves(
                $position,
                $history,
                $piece,
                $from,
            ),
        };
    }

    /**
     * @return array<int, Move>
     */
    private function pawnMoves(
        Position $position,
        DecisionHistory $history,
        Piece $pawn,
        Square $from,
    ): array {
        $moves = [];
        $direction = $pawn->color === Color::WHITE ? 1 : -1;
        $file = $from->file->value;
        $rank = $from->rank->value;

        $forward = $this->squareOrNull(
            $position,
            $file,
            $rank + $direction,
        );

        if ($forward !== null && $position->isEmpty($forward)) {
            $moves = array_merge(
                $moves,
                $this->pawnMoveVariants($position, $pawn, $from, $forward),
            );

            $initialRank = $pawn->color === Color::WHITE ? 2 : 7;
            $double = $this->squareOrNull(
                $position,
                $file,
                $rank + (2 * $direction),
            );

            if (
                $rank === $initialRank
                && $double !== null
                && $position->isEmpty($double)
            ) {
                $moves[] = new Move(
                    new PositionChange(
                        PositionChangeType::MOVE,
                        $pawn,
                        $from,
                        $double,
                    ),
                );
            }
        }

        foreach ([-1, 1] as $fileDelta) {
            $destination = $this->squareOrNull(
                $position,
                $file + $fileDelta,
                $rank + $direction,
            );

            if ($destination === null) {
                continue;
            }

            $target = $position->getPieceAt($destination);

            if ($target !== null && $target->color !== $pawn->color) {
                $moves = array_merge(
                    $moves,
                    $this->pawnMoveVariants(
                        $position,
                        $pawn,
                        $from,
                        $destination,
                        $target,
                    ),
                );
                continue;
            }

            if ($target === null) {
                $lastMove = $history->lastMove();

                if ($lastMove === null) {
                    continue;
                }

                $lastPawn = $this->adjacentEnPassantPawn(
                    $position,
                    $history,
                    $pawn,
                    $from,
                    $destination,
                );

                if ($lastPawn !== null) {
                    $moves[] = new Move(
                        new PositionChange(
                            PositionChangeType::MOVE,
                            $pawn,
                            $from,
                            $destination,
                        ),
                        new PositionChange(
                            PositionChangeType::REMOVE,
                            $lastPawn,
                        ),
                    );
                }
            }
        }

        return $moves;
    }

    /**
     * @return array<int, Move>
     */
    private function pawnMoveVariants(
        Position $position,
        Piece $pawn,
        Square $from,
        Square $to,
        ?Piece $captured = null,
    ): array {
        $changes = [
            new PositionChange(
                PositionChangeType::MOVE,
                $pawn,
                $from,
                $to,
            ),
        ];

        if ($captured !== null) {
            $changes[] = new PositionChange(
                PositionChangeType::REMOVE,
                $captured,
            );
        }

        $promotionRank = $pawn->color === Color::WHITE ? Rank::EIGHT : Rank::ONE;

        if ($to->rank !== $promotionRank) {
            return [new Move(...$changes)];
        }

        $moves = [];

        foreach (self::PROMOTION_TYPES as $type) {
            $replacement = $this->promotionPiece($position, $pawn->color, $type);

            if ($replacement === null) {
                continue;
            }

            $promotionChanges = $changes;
            $promotionChanges[] = new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                null,
                null,
                $replacement,
            );

            $moves[] = new Move(...$promotionChanges);
        }

        return $moves;
    }

    private function promotionPiece(
        Position $position,
        Color $color,
        PieceType $type,
    ): ?Piece {
        foreach ($position->getPieces()->all() as $piece) {
            if (
                $piece->color === $color
                && $piece->type === $type
                && !$position->hasPiece($piece)
            ) {
                return $piece;
            }
        }

        return null;
    }

    private function adjacentEnPassantPawn(
        Position $position,
        DecisionHistory $history,
        Piece $pawn,
        Square $from,
        Square $destination,
    ): ?Piece {
        $lastMove = $history->lastMove();

        if ($lastMove === null) {
            return null;
        }

        $changes = array_values(array_filter(
            $lastMove->changes,
            static fn (PositionChange $change): bool =>
                $change->type === PositionChangeType::MOVE,
        ));

        if (count($changes) !== 1) {
            return null;
        }

        $change = $changes[0];
        $lastPawn = $change->piece;

        if (
            $lastPawn->type !== PieceType::PAWN
            || $lastPawn->color === $pawn->color
            || $change->from === null
            || $change->to === null
        ) {
            return null;
        }

        if (
            abs($change->to->rank->value - $change->from->rank->value) !== 2
            || $change->to->file !== $destination->file
            || $change->to->rank !== $from->rank
        ) {
            return null;
        }

        if ($position->getPieceAt($change->to) !== $lastPawn) {
            return null;
        }

        return $lastPawn;
    }

    /**
     * @param array<int, array{0:int,1:int}> $deltas
     * @return array<int, Move>
     */
    private function jumpMoves(
        Position $position,
        Piece $piece,
        Square $from,
        array $deltas,
    ): array {
        $moves = [];

        foreach ($deltas as [$fileDelta, $rankDelta]) {
            $to = $this->squareOrNull(
                $position,
                $from->file->value + $fileDelta,
                $from->rank->value + $rankDelta,
            );

            if ($to === null) {
                continue;
            }

            $target = $position->getPieceAt($to);

            if ($target !== null && $target->color === $piece->color) {
                continue;
            }

            $changes = [
                new PositionChange(
                    PositionChangeType::MOVE,
                    $piece,
                    $from,
                    $to,
                ),
            ];

            if ($target !== null) {
                $changes[] = new PositionChange(
                    PositionChangeType::REMOVE,
                    $target,
                );
            }

            $moves[] = new Move(...$changes);
        }

        return $moves;
    }

    /**
     * @param array<int, array{0:int,1:int}> $directions
     * @return array<int, Move>
     */
    private function slidingMoves(
        Position $position,
        Piece $piece,
        Square $from,
        array $directions,
    ): array {
        $moves = [];

        foreach ($directions as [$fileStep, $rankStep]) {
            $file = $from->file->value + $fileStep;
            $rank = $from->rank->value + $rankStep;

            while (($to = $this->squareOrNull($position, $file, $rank)) !== null) {
                $target = $position->getPieceAt($to);

                if ($target !== null) {
                    if ($target->color !== $piece->color) {
                        $moves[] = new Move(
                            new PositionChange(
                                PositionChangeType::MOVE,
                                $piece,
                                $from,
                                $to,
                            ),
                            new PositionChange(
                                PositionChangeType::REMOVE,
                                $target,
                            ),
                        );
                    }

                    break;
                }

                $moves[] = new Move(
                    new PositionChange(
                        PositionChangeType::MOVE,
                        $piece,
                        $from,
                        $to,
                    ),
                );

                $file += $fileStep;
                $rank += $rankStep;
            }
        }

        return $moves;
    }

    /**
     * @return array<int, Move>
     */
    private function kingMoves(
        Position $position,
        DecisionHistory $history,
        Piece $king,
        Square $from,
    ): array {
        $moves = $this->jumpMoves(
            $position,
            $king,
            $from,
            [
                [1, 1], [1, 0], [1, -1], [0, 1],
                [0, -1], [-1, 1], [-1, 0], [-1, -1],
            ],
        );

        $rank = $king->color === Color::WHITE ? Rank::ONE : Rank::EIGHT;

        if (
            $from->file === File::E
            && $from->rank === $rank
        ) {
            foreach (
                [
                    [File::H, File::G, File::F],
                    [File::A, File::C, File::D],
                ] as [$rookFile, $kingFile, $rookDestination]
            ) {
                $rookSquare = $position->getBoard()->getSquare(
                    ($rank->value - 1) * 8 + $rookFile->value,
                );
                $rook = $position->getPieceAt($rookSquare);

                if (
                    $rook === null
                    || $rook->color !== $king->color
                    || $rook->type !== PieceType::ROOK
                ) {
                    continue;
                }

                $kingDestination = $position->getBoard()->getSquare(
                    ($rank->value - 1) * 8 + $kingFile->value,
                );
                $rookDestinationSquare = $position->getBoard()->getSquare(
                    ($rank->value - 1) * 8 + $rookDestination->value,
                );

                $moves[] = new Move(
                    new PositionChange(
                        PositionChangeType::MOVE,
                        $king,
                        $from,
                        $kingDestination,
                    ),
                    new PositionChange(
                        PositionChangeType::MOVE,
                        $rook,
                        $rookSquare,
                        $rookDestinationSquare,
                    ),
                );
            }
        }

        return $moves;
    }

    private function squareOrNull(
        Position $position,
        int $file,
        int $rank,
    ): ?Square {
        if ($file < File::A->value || $file > File::H->value) {
            return null;
        }

        if ($rank < Rank::ONE->value || $rank > Rank::EIGHT->value) {
            return null;
        }

        return $position->getBoard()->getSquare(
            ($rank - 1) * 8 + $file,
        );
    }
}
