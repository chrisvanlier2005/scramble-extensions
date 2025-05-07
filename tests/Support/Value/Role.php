<?php

namespace Tests\Support\Value;

enum Role: string
{
    case Admin = 'admin';
    case User = 'user';
    case Guest = 'guest';
}
