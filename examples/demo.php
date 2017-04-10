<?php
if (PHP_SAPI != 'cli') {
	echo "<pre>";
}

$strings = array(
	1 => 'La démocratie est en danger et Mme Le Pen et M. Fillon et Mélenchon expriment de la fascination envers M. Poutine qui arrête 700 opposants.',
	2 => 'C\'est fou à quel point pratiquement toutes les personnes que je connaisse de près ou de loin soutiennent Mélenchon. C\'est assez réconfortant',
	3 => 'Léon Bertrand c\'est notre Fillon régionale. Il vole l\'argent, et fait comme si de rien n\'était.',
	4 => 'Bizarre que Fillon fasse tant confiance à "Bienvenue place Beauvau" alors qu\'il y est accusé d\'avoir activé le Cabinet noir contre Sarkozy', 
);




require_once __DIR__ . '/../autoload.php';
$sentiment = new \PHPInsight\Sentiment(false, "fr");
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
