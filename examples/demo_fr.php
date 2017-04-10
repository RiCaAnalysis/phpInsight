<?php
if (PHP_SAPI != 'cli') {
	echo "<pre>";
}

$strings = array(
    1 => 'La météo aujourd\'hui est immonde',
    2 => 'Ce gateau a l\'air incroyable !',
    3 => 'Ses compétences sont médiocres.',
    4 => 'Il a beaucoup de talent',
    5 => 'Elle semble être très agressive',
    6 => 'Marie est enthousiaste à propos de son futur voyage. Son frêre est égalemment passioné par son départ - il va enfin avoir la maison pour lui tout seul.',
    7 => 'Être ou ne pas être ?',
    8 => 'Ce n\'est pas un beau jour aujourd\'hui.',
    9 => 'Je l\'aime pas !',
    10 => 'Je pense qu\'ils ne perdent pas, pour l\'instant au moins...',
    11 => 'Elle est belle non ?',
    12 => 'Ce mec :)',
    13 => 'Ce mec :(',
    14 => 'Ceci est génial pour une raison : Pas d\'effets de bords ! ',
    15 => 'Ce candidat est le meilleur',
);




require_once __DIR__ . '/../autoload.php';
$sentiment = new \PHPInsight\Sentiment(false, 'fr');
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
