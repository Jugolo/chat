<?php
$lang = array();

$lang['onWork']           = 'Sender forspøgelse';
$lang['onServerError']    = 'Server stødte på en fjel mens den prøvde at behandle din forspøgelse';
$lang['onOkay']           = 'Klient er klar og venter på at lave næste forspøgelse';
$lang['OnlineList']       = 'Online liste';
$lang['setting']          = 'Indstillinger';
$lang['soundSet']         = 'Lyd tændt?';
$lang['textColor']        = 'Tekst farve';
$lang['lang']             = 'Sprog';
$lang['onJoin']           = '%nick kom ind i channel';
$lang['onInaktiv']        = '%nick er nu inaktiv';
$lang['onNInaktiv']       = '%nick kom tilbage';
$lang['onNick']           = '%oldNick ændrede nick til %newNick';
$lang['onLeave']          = '%nick forlod channel';
$lang['onTitle']          = '%nick satte title til: "%title"';
$lang['onExit']           = '%nick forlod chatten';
$lang['WrongCommand']     = 'Kunne ikke genkende kommando';
$lang['smylie']           = 'Humør ikoner';
$lang['onMyLeave']        = 'Du forlod: %channel';
$lang['onKickMsg']        = 'Du blev smidt ud af \'%channel\' pågrund af \'%msg\'';
$lang['onKick']           = 'Du blev smidt ud af \'%channel\'';
$lang['onOKick']          = '%nick blev smidt ud af channel';
$lang['onOKickMsg']       = '%nick blev smidt ud af channel pågrund af \'%msg\'';
$lang['onMyBan']          = 'Du blev udelukket af \'%from\'';
$lang['onBan']            = '%from udelukket %too';
$lang['isBannet']         = 'Du er udelukket i %channel!!';
$lang['banOnStartChan']   = 'Du er blevet nægtet adgang i start chanel. brug /join for at tilgå en anden en';
$lang['onUnban']          = '%nick fik sin udelukkelse fjernet';
$lang['socket_open_fail'] = "Kunne ikke oprette forbindelse til serveren via WebSocket. Prøv at genindlæse siden";
$lang['config_not_valid'] = '%config er ikke en gyldig config nøgle';
$lang['flood']            = 'Du har sendt for mange beskeder per min. Vent venligst med at sende flere beskeder!';
$lang['time']             = 'Tid format';
$lang['timeEx']           = 'Denne muglighed gøre det mugligt at bestemme hvordan tiden skal vises. Denne føgler næsten date i php søg i php.net for nærmere info';
$lang['noOption']         = 'Der er ingen mugligheder';
$lang['ignore']           = 'Ignore';
$lang['unIgnore']         = 'ignore ikke længere';
$lang['onIgnore']         = 'Du ingoere nu %nick';
$lang['onUnIgnore']       = 'Du ingnorere ikke længere %nick';
$lang['upload']           = 'Upload';
$lang['uploadFiles']      = 'Upload fil';
$lang['uploadNow']        = 'Upload filen nu!';
$lang['uploadImage']      = '%nick uploadet føglende billed: %image';
$lang['day_short']        = array(
    'Søn',
    'Man',
    'Tir',
    'Ons',
    'Tor',
    'Fre',
    'Lør'
);
$lang['day_long']        = array(
    'Søndag',
    'Mandag',
    'Tirsdag',
    'Onsdag',
    'Torsdag',
    'Fredag',
    'Lørdag'
);
$lang['month_long']      = array(
    'Januar',
    'Febuar',
    'Marts',
    'April',
    'Maj',
    'Juni',
    'Juli',
    'August',
    'September',
    'Oktober',
    'November',
    'December'
);
$lang['months_short']     = array(
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Okt',
    'Nov',
    'Dec'
);

return $lang;