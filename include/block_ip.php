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
 * Read list of IPs
 *
 * @param $sListName
 *
 * @return array
 */
function pluginAntibotGetList($sListName = 'block_ip') {

    $sFile = Config::Get('sys.cache.dir') . 'data/' . Config::Get('plugin.antibot.' . $sListName . '.file');
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
 * @param $sListName
 *
 */
function pluginAntibotPutList($aList, $sListName = 'block_ip') {

    $sFile = Config::Get('sys.cache.dir') . 'data/' . Config::Get('plugin.antibot.' . $sListName . '.file');
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
 * @param bool   $bPeriod
 *
 * @return array
 */
function pluginAntibotAddIp($aList, $sIp = null, $bPeriod = true) {

    if (!$sIp) {
        $sIp = F::GetUserIp();
    }
    if ($bPeriod) {
        $sPeriod = Config::Get('plugin.antibot.block_ip.period');
        if ($sPeriod && $sPeriod != '*') {
            $sDate = F::DateTimeAdd($sPeriod);
        } else {
            $sDate = '*';
        }
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
 * Logs result
 *
 * @param string $sUserIp
 * @param string $sStatus
 */
function pluginAntibotLog($sUserIp, $sStatus) {

    if (Config::Get('plugin.antibot.block_ip.log')) {
        $sLogFile = Config::Get('sys.logs.dir') . 'block_ip.log';
        file_put_contents($sLogFile, date('Y-m-d H:i:s') . ' - ' . $sUserIp . ' - ' . $sStatus . "\n", FILE_APPEND);
    }
}

/**
 * Check user's IP
 *
 * @param string $sUserIp
 */
function pluginAntibotCheck($sUserIp = null) {

    if (!$sUserIp) {
        $sUserIp = F::GetUserIp();
    }
    $sStatus = 'ok';
    $aList = pluginAntibotGetList();
    if ($aList) {
        $iKey = pluginAntibotSeekIp($aList, $sUserIp);
        if ($iKey !== false) {
            $sStatus = 'bad';
        }
    }
    if ($sStatus == 'ok') {
        if ($aList = Config::Get('plugin.antibot.block_ip.list')) {
            if (pluginAntibotIpInList($sUserIp, $aList)) {
                $sStatus = 'black';
            }
        }
    }
    if ($sStatus != 'ok') {
        pluginAntibotExit($sUserIp, $sStatus);
        exit;
    }
    pluginAntibotLog($sUserIp, 'ok');
}

function pluginAntibotExit($sUserIp, $sStatus) {

    pluginAntibotLog($sUserIp, $sStatus);
    F::HttpResponseCode(404);
    if ($aList = Config::Get('plugin.antibot.block_ip.output.enable')) {
        if ($sHtml = Config::Get('plugin.antibot.block_ip.output.html')) {
            $sHtml = str_replace('%%ip%%', $sUserIp, $sHtml);
            echo $sHtml;
        } else {
            echo '404';
        }
    }
    exit;
}

function pluginAntibotIpInList($sIp, $aList) {

    $sLongIp = sprintf('%u', ip2long($sIp));
    foreach ($aList as $sNet) {
        if (strpos($sNet, '-')) {
            // rang
            list($sIp1, $sIp2) = explode('-', $sNet);
            if ($sLongIp >= sprintf('%u', ip2long($sIp1)) && $sLongIp <= sprintf('%u', ip2long($sIp2))) {
                return true;
            }
        } else {
            // single
            if ($sLongIp == sprintf('%u', ip2long($sNet))) {
                return true;
            }
        }
    }
    return false;
}
/**
 * Check user's IP in white list
 *
 * @param string $sUserIp
 *
 * @return bool
 */
function pluginAntibotWhiteList($sUserIp = null) {

    $bResult = false;
    if (Config::Get('plugin.antibot.white_ip.enable')) {
        if ($aList = Config::Get('plugin.antibot.white_ip.list')) {
            if (!$sUserIp) {
                $sUserIp = F::GetUserIp();
            }
            $bResult = pluginAntibotIpInList($sUserIp, $aList);
        }
    }
    if ($bResult) {
        pluginAntibotLog($sUserIp, 'white');
    }
    return $bResult;
}

if (Config::Get('plugin.antibot.block_ip.enable')) {
    $bSkip = false;
    if (Config::Get('plugin.antibot.block_ip.skip_auth_users') && Config::Get('security.user_session_key')) {
        if (isset($_COOKIE[Config::Get('security.user_session_key')])) {
            $bSkip = true;
        }
    }
    if (!$bSkip) {
        $sUserIp = F::GetUserIp();
        if (!pluginAntibotWhiteList($sUserIp)) {
            pluginAntibotCheck($sUserIp);
        }
    }
}

// EOF