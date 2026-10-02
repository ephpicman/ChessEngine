<?php

declare(strict_types=1);

namespace Ephpicman\Chess\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\ConsoleBoardRenderer;
use Ephpicman\ChessEngine\Chess\InitialPosition;
use PHPUnit\Framework\TestCase;

final class ConsoleBoardRendererTest extends TestCase
{
    public function testRendersInitialPosition(): void
    {
        $renderer = new ConsoleBoardRenderer();
        $board = $renderer->render(InitialPosition::create());

        self::assertSame(
            "8 r n b q k b n r\n"
            . "7 p p p p p p p p\n"
            . "6 . . . . . . . .\n"
            . "5 . . . . . . . .\n"
            . "4 . . . . . . . .\n"
            . "3 . . . . . . . .\n"
            . "2 P P P P P P P P\n"
            . "1 R N B Q K B N R\n"
            . "  a b c d e f g h",
            $board,
        );
    }
}
