<?php

namespace App\Entity;

/** Stored as its string value ('available' / 'sold') in game_key.status. */
enum KeyStatus: string
{
    case Available = 'available';
    case Sold = 'sold';
}
