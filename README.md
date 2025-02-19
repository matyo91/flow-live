<p align="center">
  <a href="https://github.com/matyo91/flow-live">
    <img src="assets/images/logo.png" width="auto" height="128px" alt="Flow">
  </a>
</p>

## Installation

PHP 8.3 is the minimal version to use Live Flo
The recommended way to install it through [Composer](http://getcomposer.org) and execute

```bash
composer require darkwood/flow
```

## Usage

### Run Flow exemples

```
bin/console app:flow-exemples
```

### Run CarbonImage

For more details on the CarbonImage functionality, please visit: [https://blog.darkwood.com/article/automatiser-la-creation-de-screenshots-de-code-avec-carbon-now](https://blog.darkwood.com/article/automatiser-la-creation-de-screenshots-de-code-avec-carbon-now)

```
bin/console app:carbon-image
```

### Scrap pages

For more details on the CarbonImage functionality, please visit: [https://blog.darkwood.com/article/scrape-les-sites-de-maniere-efficace](https://blog.darkwood.com/article/scrape-les-sites-de-maniere-efficace)

```
bin/console app:scrap
```

### Gyroscops

Flow as a container for PHP-ETL, for more details, please visit: [https://php-etl.github.io/documentation/](https://php-etl.github.io/documentation/)

```
bin/console app:php-etl
```

### Wave Function Collapse

For more details on Wave Function Collapse, please visit: [https://blog.darkwood.com/article/wave-function-collapse](https://blog.darkwood.com/article/wave-function-collapse)

Inspired by
- https://github.com/CodingTrain/Wave-Function-Collapse
- https://github.com/FeatheredSnek/phpwfc

```
bin/console app:wave-function-collapse
```

## Symfony Certification

For details on certification, please visit [https://certification.symfony.com](https://certification.symfony.com)

```
php -d memory_limit=-1 bin/console app:symfony-certification symfony7
```

## License

Live Flow is released under the MIT License.
