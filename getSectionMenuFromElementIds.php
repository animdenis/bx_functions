<?
<?
/*
	Скрипт генерации дерева меню по имеющимся элементам
	Этот скрипт работет внутри комплексного компонента каталога, поэтмому если используете отдельно, не забывайте подключать модули и определить инфоблок каталога.
*/

$ids = $GLOBALS['arrFilter']['ID']; //определяем ID элементов инфоблока, далее определяем в каких разделах они находятся и генерируем дерево
if(empty($ids)) return;

/*Определяем разделы и записываем их в отдельный массив*/
$dbSections = CIBlockElement::GetElementGroups($ids, true);
$secIds = [];
while($obSection = $dbSections->Fetch())
	if(!isset($secIds[$obSection["ID"]]))
		$secIds[$obSection["ID"]] = $obSection["ID"];

/*Считываем дерево разделов стандартным методом (можно не по порядку считывать, но так удобнее)*/
$arFilter = array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ACTIVE' => 'Y'); 
$arSelect = array('ID', 'NAME', 'IBLOCK_SECTION_ID');
$rsSection = CIBlockSection::GetTreeList($arFilter, $arSelect); 
$arAllSec = []; //сюда записываем разделы, где 0 ключ - корневые разделы, остальное -- дочерние разделы
while($arSection = $rsSection->Fetch())
	$arAllSec[$arSection['IBLOCK_SECTION_ID']?:0]][] = [
		'ID' => $arSection['ID'],
		'NAME' => $arSection['NAME'],
		'IS_ACTIVE' => isset($secIds[$arSection["ID"]]) ? 'Y' : 'N' //определяем, есть ли раздел в нашем списке или нет
	];
unset($secIds, $arSection, $rsSection);

/*Определяем функцию рекурсивного построения вложенного массива -- он будет исходный*/
if(!function_exists('getTree'))
{
	function getTree($arAllSec, $parentId)
	{
		$return = [];
		if(isset($arAllSec[$parentId]))
			foreach($arAllSec[$parentId] as $secId)
				$return[] = array_merge($secId, ['CHILD' => getTree($arAllSec, $secId['ID'])]);
		return $return;
	}
}

/*рекурсивно фильтруем разделы с сохранением структуры*/
if(!function_exists('filterSections'))
{
	function filterSections($sectionTree)
	{
		$return = [];
		foreach($sectionTree as $arSec)
		{
			if(count($arSec['CHILD']) == 0 && $arSec['IS_ACTIVE'] == 'Y')
				$return[] = $arSec;
			if(count($arSec['CHILD']) > 0)
			{
				$child = filterSections($arSec['CHILD']);
				if(count($child) > 0)
					$return[] = array_merge($arSec, ['CHILD' => $child]);
			}
		}
		return $return;
	}
}

$sectionTree = getTree($arAllSec, 0);
unset($arAllSec);
$sectionTree = filterSections($sectionTree); //получаем вложенный массив разделов используемых элементами инфоблока

echo '<pre>';
print_r($sectionTree);
echo '</pre>';