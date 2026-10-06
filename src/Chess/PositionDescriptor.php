<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

final readonly class PositionDescriptor
{
    private array $values;

    public function __construct(
        Position $position,
        array $config = [],
        ?DecisionHistory $history = null,
    ) {
        $this->values = $this->calculateValues(
            $position,
            $config,
            $history ?? new DecisionHistory(),
        );
    }

    public function getMaterialScore(Color $color): float
    {
        return $this->values['material_score'][$color->value];
    }

    public function getPieceCountScore(Color $color): float
    {
        return $this->values['piece_count_score'][$color->value];
    }

    public function getLegalDestinationScore(Color $color): float
    {
        return $this->values['legal_destination_score'][$color->value];
    }

    private function calculateValues(
        Position $position,
        array $config,
        DecisionHistory $history,
    ): array {
        return [
            'material_score' => $this->calculateMaterialScores($position, $config),
            'piece_count_score' => $this->calculatePieceCountScores($position),
            'legal_destination_score' => $this->calculateLegalDestinationScores(
                $position,
                $history,
            ),
        ];
    }

    private function calculateMaterialScores(Position $position, array $config): array
    {
        $parameters = $this->getMaterialScoreParameters($config);
        $scores = [
            Color::WHITE->value => 0.0,
            Color::BLACK->value => 0.0,
        ];

        for ($index = 0; $index < 64; $index++) {
            $piece = $position->getPieceAt($position->getBoard()->getSquare($index));

            if ($piece === null) {
                continue;
            }

            $value = match ($piece->type) {
                PieceType::QUEEN => $parameters['queen'],
                PieceType::ROOK => $parameters['rook'],
                PieceType::BISHOP,
                PieceType::KNIGHT => $parameters['minor_piece'],
                PieceType::PAWN => $parameters['pawn'],
                PieceType::KING => 0.0,
            };

            $scores[$piece->color->value] += $value;
        }

        return [
            Color::WHITE->value => $this->normaliseScore(
                $scores[Color::WHITE->value],
                $parameters['maximum'],
            ),
            Color::BLACK->value => $this->normaliseScore(
                $scores[Color::BLACK->value],
                $parameters['maximum'],
            ),
        ];
    }

    private function calculatePieceCountScores(Position $position): array
    {
        $counts = [
            Color::WHITE->value => 0,
            Color::BLACK->value => 0,
        ];

        for ($index = 0; $index < 64; $index++) {
            $piece = $position->getPieceAt($position->getBoard()->getSquare($index));

            if ($piece !== null) {
                $counts[$piece->color->value]++;
            }
        }

        return [
            Color::WHITE->value => $counts[Color::WHITE->value] / 16.0,
            Color::BLACK->value => $counts[Color::BLACK->value] / 16.0,
        ];
    }

    private function calculateLegalDestinationScores(
        Position $position,
        DecisionHistory $history,
    ): array {
        $generator = new LegalMoveGenerator(new MoveValidator());
        $scores = [];

        foreach ([Color::WHITE, Color::BLACK] as $color) {
            $destinations = [];

            foreach ($generator->generate($position, $color, $history) as $move) {
                $change = $move->changes[0] ?? null;

                if ($change?->type !== PositionChangeType::MOVE || $change->to === null) {
                    continue;
                }

                $destinations[$change->to->notation()] = true;
            }

            $pieceCount = $this->countPieces($position, $color);
            $maximumDestinations = 64 - $pieceCount;

            $scores[$color->value] = $maximumDestinations > 0
                ? count($destinations) / $maximumDestinations
                : 0.0;
        }

        return $scores;
    }

    private function countPieces(Position $position, Color $color): int
    {
        $count = 0;

        for ($index = 0; $index < 64; $index++) {
            $piece = $position->getPieceAt($position->getBoard()->getSquare($index));

            if ($piece !== null && $piece->color === $color) {
                $count++;
            }
        }

        return $count;
    }

    private function getMaterialScoreParameters(array $config): array
    {
        $configured = $config['material_score'] ?? [];

        if (!is_array($configured)) {
            throw new \InvalidArgumentException(
                'The material_score configuration must be an array.'
            );
        }

        $parameters = [
            'queen' => 9.0,
            'rook' => 5.0,
            'minor_piece' => 3.0,
            'pawn' => 1.0,
            'maximum' => 103.0,
        ];

        foreach ($parameters as $name => $default) {
            if (!array_key_exists($name, $configured)) {
                continue;
            }

            $value = $configured[$name];

            if (!is_int($value) && !is_float($value)) {
                throw new \InvalidArgumentException(
                    "The material_score parameter '{$name}' must be numeric."
                );
            }

            if (!is_finite((float) $value) || $value < 0) {
                throw new \InvalidArgumentException(
                    "The material_score parameter '{$name}' must be finite and non-negative."
                );
            }

            $parameters[$name] = (float) $value;
        }

        if ($parameters['maximum'] <= 0.0) {
            throw new \InvalidArgumentException(
                'The material_score maximum must be greater than zero.'
            );
        }

        return $parameters;
    }

    private function normaliseScore(float $value, float $maximum): float
    {
        return min(1.0, max(0.0, $value / $maximum));
    }
}
