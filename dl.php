<?php

const SEASON_MONTH_SET = [
    ['2018', '201810'],
    ['2018', '201811'],
    ['2018', '201812'],
    ['2018', '201901'],
    ['2018', '201902'],
    ['2018', '201903'],
    ['2019', '201908'],
    ['2019', '201909'],
    ['2019', '201910'],
    ['2019', '201911'],
    ['2019', '201912'],
    ['2019', '202001'],
    ['2019', '202002'],
];
const MATCH_URL = 'https://tleague.jp/match/';
const TEAM = '琉球';
const CSV = 'php://output';
const CSV_HEADER = ['date', 'team', 'team-score', 'opponent-score', 'opponent-team', 'home-away', 'arena'];

function csv_records($resource, $url) {
    $csv_records = [];

    curl_setopt($resource, CURLOPT_HEADER, false);
    curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($resource, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($resource, CURLOPT_URL, $url);
    curl_setopt($resource, CURLOPT_SSLVERSION,1);
    $html = curl_exec($resource);
    $dom = new DOMDocument();

    libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

    $xpath = new DOMXPath($dom);
    $match_men = $xpath->query('.//li[@class="match-men"]');

    foreach ($match_men as $match) {
        $a_record = [];

        $c_home = $xpath->query('.//div[@class="cell-home"] /a', $match);
        $c_away = $xpath->query('.//div[@class="cell-away"] /a', $match);

        $home = $c_home->item(0)->textContent;
        $away = $c_away->item(0)->textContent;
        if (!in_array(TEAM, [$home, $away], true)) {
            continue;
        }

        $c_score = $xpath->query('.//div[@class="cell-result"] /div /strong', $match);
        $score = '';
        $scores = ['', ''];
        if ($c_score->item(0)) {
            $score = $c_score->item(0)->textContent;
            $scores = explode(' - ', $score);
        }

        $c_date = $xpath->query('.//div[@class="cell-date"]', $match);
        $c_arena = $xpath->query('.//div[@class="cell-arena"] /a', $match);
        $home_game = TEAM === $home;

        $a_record['date']           = $c_date->item(0)->textContent;
        $a_record['team']           = $home_game ? $home      : $away;
        $a_record['team-score']     = $home_game ? $scores[0] : $scores[1];
        $a_record['opponent-score'] = $home_game ? $scores[1] : $scores[0];
        $a_record['opponent-team']  = $home_game ? $away      : $home;
        $a_record['home-away']      = $home_game ? 'home'     : 'away';
        $a_record['arena']          = $c_arena->item(0)->textContent;

        $csv_record = [];
        foreach (CSV_HEADER as $column) {
            $csv_record[$column] = $a_record[$column];
        }

        $csv_records[] = $csv_record;
    }
    return $csv_records;
}

// ====
// main
// ====
$resource = curl_init();
$csv_records = [CSV_HEADER];
$csv_file = fopen(CSV, 'w');
$season_month_set = SEASON_MONTH_SET;

foreach ($season_month_set as $season_month) {
    $season = $season_month[0];
    $month = $season_month[1];
    // example:    $url = 'https://tleague.jp/match/?season=2018&month=201902'
    $url = MATCH_URL . '?season=' . $season . '&month=' . $month;

    $monthly_records = csv_records($resource, $url);
    $csv_records = array_merge($csv_records, $monthly_records);
}
foreach ($csv_records as $record) {
    fputcsv($csv_file, $record);
}
curl_close($resource);
fclose($csv_file);
