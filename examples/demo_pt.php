<?php
if (PHP_SAPI != 'cli') {
    echo "<pre>";
}

$strings = array(
    1 => 'O tempo hoje está terrível',
    2 => 'Este bolo parece incrível!',
    3 => 'Suas habilidades são fracas.',
    4 => 'Ele tem muito talento',
    5 => 'Ela parece ser muito agressiva',
    6 => 'Marie está entusiasmada com sua futura viagem. Seu irmão está igualmente apaixonado por sua partida - ele finalmente vai ter a casa só para ele. ',
    7 => 'Ser ou não ser?',
    8 => 'Hoje não é um lindo dia.',
    9 => 'Eu não gosto disso!',
    10 => 'Eu não acho que eles perdem, por enquanto pelo menos ...',
    11 => 'Ela é linda, não é?',
    12 => 'Esse cara :)',
    13 => 'Esse cara :(',
    14 => 'Isso é ótimo por uma razão: Sem efeitos colaterais! ',
    15 => 'Este candidato é o melhor',
);




require_once __DIR__ . '/../autoload.php';
$sentiment = new \PHPInsight\Sentiment(false, 'pt');
foreach ($strings as $string) {

    // calculations:
    $scores = $sentiment->score($string);
    $class = $sentiment->categorise($string);

    // output:
    echo "String: $string\n";
    echo "Dominant: $class, scores: ";
    print_r($scores);
    echo "\n";
}
