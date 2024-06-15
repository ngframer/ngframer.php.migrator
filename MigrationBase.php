<?php

namespace NGFramer\NGFramerPHPBase\migration;

abstract class MigrationBase
{
    // Function to run migration to newer version.
    public abstract function up(): array;


    // Function to run migration to older version.
    public abstract function down(): array;
}
