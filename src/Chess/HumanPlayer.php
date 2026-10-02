<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

use Closure;
use InvalidArgumentException;

/**
 * Provides player decisions from console-style text input.
 *
 * The player only translates input into a Decision. It does not validate
 * chess legality and does not apply moves to the position.
 */
final class HumanPlayer implements Player
{
    private Closure $input;
    private Closure $output;

    public function __construct(
        ?Closure $input = null,
        ?Closure $output = null,
    ) {
        $this->input = $input ?? static function (): string {
            return trim((string) fgets(STDIN));
        };
        $this->output = $output ?? static function (string $message): void {
            fwrite(STDOUT, $message . PHP_EOL);
        };
    }

    public function decide(
        DecisionHistory $decisionHistory,
        Position $position,
    ): Decision {
        $color = $decisionHistory->turn();

        while (true) {
            ($this->output)(sprintf('%s to move > ', $this->colorName($color)));

            try {
                return $this->parse(
                    ($this->input)(),
                    $color,
                    $position,
                );
            } catch (InvalidArgumentException $exception) {
                ($this->output)('Invalid input: ' . $exception->getMessage());
            }
        }
    }

    private function parse(
        string $input,
        Color $color,
        Position $position,
    ): Decision {
        $input = strtolower(trim($input));

        return match ($input) {
            'resign' => new Decision(DecisionType::RESIGN),
            'draw', 'offer draw' => $this->drawOffer($position, $input),
            'accept', 'accept draw' => new Decision(DecisionType::ACCEPT_DRAW),
            default => new Decision(
                DecisionType::MOVE,
                $this->parseMove($input, $color, $position),
            ),
        };
    }

    private function drawOffer(Position $position, string $input): Decision
    {
        throw new InvalidArgumentException(
            "'{$input}' is not a valid standalone decision. Enter a move followed by 'draw' is not supported yet."
        );
    }

    private function parseMove(
        string $input,
        Color $color,
        Position $position,
    ): Move {
        if (!preg_match('/^([a-h][1-8])([a-h][1-8])([qrbn])?$/', $input, $matches)) {
            throw new InvalidArgumentException(
                "Expected a move such as 'e2e4', or 'resign'."
            );
        }

        $from = $position->getBoard()->getSquareByNotation($matches[1]);
        $to = $position->getBoard()->getSquareByNotation($matches[2]);
        $piece = $position->getPieceAt($from);

        if ($piece === null) {
            throw new InvalidArgumentException(
                "There is no piece on {$matches[1]}."
            );
        }

        if ($piece->color !== $color) {
            throw new InvalidArgumentException(
                "The piece on {$matches[1]} does not belong to {$this->colorName($color)}."
            );
        }

        $changes = [
            new PositionChange(
                PositionChangeType::MOVE,
                $piece,
                $from,
                $to,
            ),
        ];

        if (isset($matches[3])) {
            if ($piece->type !== PieceType::PAWN) {
                throw new InvalidArgumentException(
                    'Only a pawn can be promoted.'
                );
            }

            if ($to->rank !== Rank::ONE && $to->rank !== Rank::EIGHT) {
                throw new InvalidArgumentException(
                    'A promotion piece is only valid on the first or eighth rank.'
                );
            }

            $replacement = $this->findPromotionPiece(
                $position,
                $color,
                PieceType::from($matches[3]),
            );

            $changes[] = new PositionChange(
                PositionChangeType::CHANGE,
                $piece,
                replacement: $replacement,
            );
        }

        return new Move(...$changes);
    }

    private function findPromotionPiece(
        Position $position,
        Color $color,
        PieceType $type,
    ): Piece {
        foreach ($position->getPieces()->all() as $piece) {
            if (
                $piece->color === $color
                && $piece->type === $type
                && !$position->hasPiece($piece)
            ) {
                return $piece;
            }
        }

        throw new InvalidArgumentException(
            "No unused {$type->value} promotion piece is available."
        );
    }

    private function colorName(Color $color): string
    {
        return $color === Color::WHITE ? 'White' : 'Black';
    }
}
