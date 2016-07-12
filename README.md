# Game of Tests laravel 

[![Packagist](https://img.shields.io/packagist/v/swisnl/game-of-tests-laravel.svg?maxAge=2592000)](https://packagist.org/packages/swisnl/game-of-tests-laravel)

This package aims to enable a quick implementation of a Game of Tests in Laravel. Is uses the package [swisnl/game-of-tests](https://github.com/swisnl/game-of-tests/) and gives you a set of commands and basic templates to make your own Game of Tests.

This package serves as a way to search through git repositories and find PHP tests. I was inspired by the the [Spotify testing game](https://github.com/spotify/testing-game) I ran in to.

The reason i wanted to my own implementation for PHP was to help gamify testing in the company and encourage testing in general in the teams.

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->


- [How does it work?](#how-does-it-work)
- [Demo](#author)
- [Author](#author)
- [Installation](#installation)
- [Available routes](#available-routes)
- [Commands](#commands)
  - [got:inspect-directory](#gotinspect-directory)
  - [got:inspect-github](#gotinspect-github)
  - [got:inspect](#gotinspect)
  - [got:normalize-names](#gotnormalize-names)
- [Configuration](#configuration)
  - [normalize-names](#normalize-names)
  - [route-prefix](#route-prefix)
  - [excluded-filenames](#excluded-filenames)
  - [excluded-authors](#excluded-authors)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# How does it work?

The Game of Tests works by scanning Git repositories and scanning for known test files. It uses Git blame to attribute tests to developers. You can update through multiple artisan commands for Github, bare directories, or single repositories.

For now it only support PhpUnit, Codeception and Behat, feel free to contibute new parsers to [swisnl/game-of-tests](https://github.com/swisnl/game-of-tests/).

# Demo

I made a demo available which uses this package and show the Game of Tests for the Laravel GIthubGithub organisation at [http://gameoftests.swis.nl](http://gameoftests.swis.nl).

# Author

Created by [Björn Brala](https://www.swis.nl/over-ons/bjorn-brala) ([@bbrala](https://github.com/bbrala)).

# Installation

1. Require this repository

``composer require swisnl/game-of-tests-laravel``

2. Add the service provider to ``app.php`` 

```php
    ...
    \Swis\GotLaravel\Providers\GameOfTestsProvider::class,
    ...
```

3. Publish and run the migration

```php
php artisan vendor:publish --tag="migrations"
php artisan migrate
```

4. (optional) Publish the config and views

```php
php artisan vendor:publish --tag="config"
php artisan vendor:publish --tag="views"
```

This published the config. See [Configuration](#configuration) for the available options.

# Available routes

Routes are based on the configuration of [route-prefix](#route-prefix). Default value is ``got``.


URL | Description
------------ | -------------
``/got`` | List ranking of all time
``/got/score-for-month`` | Ranking of the current month. Optionally you can add: ``?monthsBack=[months]`` to go back any amount of months. For example to get the tests of last month: ``app.url/got/score-for-month?monthsBack=1``.
``/got/score-for-months-back`` | Ranking of the last [months] months (default 1 month). You can add: ``?monthsBack=[months]`` to go back any amount of months. For example to get the tests of last 3 months: ``app.url/got/score-for-month?monthsBack=3``.
``/got/{user}`` | List of parsed tests of ``{user}``. You can add: ``?fromMonthsBack=[months]`` to go back any amount of months. For example to get the tests of last 3 months: ``app.url/got/bjorn-brala?fromMonthsBack=3``, or you can add ``?monthsBack=[months]`` to show results for [months] back. For example to get the tests of last month: ``app.url/got/bjorn-brala?monthsBack=1``.
 

# Commands 

You have a few commands available to update your data.

## got:inspect-directory

Inspect a directory with bare resposities.

```
Usage:
  got:inspect-directory [options] [--] <directory>

Arguments:
  directory

Options:
      --skippast[=SKIPPAST]  Skip all before (and including) this
      --modified[=MODIFIED]  Repository modified since (uses strtotime)
      --only[=ONLY]          Skip every directory except this one
      --dry-run              Only inspect, do not insert into the database
```

## got:inspect-github

Inspect a github organisation.

```
Usage:
  got:inspect-github [options] [--] <organisation>

Arguments:
  organisation

Options:
      --modified[=MODIFIED]  Repository modified since (uses strtotime)
      --dry-run              Only inspect, do not insert into the database
```

## got:inspect

Inspect a Git url.

```
Usage:
  got:inspect [options] [--] <repositoryUrl>

Arguments:
  repositoryUrl

Options:
      --dry-run   Only inspect, do not insert into the database
```

## got:normalize-names

Normalize author names based on the [config setting](#normalize-names).

```
Usage:
  got:normalize-names
```
 
 

# Configuration

## normalize-names

``normalize-names`` : array

Normalize the names in the array to one single result. Sometimes people are bad with their git name. This will normalize names of committees to the array key. 

### Example

``'Björn Brala' => ['bjorn', 'bbrala']``

## route-prefix

``route-prefix`` : string

Prefix for Game of Tests routes. 

### Example

``'route-prefix' => 'got'``

## excluded-filenames

``excluded-filenames`` : array

What filename should not be included in the statistics. This is database LIKE argument. 

### Example

```php
'excluded-filenames' => [
    'tests/ExampleTest.php',
    'vendor/%',
    'tests/_%',
]
```

## excluded-authors

``excluded-authors`` : array

What authors should not be included in the statistics. This is database LIKE argument. 

### Example

```php
'excluded-authors' => [
    'Automated Commiter',
    'System'
]
```
