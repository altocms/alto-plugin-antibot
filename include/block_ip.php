<?php
/*---------------------------------------------------------------------------
 * @Project: Alto CMS
 * @Project URI: http://altocms.com
 * @Plugin Name: AntiBot
 * @Plugin Id: antibot
 * @Plugin URI:
 * @Description: AntiBot Plugin for Alto CMS
 * @Copyright: Alto CMS Team
 * @License: GNU GPL v2 & MIT
 *----------------------------------------------------------------------------
 */

/**
 * Read list of blocked IPs
 *
 * @return array
 */
function pluginAntibotGetList() {

    $sFile = Config::Get('sys.cache.dir') . 'data/' . Config::Get('plugin.antibot.block_ip.file');
    if (is_file($sFile)) {
        $aList = file($sFile);
        if ($aList) {
            $aList = array_map('trim', $aList);
            $sDate = date('Y-m-d H:i:s');
            $func = function ($sStr) use ($sDate) {
                $aVal = explode('|', $sStr);
                return !isset($aVal[2]) || (isset($aVal[2]) && $aVal[2] > $sDate);
            };
            $aFilterList = array_filter($aList, $func);
            return $aFilterList;
        }
    }
    return array();
}

/**
 * Save list of blocked IPs
 *
 * @param array $aList
 */
function pluginAntibotPutList($aList) {

    $sFile = Config::Get('sys.cache.dir') . 'data/' . Config::Get('plugin.antibot.block_ip.file');
    file_put_contents($sFile, implode("\n", $aList));
}

/**
 * Seek IP and returns its key in array
 *
 * @param array  $aList
 * @param string $sIp
 *
 * @return bool|int
 */
function pluginAntibotSeekIp($aList, $sIp = null) {

    if (!$sIp) {
        $sIp = F::GetUserIp();
    }
    $func = function ($sStr) use ($sIp) {
        $aVal = explode('|', $sStr);
        return $aVal[0] == $sIp;
    };
    $aFilterList = array_filter($aList, $func);
    if ($aFilterList) {
        $sLine = reset($aList);
        $iKey = array_search($sLine, $aList);
        return $iKey;
    }
    return false;
}

/**
 * Add IP in list
 *
 * @param array  $aList
 * @param string $sIp
 *
 * @return array
 */
function pluginAntibotAddIp($aList, $sIp = null) {

    if (!$sIp) {
        $sIp = F::GetUserIp();
    }
    $sPeriod = Config::Get('plugin.antibot.block_ip.period');
    if ($sPeriod && $sPeriod != '*') {
        $sDate = F::DateTimeAdd($sPeriod);
    } else {
        $sDate = '*';
    }
    $sLine = $sIp . '|' . date('Y-m-d H:i:s') . '|' . $sDate;
    $iKey = pluginAntibotSeekIp($aList, $sIp);
    if ($iKey === false) {
        $aList[] = $sLine;
    } else {
        $aList[$iKey] = $sLine;
    }
    return $aList;
}

/**
 * Check user's IP
 */
function pluginAntibotCheck() {

    $aList = pluginAntibotGetList();
    $iKey = pluginAntibotSeekIp($aList);
    if ($iKey !== false) {
        F::HttpHeader(404);
        exit;
    }
}

if (Config::Get('plugin.antibot.block_ip.enable')) {
    pluginAntibotCheck();
}

// EOF