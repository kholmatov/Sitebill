<?php
/*
 * User: kholmatov
 * Date: 15/04/2015
 */
 
// header нужен для того, чтобы сообщить браузеру
header('Content-Type: text/xml; charset=utf-8');

//переменная для корневой папки
$document_root=$_SERVER['DOCUMENT_ROOT'].'/';

//Подключения к базу данных MySql
require_once($document_root.'/inc/db.inc.php');

/*
Массивы для конвертация типа объектов для типа АВИТО
    Квартира, апартаменты
    Дом, вилла
    Земельный участок
    Гараж, машиноместо
    Коммерческая недвижимость
*/

$array_topic['dom'] = Array('Дома','Дома-участки','Вилла',' Замок','Коттеджи','Пентхаус','Продажа квартир','Таунхаус','Элитное жилье','Виллы, дома','Замки','Шале');
$array_topic['kvartira'] = Array('1-комн.','2-комн.','3-комн.','4-комн.','Апартаменты','Дуплекс','Апартаменты и квартиры');
$array_topic['comers'] = Array('Доходный дом','Коммерческие помещения','Офисное здание','Пансион','Коммерческая недвижимость');
$array_topic['zemlya'] = Array('Земля');

// Начинаем формировать XML, который будет хранится в переменной $xml

$xml='<?xml version="1.0"?>
      <Ads target="Avito.ru" formatVersion="2">
     ';
	
    // Составляем запрос по выбору кода и названия страны из таблицы
    $query = "SELECT `id`, `user_id`,
        `type_id`,
        `topic_id`, (SELECT t.name FROM re_topic AS t WHERE t.id = d.topic_id) AS tname,
        (SELECT t.url FROM re_topic AS t WHERE t.id = d.topic_id) AS talias,
        (SELECT r.url FROM re_topic AS t LEFT JOIN re_topic as r on t.parent_id=r.id WHERE t.id = d.topic_id) AS parent_alias,
        `country_id`, (SELECT name FROM  re_country AS c WHERE c.country_id=d.country_id) AS country_name,
        `city_id`, (SELECT name FROM  re_city AS c WHERE c.city_id=d.city_id) AS city_name,
        `metro_id`, `district_id`, `street`, `price`, `text`, `contact`, `date_added`, `agent_tel`, `room_count`, `elite`,
        `session_id`, `active`, `sub_id1`, `sub_id2`,
        `reviews_count`, `hot`, `floor`, `floor_count`, `walls`, `balcony`, `square_all`, `square_live`, `square_kitchen`, `bathroom`,
        `img1`, `img2`, `img3`, `img4`, `img5`, `img1_preview`, `img2_preview`, `img3_preview`, `img4_preview`, `img5_preview`,
        `is_telephone`, `furniture`, `plate`, `agent_email`, `number`, `spec`, `floor_cover`, `square_room`, `is_kitchen`, `region_id`,
        `street_id`, `planning`, `dom`, `flat_number`, `owner`, `source`, `adv_date`, `more1`, `more2`, `more3`, `youtube`, `fio`, `phone`,
        `realty_type_id`, `view_count`, `best`, `email`, `distance`, `tmp_password`, `ad_mobile_phone`, `ad_stacionary_phone`, `can_call_start`,
        `can_call_end`, `currency_id`, `premium_status_end`, `bold_status_end`, `vip_status_end`, `meta_title`, `meta_description`, `meta_keywords`,
        `geo_lat`, `geo_lng`, `image`
        FROM re_data AS d
        WHERE d.active > 0 
        ";


    
    $result = mysql_query($query);
    if ($result) {

        // Затем в цикле разбираем запрос, и формируем XML
        while ($row = mysql_fetch_array($result)) {
        
        if(isset($row['square_all']) && $row['square_all']!=""):
        	$area=floatval($row['square_all']);
    	else:
    	 $area=floatval($row['square_live']);
    	endif;
    	 
    	if($area > 0): 
             $xml.="\n".'<Ad>'
                 ."\n".'<Id>'.$row['id'].'</Id>'
                 ."\n".'<Category>Недвижимость за рубежом</Category>'
                 ."\n".'<OperationType>Продам</OperationType>';

            if(in_array($row['tname'], $array_topic['dom'])){
                $xml.="\n".'<ObjectType>Дом, вилла</ObjectType>';
            }elseif(in_array($row['tname'], $array_topic['kvartira'])){
                $xml.="\n".'<ObjectType>Квартира, апартаменты</ObjectType>';
            }elseif(in_array($row['tname'], $array_topic['comers'])){
                $xml.="\n".'<ObjectType>Коммерческая недвижимость</ObjectType>';
            }elseif(in_array($row['tname'], $array_topic['zemlya'])){
                $xml.="\n".'<ObjectType>Земельный участок</ObjectType>';
            }

		   $xml.="\n".'<Country>'.$row['country_name'].'</Country>';
           $xml.="\n".'<Square>'.$area.'</Square>';
           $xml.="\n".'<Description>'.parseToXML($row['text']).'</Description>';
           $xml.="\n".'<ContactPhone>+7 (000) 000-00-00</ContactPhone>';

           //ковертурием валюту от евро в рубл
           $_price=number_format(($row['price']*63),2,'.', '');

           $xml.="\n".'<Price>'.$_price.'</Price>';

           $xml.="\n<Images>";

           if(isset($row['image']) && $row['image'] !=""){
                $xml.= imageJson($row['image']);
           }else{
            	
                $sql="SELECT normal
                      FROM re_data_image AS di INNER JOIN re_image AS i
                      ON di.image_id=i.image_id
                      WHERE di.id=".$row['id']
                    ." ORDER BY di.sort_order LIMIT 10";
                $rs = mysql_query($sql);
                if($rs){
                    while ($item = mysql_fetch_array($rs)) {
                        $xml.="\n".'<Image url="http://site.ru/img/data/'.$item['normal'].'"/>';
                    }
                }
            }
           $xml.="\n</Images>";
           $xml.="\n<AdStatus>Free</AdStatus>";
           $xml.="\n</Ad>";
        endif;
        }
    }

    $xml.="\n</Ads>";
     // Выводим результат на экран
    echo $xml;

function imageJson($json){
    $xml="";
    $img =  unserialize($json);
    $z=0;
    foreach($img as $item){
        if($z < 10)
            $xml.="\n".'<Image url="http://site.ru/img/data/'.$item['normal'].'"/>';
        else
            break;
      $z++;
    }
    return $xml;
}

function parseToXML($htmlStr)
{
    $xmlStr=str_replace('<','&lt;',$htmlStr);
    $xmlStr=str_replace('>','&gt;',$xmlStr);
    $xmlStr=str_replace('"','&quot;',$xmlStr);
    $xmlStr=str_replace("'",'&#39;',$xmlStr);
    $xmlStr=str_replace("&",'&amp;',$xmlStr);
    return $xmlStr;
}

?>
