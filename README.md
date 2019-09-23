phpInsight - Sentiment Analysis in PHP
---------

### Installation
```bash
composer reqiure dmitry-udod/phpInsight
```

### Usage
```php
use PHPInsight\Sentiment;
#....
$analyzer = new Sentiment();
$analyzer->categorise($string); #return text category, positive, negative or neutral
$scores = $analyzer->score($string);

#Returns text scores, for example
#(
#    [neg] => 0.865
#    [neu] => 0.108
#    [pos] => 0.027
#)
```

### Demo
Run
```bash
composer demo
```

### Generate dictionaries
Run
```bash
composer generate-dictionaries
```

### Tests
Install and run phpunit in project dir