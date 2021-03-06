<?php
/**
 * Created by IntelliJ IDEA.
 * User: Peter
 * Date: 31-05-2016
 * Time: 12:35
 */
session_start();
require('../connections/mysql.php');
//require('../php/common/sha256.php');

function setCategories($itemId, $cats, $database) {
    // clear existing
    $sql = "DELETE FROM `item_category` WHERE `itemId` = ".$itemId."";
    $database->query($sql);
    //set new
    $arr = explode(',', $cats);
    $values = [];
    foreach ($arr as $value) {
        $values[] = "('".$itemId."', '".$value."')";
    }
    $insSQL = "INSERT INTO `item_category` (`itemId`, `categoryId`) VALUES ".implode(',', $values)."";
    $database->query($insSQL);
}

function setGroup($itemId, $group, $database) {
    // clear existing
    $sql = "DELETE FROM `item_group` WHERE `itemId` = ".$itemId."";
    $database->query($sql);

    // set new or nothing if no group given
    if ($group) {
        $insSQL = "INSERT INTO `item_group` (`itemId`, `groupId`) VALUES ('".$itemId."', '".$group."')";
        $database->query($insSQL);
    }
}

function getLocation($locationId, $database) {
    $sql = "SELECT * FROM `locations` WHERE `id` = ".$locationId." LIMIT 0,1";
    $result = $database->query($sql);

    return mysqli_fetch_assoc($result);
}

function updateMruImages($id, $database) {
    $sql = "UPDATE `images` SET `recentlyUsed` = NOW() WHERE `id` = '".$id."' ";
    $database->query($sql);
}

switch ($_GET['action']) {
    case 'create':
        $rawData = file_get_contents("php://input");
        $tmp = json_decode($rawData);
        $data = $tmp->calendaritem;

        //$insertedItems[] = array();

        //print_r(count($data));
        for ($i = 0; $i < count($data); $i++) {
            $title = $database->real_escape_string($data[$i]->title);
            $subtitle = $database->real_escape_string($data[$i]->subtitle);
            $text = $database->real_escape_string($data[$i]->text);
            $imageId = $database->real_escape_string($data[$i]->imageId);
            $beginDate = $database->real_escape_string($data[$i]->beginDate);
            $location = $database->real_escape_string($data[$i]->location);
            $locationId = $database->real_escape_string($data[$i]->locationId);
            $imageId = $database->real_escape_string($data[$i]->imageId);
            $phone = $database->real_escape_string($data[$i]->phone);
            $www = $database->real_escape_string($data[$i]->url);
            $email = $database->real_escape_string($data[$i]->email);
            $source = $database->real_escape_string($data[$i]->source);
            $sourceURL = $database->real_escape_string($data[$i]->sourceURL);
            $categorySelector = $database->real_escape_string($data[$i]->categorySelector);
            $groupSelector = $database->real_escape_string($data[$i]->groupSelector);
            $published = $database->real_escape_string($data[$i]->published);
            $permanent = $database->real_escape_string($data[$i]->permanent);
            if($permanent != 1) {$permanent = 0;}
            $general = $database->real_escape_string($data[$i]->general);
            if($general != 1) {$general = 0;}
            $endDate = $database->real_escape_string($data[$i]->endDate);
            $userId = $database->real_escape_string($data[$i]->userId);
            $shortLocation = $database->real_escape_string($data[$i]->shortLocation);
            $highlight = 0;// on creation always 0
            $embargo = $database->real_escape_string($data[$i]->embargo);
            $embargoEndDate = $database->real_escape_string($data[$i]->embargoEndDate);
            $embargoEndTime = $database->real_escape_string($data[$i]->embargoEndTime);

            if ($location == 1) {
                //  set locationId, clear shortlocation
                $shortLocation = '';
                $locationData = getLocation($locationId, $database);
            } else {
               //  clear locationId, set shortlocation
                $location = 0;
                $locationId = 0;
                $locationData = $shortLocation;
            }

            if ($embargo == 1) {
                $embargoDate = new DateTime($embargoEndDate .' '. $embargoEndTime);
            } else {
                $embargo = 0;
                $embargoDate = new DateTime();
            }

            $sql = "INSERT INTO `items` (`title`, `subtitle`, `text`, `beginDate`, `endDate`, `source`, `sourceLink`, `userId`, `imageId`, `locationId`, `shortLocation`, `www`, `highlight`, `created`, `published`, `permanent`, `embargo`, `embargoEnd`, `general`, `email`, `phone`, `location` ) VALUES ('".$title."', '".$subtitle."', '".$text."', '".$beginDate."', '".$endDate."', '".$source."', '".$sourceURL."', '".$userId."', '".$imageId."', '".$locationId."', '".$shortLocation."', '".$www."', '".$highlight."', NOW(), '".$published."', '".$permanent."', '".$embargo."', '".$embargoDate->format('Y-m-d H:i:s')."', '".$general."', '".$email."', '".$phone."', '".$location."' )";

            $database->query($sql);
            $insertedId = $database->insert_id;

            // set category and group for inserted id
            setCategories($insertedId, $categorySelector, $database);
            if($groupSelector > 0) {setGroup($insertedId, $groupSelector, $database);}

            updateMruImages($imageId, $database);

            $insertedItems[] = array('shortLocation'=> $shortLocation, 'name'=> $locationData['name'], 'street'=>$locationData['street'], 'number'=>$locationData['number']);

            // extjs flips when returning item id from php
            //$insertedItems[] = array('id'=> "$insertedId", 'shortLocation'=> $shortLocation, 'name'=> $locationData['name'], 'street'=>$locationData['street'], 'number'=>$locationData['number']);
        }

        echo '{"success": true, "calendaritem": ' . json_encode($insertedItems) . ' }';

        break;

    case 'update':
        $rawdata = file_get_contents("php://input");
        $tmp = json_decode($rawdata);
        $data = $tmp->calendaritem;

        for ($i = 0;$i < count($data); $i++) {
            $id = $data[$i]->id;
            $title = $database->real_escape_string($data[$i]->title);
            $subtitle = $database->real_escape_string($data[$i]->subtitle);
            $text = $database->real_escape_string($data[$i]->text);
            $beginDate = $data[$i]->beginDate;
            $endDate = $data[$i]->endDate;
            $source = $database->real_escape_string($data[$i]->source);
            $sourceLink = $database->real_escape_string($data[$i]->sourceURL);
            $userId = $data[$i]->userId;
            $imageId = $data[$i]->imageId;
            $location = $data[$i]->location;
            $locationId = $data[$i]->locationId;
            if ($location) {
                $locationId = $data[$i]->locationId;
                $shortLocation = '';
            } else {
                $location = 0;
                $locationId = 0;
                $shortLocation = $database->real_escape_string($data[$i]->shortLocation);
            }

            $www = $database->real_escape_string($data[$i]->www);
            $highlight = $data[$i]->highlight;
            if($highlight != 1) {$highlight = 0;}
            $published = $data[$i]->published;
            if($published != 1) {$published = 0;}
            $permanent = $data[$i]->permanent;
            if($permanent != 1) {$permanent = 0;}
            $embargo = $data[$i]->embargo;
            if($embargo != 1) {$embargo = 0;}
            $embargoEndDate = $data[$i]->embargoEndDate;
            $embargoEndTime = $data[$i]->embargoEndTime;
            if ($embargo) {
                $date = new DateTime($embargoEndDate);
                $date->setTime(substr($embargoEndTime, 0, 2), substr($embargoEndTime, 3, 2));
                $embargoDate = $date->format('Y-m-d H:i:s');
            } else {
                $embargoDate = date('Y-m-d H:i:s');
            }
            $general = $data[$i]->general;
            if($general != 1) {$general = 0;}
            $email = $database->real_escape_string($data[$i]->email);
            $phone = $database->real_escape_string($data[$i]->phone);
            $categorySelector = $database->real_escape_string($data[$i]->categorySelector);
            $groupSelector = $database->real_escape_string($data[$i]->groupSelector);

            try {
                $sql = "UPDATE `items` SET `title` = '".$title."', `subtitle` = '".$subtitle."', `text` = '".$text."', `beginDate` = '".$beginDate."', `endDate` = '".$endDate."', `source` = '".$source."', `sourceLink` = '".$sourceLink."', `userId` = '".$userId."', `imageId` = '".$imageId."', `location` = '".$location."', `locationId` = '".$locationId."', `shortLocation` = '".$shortLocation."', `www` = '".$www."', `highlight` = '".$highlight."', `published` = '".$published."', `permanent` = '".$permanent."', `embargo` = '".$embargo."', `embargoEnd` = '".$embargoDate."', `general` = '".$general."', `email` = '".$email."', `phone` = '".$phone."' WHERE `id` = ".$id."";
                if ($categorySelector) {
                    setCategories($id, $categorySelector, $database);
                }
                //if ($groupSelector) {
                    setGroup($id, $groupSelector, $database);
                //}
                $database->query($sql);

                if ($locationId) {
                    // return location data
                    $locationData = getLocation($locationId, $database);
                    $locationName = $locationData['name'];
                    $locationStreet = $locationData['street'];
                    $locationNumber = $locationData['number'];
                    $shortLocation = '';
                } else {
                    // return short location
                    $locationName = '';
                    $locationStreet = '';
                    $locationNumber = '';
                    $shortLocation = $shortLocation;
                }

                echo '{"success": true, "calendaritem": [{"id": "'.$id.'", "locationId": "'.$locationId.'", "name" : "'.$locationName.'", "street": "'.$locationStreet.'", "number": "'.$locationNumber.'", "shortLocation": "'.$shortLocation.'"}]}';
            } catch (Exception $e) {
                echo '{"success": false, "messages": [{"calendaritem": "' . $e->getMessage() . '"}]}';
            }
        }
        break;
    case 'destroy':
        $rawdata = file_get_contents("php://input");
        $tmp = json_decode($rawdata);
        $data = $tmp->calendaritem;

        for ($i = 0; $i < count($data); $i++) {
            // delete item
            $sql = "DELETE FROM `items` WHERE id = ".$data[$i]->id."";
            $database->query($sql);
            // delete category ref
            $sql = "DELETE FROM `item_category` WHERE `itemId` = ".$data[$i]->id."";
            $database->query($sql);
            // delete group ref
            $sql = "DELETE FROM `item_group` WHERE `itemId` = ".$data[$i]->id."";
            $database->query($sql);
            // delete imgage ref
            $sql = "DELETE FROM `item_image` WHERE `itemId` = ".$data[$i]->id."";
            $database->query($sql);

        }
        echo '{"success": true}';

        break;
    default:
        if (isset($_GET['id'])) {

        } else {
            $page = $_GET['page'];
            $pageSize = $_GET['limit'];

            if (isset($_GET['start'])) {
                $start = $_GET['start'];
            } else {
                $start = 0;
            };
            if ($pageSize) {
                $limit = " LIMIT " . $start . ", " . $pageSize . "";
            } else {
                $limit = '';
            }

            if (isset($_GET['sort'])) {
                $sortData = json_decode(stripslashes($_GET['sort']));
                $sortSql = ' ORDER BY ';
                for ($i = 0; $i < count($sortData); $i++) {
                    $sortArray[] = ' `' . $sortData[$i]->property . '` ' . $sortData[$i]->direction . ' ';
                }
                $sortSql .= implode(', ', $sortArray);
            }

            if (isset($_GET['filter'])) {
                $filterData = json_decode(stripslashes($_GET['filter']));
                $filterSql = ' WHERE ';
                for ($i = 0; $i < count($filterData); $i++) {
                    if ($filterData[$i]->operator == 'between') {
                        $values = explode('/', $filterData[$i]->value);
                        $filterArray[] = '`' . $filterData[$i]->property . '` ' . $filterData[$i]->operator . ' \'' . $values[0] . '\' AND \'' . $values[1] . '\' ';
                    } else {
                        $filterArray[] = '`' . $filterData[$i]->property . '` ' . $filterData[$i]->operator . ' \'' . $filterData[$i]->value . '\' ';
                    }
                }
                $filterSql .= implode(' AND ', $filterArray);
            }

            if (isset($_GET['id'])) {
                $filterSql = " WHERE id = '".$_GET['id']."' ";
            }

            $sql = "SELECT SQL_CALC_FOUND_ROWS `id`, `title`, `subtitle`, `text`, `beginDate`, `endDate`, `source`, `sourceURL`, `userId`, `imageId`, `location`, `locationId`, `shortLocation`, `url`, `highlight`, `created`, `lastEdited`, `published`, `imagePath`, `imageName`, `artist`, `recentlyUsed`, `name`, `street`, `number`, `zip`, `city`, `phone`, `email`, `lat`, `lng`, `permanent`, `embargo`, `embargoEnd`, `general` FROM `itemsView`" . $filterSql . " GROUP BY `id` " .$sortSql. " " . $limit . "";
            $result = $database->query($sql);
            $totalQuery = 'SELECT FOUND_ROWS()';
            $totalResult = $database->query($totalQuery);
            $total = mysqli_fetch_row($totalResult);

            if (mysqli_num_rows($result)) {
                while ($obj = mysqli_fetch_assoc($result)) {
                    $obj['title'] = mb_convert_encoding($obj['title'], "utf-8", "utf-8");
                    $obj['subtitle'] = mb_convert_encoding($obj['subtitle'], "utf-8", "utf-8");
                    $obj['text'] = mb_convert_encoding($obj['text'], "utf-8", "utf-8");
                    $obj['shortLocation'] = mb_convert_encoding($obj['shortLocation'], "utf-8", "utf-8");
                    $obj['url'] = mb_convert_encoding($obj['url'], "utf-8", "utf-8");
                    $arr[] = $obj;
                }
                echo '{"success": true, "total": ' . $total[0] . ', "calendaritem": ' . json_encode($arr) . ' }';
            } else {
                echo '{"success": true, "total": "0"}';
            }
        }
        break;
}