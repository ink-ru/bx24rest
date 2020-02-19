<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
// use Bitrix\Main\Application
// use Bitrix\Main\UserTable

if (!CModule::IncludeModule('intranet'))
{
	ShowError('Невозможно подключить модуль');
	return false;
}

// TODO: IntranetUtils::GetDepartmentManagerOld()
// https://bxapi.ru/?module_id=intranet&class=CIntranetUtils

// Сброс кеша - $_GET["clear_cache"]
if($clear_cache === 'Y') \Bitrix\Main\UserTable::getEntity()->cleanCache();

// список полей таблицы
// \Bitrix\Main\UserTable::getMap();

$iblock_id = COption::GetOptionInt('intranet', 'iblock_structure', 0);

// TODO: https://g-rain-design.ru/blog/posts/d7-orm-user-group/
// TODO: add bloks switches - $arParams['SHOW_DEP'] = 'Y';

$arSelect = Array(
	// "NAME", "LAST_NAME",
	"ID", "ACTIVE", "DATE_REGISTER", "LAST_LOGIN", "LAST_ACTIVITY_DATE", "WORK_POSITION",
	'UF_DEPARTMENT', 'UF_EMPLOYMENT_DATE',
	'UF_TIMEMAN',
	'UF_TM_MAX_START',
	'UF_TM_MIN_FINISH',
	'UF_TM_MIN_DURATION',
	'UF_TM_REPORT_REQ',
	// 'UF_TM_REPORT_TPL',
	'UF_TM_FREE',
	'UF_TM_TIME',
	'UF_TM_DAY',
	'UF_TM_REPORT_DATE',
	'UF_TM_ALLOWED_DELTA',
	// 'WORK_COMPANY',
	// 'WORK_DEPARTMENT',
            );
// $arSelect = Array('*'); // "NAME"

$arFilter = Array();
$res = Bitrix\Main\UserTable::getList(Array(
   "select" => $arSelect,
   "filter" => $arFilter,
));

$full_struct = CIntranetUtils::getStructure();

// TODO: !emplty
$arResult['FULL_STRUCT']['TREE'] = $full_struct['TREE']; // CIntranetUtils::GetDeparmentsTree()
$arResult['FULL_STRUCT']['DEPARTMENTS'] = $full_struct['DATA'];

while ($arRes = $res->fetch())
{
	if($arRes['ID'] < 0) continue;
	// TODO: foreach if( is_object($r) && preg_match('~Type.DateTime~', get_class($r)) )
	$arRes['DATE_REGISTER'] = FormatDateFromDB($arRes['DATE_REGISTER'], 'FULL');
	$arRes['LAST_LOGIN'] = FormatDateFromDB($arRes['LAST_LOGIN'], 'FULL');
	$arRes['LAST_ACTIVITY_DATE'] = FormatDateFromDB($arRes['LAST_ACTIVITY_DATE'], 'FULL');

	$arRes['UF_HEADS'] = $arRes['HEAD_IN_SECTIONS'] = array();
	$arRes['UF_CURR_DEPARTMENT_HEAD'] = $arRes['IS_ADMIN'] = '';
	$arRes['UF_DEPARTMENT_HEAD'] = '';

	{
		$arSelect = array( // array('*');
			'ID',
			'ACTIVE',
			'GLOBAL_ACTIVE',
			'NAME',
			'DEPTH_LEVEL',
			'IBLOCK_TYPE_ID', // structure
			'IBLOCK_CODE', // departments
		);
		
		$dbRes = CIBlockSection::GetList(
			array('active_from' => 'desc'),
			array(
				'IBLOCK_ID' => $iblock_id,
				'UF_HEAD' => $arRes['ID'],
			),
			false,
			$arSelect
		);

		// CIntranetUtils::GetUserDepartments()
		$arRes['HEAD_IN_SECTIONS'] = array();
		while ($tRes = $dbRes->Fetch()) 
		{
			$arRes['HEAD_IN_SECTIONS'][] = $tRes;
		}
		
	}

	// Руководитель текущего подразделения
	if(is_array($arRes['UF_DEPARTMENT']) and count($arRes['UF_DEPARTMENT']) > 0)
	{
		// TODO: Проверять на активность // $appendManager = \CUser::getById($managerId)->fetch();
		$arRes['UF_DEPARTMENT_HEAD'] = CIntranetUtils::GetDepartmentManagerID($arRes['UF_DEPARTMENT'][0]); // https://bxapi.ru/src/?module_id=intranet&name=CIntranetUtils::GetDepartmentManagerID
		// CIntranetUtils::GetIBlockSectionChildren($arRes['UF_DEPARTMENT'])
	}

	if($arRes['UF_DEPARTMENT_HEAD'] == $arRes['ID'])
	{
		$arRes['UF_CURR_DEPARTMENT_HEAD'] = true;

		// Получение руководителя - https://dev.1c-bitrix.ru/community/webdev/user/74260/blog/7810/
		// https://dev.1c-bitrix.ru/learning/course/?COURSE_ID=57&LESSON_ID=2172&LESSON_PATH=5442.4567.4795.2172

		$arRes['UF_HEADS'] = array_keys(CIntranetUtils::GetDepartmentManager(CIntranetUtils::GetUserDepartments($arRes['ID']), $arRes['ID'], true));
		
	}
	else $arRes['UF_CURR_DEPARTMENT_HEAD'] = false;

	$arRes['IS_ADMIN'] = in_array(1, CUser::GetUserGroup($arRes['ID'])) ? true : false;

	if((!is_array($arRes['UF_HEADS']) or count($arRes['UF_HEADS']) < 1 ) and $arRes['UF_DEPARTMENT_HEAD'] > 0)  $arRes['UF_HEADS'] = array( $arRes['UF_DEPARTMENT_HEAD'] );

	$arResult['EMPLS'][] = $arRes;

}

	
foreach ($arResult as $key => $value)
{
	// echo '<pre>';
	print($key." => \n");
	print_r($value);
	// echo '</pre><hr style="border-style: dotted;">';
}

/*$fp = fopen('results.json', 'w');
fwrite($fp, json_encode($arResult));
fclose($fp);*/


if (!empty($arParams['USER_ID']))
{

	if ($arParams['USER_ID'])
	{
		$dbRes = CUser::GetByID($arParams['USER_ID']);
		$arResult['USER'] = $dbRes->Fetch();
	}

	if (is_array($arResult['USER']))
	{
		if (is_array($arResult['USER']['UF_DEPARTMENT']) && count($arResult['USER']['UF_DEPARTMENT']) > 0)
		{
			$dbRes = CIBlockSection::GetList(array('SORT' => 'ASC', 'NAME' => 'ASC'), array('ID' => $arResult['USER']['UF_DEPARTMENT']));
			$arResult['DEPARTMENTS'] = array();
			while ($arSection = $dbRes->Fetch())
			{
				$arResult['DEPARTMENTS'][$arSection['ID']] = $arSection['NAME'];
			}
		}

		if ($arResult['USER']['PERSONAL_PHOTO'])
			$arResult['USER']['PERSONAL_PHOTO'] = CFile::ShowImage($arResult['USER']['PERSONAL_PHOTO'], 200, 200, 'border="0"', '', true);
	}

	print_r($arResult);

}


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
