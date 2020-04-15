<?php

function covid19ImpactEstimator($data)
{
	readInputData($data);	
}

function readInputData($data){

   $input[] = json_decode($data);
	foreach ($input as $value) {
		$reportedCases = $value->reportedCases;
		$periodType = $value->periodType;
		$timeToElapse = $value->timeToElapse;
		$totalHospitalBeds = $value->totalHospitalBeds;
	}
	currentlyInfected($reportedCases);
	impactInfectionsByRequestedTime($reportedCases, $periodType, $timeToElapse);
	severeImpactInfectionsByRequestedTime($reportedCases, $periodType, $timeToElapse);
	severeCasesByRequestedTime($reportedCases,$periodType, $timeToElapse);
	severeImpactInfectionsByRequestedTime($reportedCases,$periodType, $timeToElapse);
	hospitalBedsByRequestedTime($totalHospitalBeds, $reportedCases,$periodType, $timeToElapse);
 	casesForICUByRequestedTime($reportedCases,$periodType, $timeToElapse);
 	casesForVentilatorsByRequestedTime($reportedCases, $periodType, $timeToElapse);
 	dollarsInFlight($reportedCases,$periodType, $timeToElapse);
 	jsonApi($data,$totalHospitalBeds,$reportedCases, $periodType, $timeToElapse);
}

function currentlyInfected($reportedCases){
	return $reportedCases * 10;
}

function severeCurrentlyInfected($reportedCases){
	return $reportedCases * 50;
}

function infectionsByRequestedTime($periodType, $timeToElapse){
	if (strtolower($periodType) == "days") {
		$exponential = (int)($timeToElapse / 3);
		return pow(2,$exponential);	
	}
	elseif (strtolower($periodType) == "weeks") {
		$noOfDays = (int)($timeToElapse * 7);
		$exponential = (int)($noOfDays / 3);
		return pow(2,$exponential);	
	}
	elseif (strtolower($periodType) == "months") {
		$noOfDays = (int)($timeToElapse * 30);
		$exponential = (int)($noOfDays / 3);
		return pow(2,$exponential);	
	}
	else {
		return "Invalid Period Type.";
	}
}

function impactInfectionsByRequestedTime($reportedCases,$periodType, $timeToElapse){
	return currentlyInfected($reportedCases) * infectionsByRequestedTime($periodType, $timeToElapse);
}

function severeImpactInfectionsByRequestedTime($reportedCases,$periodType, $timeToElapse){
	return severeCurrentlyInfected($reportedCases) * infectionsByRequestedTime($periodType, $timeToElapse);
}

function severeCasesByRequestedTime($reportedCases,$periodType, $timeToElapse){
	$impact = impactInfectionsByRequestedTime($reportedCases,$periodType, $timeToElapse) * 0.15;
	$severe = severeImpactInfectionsByRequestedTime($reportedCases,$periodType, $timeToElapse) * 0.15;
	$combined =["impact"=>(int)$impact,"severe"=>(int)$severe];
	return json_encode($combined);
}

function hospitalBedsByRequestedTime($totalHospitalBeds, $reportedCases,$periodType, $timeToElapse){
	$totalBeds = $totalHospitalBeds * 0.35;
	$combined = severeCasesByRequestedTime($reportedCases,$periodType, $timeToElapse);
	$data[] = json_decode($combined);
	foreach ($data as $value) {
		$impact = $value->impact;
		$severe = $value->severe;
	}
	$impactBeds = $totalBeds - $impact;
	$severeBeds = $totalBeds - $severe;
	$bedsHistory =["impactBeds"=>(int)$impactBeds,"severeBeds"=>(int)$severeBeds];
	return json_encode($bedsHistory);
}
function casesForICUByRequestedTime($reportedCases, $periodType, $timeToElapse){
 $impactICU = impactInfectionsByRequestedTime($reportedCases, $periodType, $timeToElapse) * 0.05;
 $severeICU = severeImpactInfectionsByRequestedTime($reportedCases, $periodType, $timeToElapse) * 0.05; 
 $combinedICU =["impactICU"=>(int)$impactICU,"severeICU"=>(int)$severeICU];
 return json_encode($combinedICU);
}

function casesForVentilatorsByRequestedTime($reportedCases, $periodType, $timeToElapse){
 $impactVents = impactInfectionsByRequestedTime($reportedCases, $periodType, $timeToElapse) * 0.02;
 $severeVents = severeImpactInfectionsByRequestedTime($reportedCases, $periodType, $timeToElapse) * 0.02; 
 $combinedVents =["impactVents"=>(int)$impactVents,"severeVents"=>(int)$severeVents];
 return json_encode($combinedVents);
}

function dollarsInFlight($reportedCases,$periodType, $timeToElapse){
$impact = impactInfectionsByRequestedTime($reportedCases,$periodType, $timeToElapse);
$severe = severeImpactInfectionsByRequestedTime($reportedCases,$periodType, $timeToElapse);
$dollarsImpact = ($impact * 0.65 * 4 * 0.73);
$dollarsSevere = ($severe * 0.65 * 4 * 0.73);
$combinedDollars =["dollarsImpact"=>(int)$dollarsImpact,"dollarsSevere"=>(int)$dollarsSevere];
return json_encode($combinedDollars);
}

function jsonApi($data,$totalHospitalBeds,$reportedCases, $periodType, $timeToElapse){
	$sBT = severeCasesByRequestedTime($reportedCases,$periodType, $timeToElapse);
	$input[] = json_decode($sBT);
	foreach ($input as $value) {
		$impactSevereCasesByRequestedTime = $value->impact;
		$severeCasesByRequestedTime = $value->severe;
	}

	$hosy = hospitalBedsByRequestedTime($totalHospitalBeds, $reportedCases,$periodType, $timeToElapse);
	$hospital[] = json_decode($hosy);
	foreach ($hospital as $value) {
		$impactBeds = $value->impactBeds;
	    $severeBeds = $value->severeBeds;
	}

	$icu = casesForICUByRequestedTime($reportedCases, $periodType, $timeToElapse);
	$iCU[] = json_decode($icu);
	foreach ($iCU as $value) {
		$impactICU = $value->impactICU;
	    $severeICU = $value->severeICU;
	}

	$vents = casesForVentilatorsByRequestedTime($reportedCases, $periodType, $timeToElapse);
	$vENTS[] = json_decode($vents);
	foreach ($vENTS as $value) {
		$impactVents = $value->impactVents;
	    $severeVents = $value->severeVents;
	}

	$dolla = dollarsInFlight($reportedCases,$periodType, $timeToElapse);
	$dOLLA[] = json_decode($dolla);
	foreach ($dOLLA as $value) {
		$dollarsImpact = $value->dollarsImpact;
	    $dollarsSevere = $value->dollarsSevere;
	}
    
	    $impact = [
		 	    "currentlyInfected" =>currentlyInfected($reportedCases),
				"infectionsByRequestedTime" =>impactInfectionsByRequestedTime($reportedCases,$periodType, $timeToElapse),
				"severeCasesByRequestedTime" =>$impactSevereCasesByRequestedTime,
				"hospitalBedsByRequestedTime" => $impactBeds,
				"casesForICUByRequestedTime" =>$impactICU,
				"casesForVentilatorsByRequestedTime" =>$impactVents,
				"dollarsInFlight" =>$dollarsImpact
			  ];

        $severeImpact = [
		 	    "currentlyInfected" =>severeCurrentlyInfected($reportedCases),
				"infectionsByRequestedTime" =>severeImpactInfectionsByRequestedTime($reportedCases,$periodType, $timeToElapse),
				"severeCasesByRequestedTime" =>$severeCasesByRequestedTime,
				"hospitalBedsByRequestedTime" =>$severeBeds,
				"casesForICUByRequestedTime" =>$severeICU,
				"casesForVentilatorsByRequestedTime" =>$severeVents,
				"dollarsInFlight" =>$dollarsSevere
			  ];	

		 $final =["impact"=>$impact,"severeImpact"=>$severeImpact];

		 $restApi = (object)array();
		 $restApi->data = json_decode($data);
		 $restApi->estimate = json_decode(json_encode($final));
		 return json_encode($restApi);

}

// $data = '{
// 			"region": {
// 				"name": "Africa",
// 				"avgAge": 19.7,
// 				"avgDailyIncomeInUSD": 5,
// 				"avgDailyIncomePopulation": 0.71
// 			},
// 			"periodType": "days",
// 			"timeToElapse": 38,
// 			"reportedCases": 2747,
// 			"population": 66622705,
// 			"totalHospitalBeds": 678874
// 		}';
covid19ImpactEstimator($data);