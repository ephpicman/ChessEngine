#!/usr/bin/env php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ephpicman\ChessEngine\Chess\ConsoleBoardRenderer;
use Ephpicman\ChessEngine\Chess\Game;
use Ephpicman\ChessEngine\Chess\HumanPlayer;
use Ephpicman\ChessEngine\Chess\InitialPosition;

$position = InitialPosition::create();
$renderer = new ConsoleBoardRenderer();

$white = new HumanPlayer(renderer: $renderer);
$black = new HumanPlayer(renderer: $renderer);

$game = new Game(
    $position,
    $white,
    $black,
    output: static function (string $message): void {
        fwrite(STDOUT, $message . PHP_EOL);
    },
);

$game->play();
