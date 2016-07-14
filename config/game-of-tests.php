<?php
return [
    /**
     * Normalize the names in the array to one single result.
     * Sometimes people are bad with their git name.
     */
    'normalize-names' => [
        'Björn Brala' => ['bjorn', 'bbrala'],
    ],

    /**
     * Prefix for Game of Tests routes.
     */
    'route-prefix' => 'got',

    /**
     * What filename should not be included in the statistics.
     */
    'excluded-filenames' => [
        'tests/ExampleTest.php',
        'vendor/%',
        'tests/\_%',
    ],

    /**
     * Exclude authors from the statistics, like example an
     * automated system that bootstraps a project with some
     * basic tests.
     */
    'excluded-authors' => [

    ],

];