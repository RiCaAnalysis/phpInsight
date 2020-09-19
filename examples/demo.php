<?php

$strings = array(
    'Weather today is rubbish',
    'This cake looks amazing',
    'His skills are mediocre',
    'He is very talented',
    'She is seemingly very agressive',
    'Marie was enthusiastic about the upcoming trip. Her brother was also passionate about her leaving - he would finally have the house for himself.',
    'To be or not to be?',
    'To be or not to be unsubscribe from me aaaaaa',
    'guys i got this game 2 days ago and i found it great in every aspect so why zero everything improved i love the story . i love to be like a commander as commander shepherd in mass effect and guide my army it feels good . maybe some fighting mechanic in the first chapter was better but it is great as a final',
    'a very good game that could have been a lot better.\r the graphics are good, the gameplay is smooth and the campaing, besides it is a little short, it is very enjoyable\r :3',
    'genuinely one of the worst, most boring and feeble games i’ve ever played. thank god i got it on ea access. no campaign. simple and boring customisation. ui problems everywhere. can’t understand any of the menus. bad gameplay. bad sound. awful flight mechanics. boring maps with not enough cover. terrible character movement. not actually great graphics. i could go on. but it’s boring me.',
    'simply horrible. dlc full of glitches, lack of information, boring events and short and expensive. don`t buy, run away and play another game, forget destiny and bungie'
);

require_once __DIR__ . './../vendor/autoload.php';
$sentiment = new PHPInsight\Sentiment();

foreach ($strings as $string) {
	$scores = $sentiment->score($string);
	$class = $sentiment->categorise($string);
	echo "String: $string\n";
	echo "Dominant: $class, scores: ";
	print_r($scores);
	echo "\n";
}
