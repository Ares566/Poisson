<?php
/**
 * Предсказание счета футбольного матча 
 * опираясь на статистику ЧМ
 * Используется распределение Пуассона
 */

print_r(match(1,30));
print_r(match(1,15));
print_r(match(10,30));

/**
 * Return score in match between teams $c1 and $c2
 * @param  int $c1 first team id
 * @param  int $c2 second team id
 * @return array possible score
 */
function match($c1,$c2)
{
	// предсказанный счет
	$aRetVals = array(0,0);
	if($c1 == $c2)
		return $aRetVals;

	// берем статистику единожды
	static $aAvgTeam = null;
	if(is_null($aAvgTeam))
		$aAvgTeam = initStat();

	// вероятность забить у команды $c1 команде $c2
	$aRetVals[0] = $aAvgTeam['team'][$c1]['fratioAtacking'] * $aAvgTeam['team'][$c2]['fratioDefending'] * $aAvgTeam['favgGoalsScored'];

	// вероятность забить у команды $c2 команде $c1
	$aRetVals[1] = $aAvgTeam['team'][$c2]['fratioAtacking'] * $aAvgTeam['team'][$c1]['fratioDefending'] * $aAvgTeam['favgGoalsScored'];
	
	

	// генератор случайностей, который опирается на усредненные значения
	// если нужно смоделировать несколько игр
	// $fDiff = (abs($aRetVals[0]-$aRetVals[1])*exp(1))*100;
	// $fPDif = mt_rand(-$fDiff,$fDiff);
	
	// $aRetVals[0] -= $fPDif/100;
	// $aRetVals[1] += $fPDif/100;
	
	// пытаемся по Пуасону предугадать максимально вероятный счет
	$fMAX1 = $fMAX2 = $iScored1 = $iScored2 = 0;
	for ($i=0; $i <= ceil($aAvgTeam['favgGoalsScored']+1); $i++) { 
		
		$fPV1 = poisson($i,$aRetVals[0]);
		// можно показать все возможные варианты счета
		if($fMAX1 < $fPV1){
			$fMAX1 = $fPV1;
			$iScored1 = $i;
		}

		$fPV2 = poisson($i,$aRetVals[1]);
		if($fMAX2 < $fPV2){
			$fMAX2 = $fPV2;
			$iScored2 = $i;
		}		
	}
	$aRetVals = array($iScored1,$iScored2);

	return $aRetVals;

}

/**
 * Initialize and analyze statistic of teams scores
 * @return array description of each team: Attacking and Defending ratios
 */
function initStat()
{
	include './data.php';

	// всего голов забито в чемпионате
	$iallGoalsScored = 0;

	// всего голов пропущено в чемпионате
	$iallGoalsSkiped = 0;

	// всего игр в чемпионате
	$iallMatches = 0;
	// командные коэффициенты 
	$aAvgTeam = array();
	foreach ($data as $iTID => $aTeam) {
		$iallGoalsScored += $aTeam['goals']['scored'];
		$iallGoalsSkiped += $aTeam['goals']['skiped'];
		$iallMatches += $aTeam['games'];
		if($aTeam['games']){
			$aAvgTeam[$iTID]['favgTeamScored'] = $aTeam['goals']['scored'] / $aTeam['games'];
			$aAvgTeam[$iTID]['favgTeamSkiped'] = $aTeam['goals']['skiped'] / $aTeam['games'];
		}
	}
	// среднее количество голов в чемпионате
	$favgGoalsScored = $iallGoalsScored/$iallMatches;
	$favgGoalsSkiped = $iallGoalsSkiped/$iallMatches;

	// сила атаки и защиты команды
	$aRetVals = array();
	foreach ($aAvgTeam as $iTID => $aAVGS) {
		$aRetVals['team'][$iTID]['fratioAtacking'] = $aAVGS['favgTeamScored'] / $favgGoalsScored;
		$aRetVals['team'][$iTID]['fratioDefending'] = $aAVGS['favgTeamSkiped'] / $favgGoalsSkiped;
	}	
	$aRetVals['favgGoalsScored'] = $favgGoalsScored;
	$aRetVals['favgGoalsSkiped'] = $favgGoalsSkiped;

	return $aRetVals;
}


function factorial($number)
{
        if ($number < 2) {
                return 1;
        } else {
                return ($number * factorial($number-1));
        }
}
/**
 * Poisson distribution
 * @param  int $occurrence 	occurrence
 * @param  float $chance  	chance
 * @return distribution
 */
function poisson($occurrence, $chance)
{
        $e = exp(1);

        $a = pow($e, (-1 * $chance));
        $b = pow($chance,$occurrence);
        $c = factorial($occurrence);

        return $a * $b / $c;
}
