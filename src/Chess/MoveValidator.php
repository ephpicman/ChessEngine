<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Validates chess moves according to the current position and the
 * complete decision history of the game.
 *
 * The validator implements the movement and king-safety rules of
 * standard chess. It does not modify the supplied Position or
 * DecisionHistory.
 *
 * The validator covers:
 *
 * - piece movement;
 * - captures;
 * - pawn double moves;
 * - promotion;
 * - en passant;
 * - castling;
 * - turn order;
 * - king safety;
 * - protection against exposing one's own king to check.
 *
 * Game-ending conditions such as checkmate, stalemate, repetition and
 * the fifty/seventy-five-move rules are game-state conditions rather
 * than properties of one individual Move and therefore are outside
 * the responsibility of this class.
 *
 * @package   Ephpicman\ChessEngine
 * @author    Ephpicman <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final class MoveValidator
{
    /**
     * Determines whether a move is legal in the current position.
     *
     * @param Position $position
     * @param DecisionHistory $history
     * @param Move $move
     *
     * @return bool
     */
    public function validate(
        Position $position,
        DecisionHistory $history,
        Move $move,
    ): bool {
        $changes = $move->changes;

        if ($changes === []) {
            return false;
        }

        $turn = $history->turn();

        $moveChanges = array_values(
            array_filter(
                $changes,
                static fn (PositionChange $change): bool =>
                    $change->type === PositionChangeType::MOVE
            )
        );

        $removeChanges = array_values(
            array_filter(
                $changes,
                static fn (PositionChange $change): bool =>
                    $change->type === PositionChangeType::REMOVE
            )
        );

        $changeChanges = array_values(
            array_filter(
                $changes,
                static fn (PositionChange $change): bool =>
                    $change->type === PositionChangeType::CHANGE
            )
        );

        if ($moveChanges === []) {
            return false;
        }

        /*
         * A normal chess move contains exactly one MOVE change.
         *
         * Castling is the only standard move represented by two MOVE
         * changes because both the king and rook change squares.
         */
        if (count($moveChanges) > 2) {
            return false;
        }

        $movingPiece = $moveChanges[0]->piece;

        if ($movingPiece->color !== $turn) {
            return false;
        }

        if (!$position->hasPiece($movingPiece)) {
            return false;
        }

        if (
            $position->getSquareOf($movingPiece) === null
        ) {
            return false;
        }

        /*
         * A second MOVE change means this must be castling.
         */
        if (count($moveChanges) === 2) {
            if (
                count($removeChanges) !== 0
                || count($changeChanges) !== 0
            ) {
                return false;
            }

            if (!$this->isCastling(
                $position,
                $history,
                $moveChanges
            )) {
                return false;
            }
        } else {
            if (!$this->validateSinglePieceMove(
                $position,
                $history,
                $moveChanges[0],
                $removeChanges,
                $changeChanges
            )) {
                return false;
            }
        }

        /*
         * Apply the candidate move to a virtual board and verify that
         * the moving side's king is not left in check.
         */
        $board = $this->createVirtualBoard($position);

        if (!$this->applyChanges($board, $changes)) {
            return false;
        }

        return !$this->isKingInCheck(
            $board,
            $turn
        );
    }

    /**
     * Validates a move involving one moving piece.
     *
     * @param Position $position
     * @param DecisionHistory $history
     * @param PositionChange $moveChange
     * @param array<int, PositionChange> $removeChanges
     * @param array<int, PositionChange> $changeChanges
     *
     * @return bool
     */
    private function validateSinglePieceMove(
        Position $position,
        DecisionHistory $history,
        PositionChange $moveChange,
        array $removeChanges,
        array $changeChanges,
    ): bool {
        if (
            $moveChange->from === null
            || $moveChange->to === null
        ) {
            return false;
        }

        $piece = $moveChange->piece;

        $currentSquare = $position->getSquareOf($piece);

        if (
            $currentSquare === null
            || $currentSquare->getIndex() !== $moveChange->from->getIndex()
        ) {
            return false;
        }

        $destination = $moveChange->to;
        $destinationPiece = $position->getPieceAt($destination);

        if (
            $destinationPiece !== null
            && $destinationPiece->color === $piece->color
        ) {
            return false;
        }

        $isCapture = $destinationPiece !== null;

        /*
         * A normal capture must explicitly contain the REMOVE change.
         */
        if ($isCapture) {
            if (count($removeChanges) !== 1) {
                return false;
            }

            if ($removeChanges[0]->piece !== $destinationPiece) {
                return false;
            }
        } else {
            /*
             * An empty destination may only have REMOVE when this is
             * an en passant capture.
             */
            if (count($removeChanges) > 1) {
                return false;
            }
        }

        if (count($changeChanges) > 1) {
            return false;
        }

        /*
         * Promotion is the only supported CHANGE operation.
         */
        if ($changeChanges !== []) {
            if (!$this->isPromotion(
                $piece,
                $destination,
                $changeChanges[0]
            )) {
                return false;
            }
        }

        return match ($piece->type) {
            PieceType::PAWN => $this->validatePawnMove(
                $position,
                $history,
                $moveChange,
                $removeChanges,
                $changeChanges,
            ),

            PieceType::KNIGHT => $this->validateKnightMove(
                $moveChange
            ),

            PieceType::BISHOP => $this->validateBishopMove(
                $position,
                $moveChange
            ),

            PieceType::ROOK => $this->validateRookMove(
                $position,
                $moveChange
            ),

            PieceType::QUEEN => $this->validateQueenMove(
                $position,
                $moveChange
            ),

            PieceType::KING => $this->validateKingMove(
                $moveChange
            ),
        };
    }

    /**
     * Validates a pawn move.
     */
    private function validatePawnMove(
        Position $position,
        DecisionHistory $history,
        PositionChange $moveChange,
        array $removeChanges,
        array $changeChanges,
    ): bool {
        $from = $moveChange->from;
        $to = $moveChange->to;
        $pawn = $moveChange->piece;

        if ($from === null || $to === null) {
            return false;
        }

        $direction = $pawn->color === Color::WHITE ? 1 : -1;

        $fromFile = $from->file->value;
        $fromRank = $from->rank->value;

        $toFile = $to->file->value;
        $toRank = $to->rank->value;

        $fileDifference = $toFile - $fromFile;
        $rankDifference = $toRank - $fromRank;

        $destinationPiece = $position->getPieceAt($to);

        /*
         * One-square forward move.
         */
        if (
            $fileDifference === 0
            && $rankDifference === $direction
            && $destinationPiece === null
        ) {
            return $this->validatePromotionRequirement(
                $pawn,
                $to,
                $changeChanges
            );
        }

        /*
         * Two-square initial move.
         */
        $initialRank = $pawn->color === Color::WHITE ? 2 : 7;

        if (
            $fileDifference === 0
            && $rankDifference === 2 * $direction
            && $fromRank === $initialRank
            && $destinationPiece === null
        ) {
            $middleSquare = $position->getBoard()->getSquareByNotation(
                $from->file->letter()
                . (string) ($fromRank + $direction)
            );

            if ($position->isOccupied($middleSquare)) {
                return false;
            }

            return $changeChanges === [];
        }

        /*
         * Normal pawn capture.
         */
        if (
            abs($fileDifference) === 1
            && $rankDifference === $direction
            && $destinationPiece !== null
            && $destinationPiece->color !== $pawn->color
        ) {
            return $this->validatePromotionRequirement(
                $pawn,
                $to,
                $changeChanges
            );
        }

        /*
         * En passant.
         */
        if (
            abs($fileDifference) === 1
            && $rankDifference === $direction
            && $destinationPiece === null
        ) {
            return $this->validateEnPassant(
                $position,
                $history,
                $moveChange,
                $removeChanges
            );
        }

        return false;
    }

    /**
     * Validates an en passant capture.
     */
    private function validateEnPassant(
        Position $position,
        DecisionHistory $history,
        PositionChange $moveChange,
        array $removeChanges,
    ): bool {
        if (count($removeChanges) !== 1) {
            return false;
        }

        $lastMove = $history->lastMove();

        if ($lastMove === null) {
            return false;
        }

        $lastMoveChanges = array_values(
            array_filter(
                $lastMove->changes,
                static fn (PositionChange $change): bool =>
                    $change->type === PositionChangeType::MOVE
            )
        );

        if (count($lastMoveChanges) !== 1) {
            return false;
        }

        $lastMoveChange = $lastMoveChanges[0];
        $lastPawn = $lastMoveChange->piece;

        if ($lastPawn->type !== PieceType::PAWN) {
            return false;
        }

        if ($lastPawn->color === $moveChange->piece->color) {
            return false;
        }

        if (
            $lastMoveChange->from === null
            || $lastMoveChange->to === null
        ) {
            return false;
        }

        $lastRankDifference =
            abs(
                $lastMoveChange->to->rank->value
                - $lastMoveChange->from->rank->value
            );

        if ($lastRankDifference !== 2) {
            return false;
        }

        if (
            $lastMoveChange->to->file !== $moveChange->to->file
            || $lastMoveChange->to->rank !== $moveChange->from->rank
        ) {
            return false;
        }

        if ($removeChanges[0]->piece !== $lastPawn) {
            return false;
        }

        $capturedSquare = $position->getSquareOf($lastPawn);

        if ($capturedSquare === null) {
            return false;
        }

        return $capturedSquare->getIndex()
            === $lastMoveChange->to->getIndex();
    }

    /**
     * Validates a promotion.
     */
    private function isPromotion(
        Piece $piece,
        Square $destination,
        PositionChange $change,
    ): bool {
        if ($piece->type !== PieceType::PAWN) {
            return false;
        }

        $promotionRank = $piece->color === Color::WHITE
            ? Rank::EIGHT
            : Rank::ONE;

        if ($destination->rank !== $promotionRank) {
            return false;
        }

        if ($change->piece !== $piece) {
            return false;
        }

        if ($change->replacement === null) {
            return false;
        }

        if ($change->replacement->color !== $piece->color) {
            return false;
        }

        return match ($change->replacement->type) {
            PieceType::QUEEN,
            PieceType::ROOK,
            PieceType::BISHOP,
            PieceType::KNIGHT => true,

            PieceType::KING,
            PieceType::PAWN => false,
        };
    }

    /**
     * Ensures that a pawn reaching its final rank is promoted.
     */
    private function validatePromotionRequirement(
        Piece $pawn,
        Square $destination,
        array $changeChanges,
    ): bool {
        $promotionRank = $pawn->color === Color::WHITE
            ? Rank::EIGHT
            : Rank::ONE;

        if ($destination->rank === $promotionRank) {
            return count($changeChanges) === 1;
        }

        return $changeChanges === [];
    }

    /**
     * Validates a knight move.
     */
    private function validateKnightMove(
        PositionChange $moveChange,
    ): bool {
        $from = $moveChange->from;
        $to = $moveChange->to;

        if ($from === null || $to === null) {
            return false;
        }

        $file = abs($to->file->value - $from->file->value);
        $rank = abs($to->rank->value - $from->rank->value);

        return ($file === 1 && $rank === 2)
            || ($file === 2 && $rank === 1);
    }

    /**
     * Validates a bishop move.
     */
    private function validateBishopMove(
        Position $position,
        PositionChange $moveChange,
    ): bool {
        return $this->validateSlidingMove(
            $position,
            $moveChange,
            true,
            false,
        );
    }

    /**
     * Validates a rook move.
     */
    private function validateRookMove(
        Position $position,
        PositionChange $moveChange,
    ): bool {
        return $this->validateSlidingMove(
            $position,
            $moveChange,
            false,
            true,
        );
    }

    /**
     * Validates a queen move.
     */
    private function validateQueenMove(
        Position $position,
        PositionChange $moveChange,
    ): bool {
        return $this->validateSlidingMove(
            $position,
            $moveChange,
            true,
            true,
        );
    }

    /**
     * Validates a king's ordinary one-square move.
     */
    private function validateKingMove(
        PositionChange $moveChange,
    ): bool {
        $from = $moveChange->from;
        $to = $moveChange->to;

        if ($from === null || $to === null) {
            return false;
        }

        return max(
            abs($to->file->value - $from->file->value),
            abs($to->rank->value - $from->rank->value),
        ) === 1;
    }

    /**
     * Validates a sliding piece movement and ensures that no piece
     * blocks the path.
     */
    private function validateSlidingMove(
        Position $position,
        PositionChange $moveChange,
        bool $diagonal,
        bool $straight,
    ): bool {
        $from = $moveChange->from;
        $to = $moveChange->to;

        if ($from === null || $to === null) {
            return false;
        }

        $fileDifference = $to->file->value - $from->file->value;
        $rankDifference = $to->rank->value - $from->rank->value;

        $sameFile = $fileDifference === 0;
        $sameRank = $rankDifference === 0;
        $sameDiagonal =
            abs($fileDifference) === abs($rankDifference);

        $validDirection =
            ($straight && ($sameFile || $sameRank))
            || ($diagonal && $sameDiagonal);

        if (!$validDirection || ($sameFile && $sameRank)) {
            return false;
        }

        $fileStep = $fileDifference <=> 0;
        $rankStep = $rankDifference <=> 0;

        $file = $from->file->value + $fileStep;
        $rank = $from->rank->value + $rankStep;

        while (
            $file !== $to->file->value
            || $rank !== $to->rank->value
        ) {
            $square = $position->getBoard()->getSquare(
                ($rank - 1) * 8 + $file
            );

            if ($position->isOccupied($square)) {
                return false;
            }

            $file += $fileStep;
            $rank += $rankStep;
        }

        return true;
    }

    /**
     * Validates castling.
     *
     * Standard chess always starts from the orthodox initial position,
     * therefore the original king and rook identities are recoverable
     * from the current position and their movement history.
     */
    private function isCastling(
        Position $position,
        DecisionHistory $history,
        array $moveChanges,
    ): bool {
        $kingChange = null;
        $rookChange = null;

        foreach ($moveChanges as $change) {
            if ($change->piece->type === PieceType::KING) {
                $kingChange = $change;
            }

            if ($change->piece->type === PieceType::ROOK) {
                $rookChange = $change;
            }
        }

        if ($kingChange === null || $rookChange === null) {
            return false;
        }

        if (
            $kingChange->from === null
            || $kingChange->to === null
            || $rookChange->from === null
            || $rookChange->to === null
        ) {
            return false;
        }

        if (
            $kingChange->piece->color !== $rookChange->piece->color
        ) {
            return false;
        }

        $color = $kingChange->piece->color;

        $rank = $color === Color::WHITE ? Rank::ONE : Rank::EIGHT;

        if ($kingChange->from->file !== File::E
            || $kingChange->from->rank !== $rank
        ) {
            return false;
        }

        if ($kingChange->to->rank !== $rank) {
            return false;
        }

        $kingSide = $kingChange->to->file === File::G;
        $queenSide = $kingChange->to->file === File::C;

        if (!$kingSide && !$queenSide) {
            return false;
        }

        $rookFromFile = $kingSide ? File::H : File::A;
        $rookToFile = $kingSide ? File::F : File::D;

        if (
            $rookChange->from->file !== $rookFromFile
            || $rookChange->from->rank !== $rank
            || $rookChange->to->file !== $rookToFile
            || $rookChange->to->rank !== $rank
        ) {
            return false;
        }

        if ($history->hasMoved($kingChange->piece)) {
            return false;
        }

        if ($history->hasMoved($rookChange->piece)) {
            return false;
        }

        /*
         * The rook must still be on its original square.
         */
        if (
            $position->getPieceAt($rookChange->from)
            !== $rookChange->piece
        ) {
            return false;
        }

        /*
         * Every square between king and rook must be empty.
         */
        $fromFile = $kingChange->from->file->value;
        $rookFile = $rookChange->from->file->value;
        $rankValue = $rank->value;

        $start = min($fromFile, $rookFile) + 1;
        $end = max($fromFile, $rookFile) - 1;

        for ($file = $start; $file <= $end; $file++) {
            $square = $position->getBoard()->getSquare(
                ($rankValue - 1) * 8 + $file
            );

            if (
                $position->isOccupied($square)
                && $square->getIndex() !== $kingChange->from->getIndex()
            ) {
                return false;
            }
        }

        /*
         * The king may not castle while in check or through an attacked
         * square.
         */
        if (
            $this->isKingInCheck(
                $this->createVirtualBoard($position),
                $color
            )
        ) {
            return false;
        }

        $transitFile = $kingSide ? File::F : File::D;

        $transitSquare = $position->getBoard()->getSquare(
            ($rankValue - 1) * 8 + $transitFile->value
        );

        $destinationSquare = $kingChange->to;

        $board = $this->createVirtualBoard($position);

        unset($board[$kingChange->from->getIndex()]);
        $board[$transitSquare->getIndex()] = $kingChange->piece;

        if ($this->isSquareAttacked(
            $board,
            $transitSquare,
            $color->opposite()
        )) {
            return false;
        }

        unset($board[$transitSquare->getIndex()]);
        $board[$destinationSquare->getIndex()] = $kingChange->piece;

        if ($this->isSquareAttacked(
            $board,
            $destinationSquare,
            $color->opposite()
        )) {
            return false;
        }

        return true;
    }

    /**
     * Creates a mutable virtual representation of the current board.
     *
     * @return array<int, Piece>
     */
    private function createVirtualBoard(Position $position): array
    {
        $board = [];

        for ($index = 0; $index < 64; $index++) {
            $square = $position->getBoard()->getSquare($index);
            $piece = $position->getPieceAt($square);

            if ($piece !== null) {
                $board[$index] = $piece;
            }
        }

        return $board;
    }

    /**
     * Applies position changes to a virtual board.
     *
     * @param array<int, Piece> $board
     * @param array<int, PositionChange> $changes
     */
private function applyChanges(array &$board, array $changes): bool
{
    foreach ($changes as $change) {
        if ($change->type !== PositionChangeType::REMOVE) {
            continue;
        }

        $index = $this->findPieceIndex(
            $board,
            $change->piece,
        );

        if ($index === null) {
            return false;
        }

        unset($board[$index]);
    }

    foreach ($changes as $change) {
        if ($change->type !== PositionChangeType::MOVE) {
            continue;
        }

        if (
            $change->from === null
            || $change->to === null
        ) {
            return false;
        }

        $fromIndex = $change->from->getIndex();
        $toIndex = $change->to->getIndex();

        if (
            !isset($board[$fromIndex])
            || $board[$fromIndex] !== $change->piece
        ) {
            return false;
        }

        unset($board[$fromIndex]);

        $board[$toIndex] = $change->piece;
    }

    foreach ($changes as $change) {
        if ($change->type !== PositionChangeType::CHANGE) {
            continue;
        }

        if ($change->replacement === null) {
            return false;
        }

        $index = $this->findPieceIndex(
            $board,
            $change->piece,
        );

        if ($index === null) {
            return false;
        }

        $board[$index] = $change->replacement;
    }

    return true;
}

    /**
     * Finds the square index occupied by a specific piece.
     *
     * @param array<int, Piece> $board
     */
    private function findPieceIndex(
        array $board,
        Piece $piece,
    ): ?int {
        foreach ($board as $index => $placedPiece) {
            if ($placedPiece === $piece) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Determines whether a king is currently in check.
     *
     * @param array<int, Piece> $board
     */
    private function isKingInCheck(
        array $board,
        Color $color,
    ): bool {
        $kingIndex = null;

        foreach ($board as $index => $piece) {
            if (
                $piece->type === PieceType::KING
                && $piece->color === $color
            ) {
                $kingIndex = $index;
                break;
            }
        }

        if ($kingIndex === null) {
            return true;
        }

        $kingSquare = Square::fromIndex($kingIndex);

        return $this->isSquareAttacked(
            $board,
            $kingSquare,
            $color->opposite()
        );
    }

    /**
     * Determines whether a square is attacked by the specified color.
     *
     * This intentionally checks attacks independently of whether the
     * attacking side's own king would be exposed. This follows the
     * FIDE definition of an attacked square.
     *
     * @param array<int, Piece> $board
     */
    private function isSquareAttacked(
        array $board,
        Square $target,
        Color $attacker,
    ): bool {
        foreach ($board as $index => $piece) {
            if ($piece->color !== $attacker) {
                continue;
            }

            $from = Square::fromIndex($index);

            if ($this->pieceAttacksSquare(
                $board,
                $piece,
                $from,
                $target
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether one piece attacks a target square.
     *
     * This method does not consider king safety of the attacking side.
     *
     * @param array<int, Piece> $board
     */
    private function pieceAttacksSquare(
        array $board,
        Piece $piece,
        Square $from,
        Square $target,
    ): bool {
        $fileDifference =
            $target->file->value - $from->file->value;

        $rankDifference =
            $target->rank->value - $from->rank->value;

        $fileDistance = abs($fileDifference);
        $rankDistance = abs($rankDifference);

        return match ($piece->type) {
            PieceType::KING =>
                max($fileDistance, $rankDistance) === 1,

            PieceType::KNIGHT =>
                ($fileDistance === 1 && $rankDistance === 2)
                || ($fileDistance === 2 && $rankDistance === 1),

            PieceType::PAWN =>
                $rankDifference === (
                    $piece->color === Color::WHITE ? 1 : -1
                )
                && $fileDistance === 1,

            PieceType::BISHOP =>
                $this->attacksAlongLine(
                    $board,
                    $from,
                    $target,
                    true,
                    false
                ),

            PieceType::ROOK =>
                $this->attacksAlongLine(
                    $board,
                    $from,
                    $target,
                    false,
                    true
                ),

            PieceType::QUEEN =>
                $this->attacksAlongLine(
                    $board,
                    $from,
                    $target,
                    true,
                    true
                ),
        };
    }

    /**
     * Determines whether a sliding piece has an unobstructed line
     * to the target square.
     *
     * @param array<int, Piece> $board
     */
    private function attacksAlongLine(
        array $board,
        Square $from,
        Square $target,
        bool $diagonal,
        bool $straight,
    ): bool {
        $fileDifference =
            $target->file->value - $from->file->value;

        $rankDifference =
            $target->rank->value - $from->rank->value;

        $sameFile = $fileDifference === 0;
        $sameRank = $rankDifference === 0;
        $sameDiagonal =
            abs($fileDifference) === abs($rankDifference);

        if (
            !(
                ($straight && ($sameFile || $sameRank))
                || ($diagonal && $sameDiagonal)
            )
            || ($sameFile && $sameRank)
        ) {
            return false;
        }

        if ($sameFile && $sameRank) {
            return false;
        }

        $fileStep = $fileDifference <=> 0;
        $rankStep = $rankDifference <=> 0;

        $file = $from->file->value + $fileStep;
        $rank = $from->rank->value + $rankStep;

        while (
            $file !== $target->file->value
            || $rank !== $target->rank->value
        ) {
            $index = ($rank - 1) * 8 + $file;

            if (isset($board[$index])) {
                return false;
            }

            $file += $fileStep;
            $rank += $rankStep;
        }

        return true;
    }
}
