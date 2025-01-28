<?php

function describeTest(string $name): string
{
    return ucfirst(str_replace('_', ' ', $name));
}

function describeSuite(string $name): string
{
    return ucfirst(str_replace('_', ' ', $name));
} 