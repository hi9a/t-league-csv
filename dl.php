<?php

const SEASON_MONTH_SET = [
    ['2018', '201810'],
    ['2018', '201811'],
    ['2018', '201812'],
    ['2018', '201901'],
    ['2018', '201902'],
    ['2018', '201903'],
];
const MATCH_URL = 'https://tleague.jp/match/';
const TEAM = '琉球';
const CSV = '/tmp/output.csv';
const CSV_TITLE = ['date', 'team', 'team score', 'opponent score', 'opponent', 'home/away', 'arena'];

function csv_records($resource, $url) {
    $csv_records = [];

    curl_setopt($resource, CURLOPT_HEADER, false);
    // http://php.net/manual/en/function.curl-exec.php
    // if the CURLOPT_RETURNTRANSFER option is set, it will return the result on success, FALSE on failure. 
    curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($resource, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($resource, CURLOPT_URL, $url);
    curl_setopt($resource, CURLOPT_SSLVERSION,1); 
    $html = curl_exec($resource);
    $dom = new DOMDocument();

    // 取得htmlが適切でないらしく下記warningが出力される。
    // PHP Warning:  DOMDocument::loadHTML(): htmlParseEntityRef: no name in Entity
    // 出したくないなら
    // libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

    $xpath = new DOMXPath($dom);
    $match_men = $xpath->query('.//li[@class="match-men"]');

    foreach ($match_men as $match) {
        $c_home = $xpath->query('.//div[@class="cell-home"] /a', $match);
        $c_away = $xpath->query('.//div[@class="cell-away"] /a', $match);

        $home = $c_home->item(0)->textContent;
        $away = $c_away->item(0)->textContent;
        if (!in_array(TEAM, [$home, $away])) {
            continue;
        }

        $c_score = $xpath->query('.//div[@class="cell-result"] /div /strong', $match);
        $score = $c_score->item(0)->textContent;
        $scores = explode(' ', $score); // [home score, '-'(constant string value), away score]

        $c_date = $xpath->query('.//div[@class="cell-date"]', $match);
        $date = $c_date->item(0)->textContent;

        $home_game = TEAM === $home;

        $team           = $home_game ? $home      : $away;
        $opponent       = $home_game ? $away      : $home;
        $team_score     = $home_game ? $scores[0] : $scores[2];
        $opponent_score = $home_game ? $scores[2] : $score[0];
        $homeaway       = $home_game ? 'home'     : 'away'; 

        $c_arena = $xpath->query('.//div[@class="cell-arena"] /a', $match);
        $arena = $c_arena->item(0)->textContent;

        $csv_records[] = [$date, $team, $team_score, $opponent_score, $opponent, $homeaway, $arena];
    }
    return $csv_records;
}

////////////////////////////////
// main
////////////////////////////////
$resource = curl_init();
$csv_records = [CSV_TITLE];
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
